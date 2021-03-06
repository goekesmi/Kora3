<?php namespace App\Http\Controllers;

use App\ComboListField;
use App\DateField;
use App\DocumentsField;
use App\Field;
use App\FileTypeField;
use App\Form;
use App\FormGroup;
use App\GalleryField;
use App\GeneratedListField;
use App\ListField;
use App\Metadata;
use App\MultiSelectListField;
use App\OptionPreset;
use App\Page;
use App\Project;
use App\ProjectGroup;
use App\Record;
use App\RecordPreset;
use App\RichTextField;
use App\ScheduleField;
use App\TextField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ImportController extends Controller {

    /*
    |--------------------------------------------------------------------------
    | Import Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles import of Project/Form structures as well as Record
    | data
    |
    */

    /**
     * @var string - Valid formats for record import
     */
    const JSON = "JSON";
    const XML = "XML";

    /**
     * Constructs controller and makes sure user is authenticated.
     */
    public function __construct() {
        $this->middleware('auth');
        $this->middleware('active');
    }

    /**
     * Exports a sample file of the structure for importing data.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  string $type - Format type
     * @return string - html for the file download
     */
    public function exportSample($pid, $fid, $type){
        if(!FormController::validProjForm($pid, $fid))
            return redirect('projects/'.$pid)->with('k3_global_error', 'form_invalid');

        $form = FormController::getForm($fid);

        if(!(\Auth::user()->isFormAdmin($form)))
            return redirect('projects/'.$pid)->with('k3_global_error', 'not_form_admin');

        $fields = Field::where('fid', '=', $fid)->get();

        switch($type) {
            case 'XML':
                $xml = '<?xml version="1.0" encoding="utf-8"?><Records><Record>';

                foreach($fields as $field) {
                    $xml .= $field->getTypedField()->getExportSample($field->slug, "XML");
                }

                $xml .= '</Record></Records>';

                header("Content-Disposition: attachment; filename=" . $form->name . '_exampleData.xml');
                header("Content-Type: application/octet-stream; ");

                echo $xml;
                break;
            case 'JSON':
                $tmpArray = array();

                foreach($fields as $field) {
                    $fieldArray = $field->getTypedField()->getExportSample($field->slug, "JSON");
                    $tmpArray = array_merge($fieldArray, $tmpArray);
                }

                $json = [$tmpArray];

                $json = json_encode($json);

                header("Content-Disposition: attachment; filename=" . $form->name . '_exampleData.json');
                header("Content-Type: application/octet-stream; ");

                echo $json;
                break;
        }
    }

    /**
     * Builds the matchup table for comparing imported tag names to actual field names.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  Request $request
     * @return array - Contains html for table as well as list of record objects
     */
    public function matchupFields($pid, $fid, Request $request) {
        $form = FormController::getForm($fid);

        if(!(\Auth::user()->isFormAdmin($form)))
            return redirect('projects/'.$pid)->with('k3_global_error', 'not_form_admin');

        //if zip file
        if(!is_null($request->file('files'))) {
            $zip = new \ZipArchive();
            $res = $zip->open($request->file('files'));
            if($res) {
                $dir = config('app.base_path').'storage/app/tmpFiles/impU'.\Auth::user()->id;
                if(file_exists($dir)) {
                    //clear import directory
                    $files = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($dir),
                        \RecursiveIteratorIterator::LEAVES_ONLY
                    );
                    foreach($files as $file) {
                        // Skip directories (they would be added automatically)
                        if(!$file->isDir()) {
                            unlink($file);
                        }
                    }
                }
                $zip->extractTo($dir.'/');
                $zip->close();
            }
        }

        $type = strtoupper($request->type);

        $tagNames = array();
        $recordObjs = array();

        switch($type) {
            case self::XML:
                $xml = simplexml_load_file($request->file('records'));

                foreach($xml->children() as $record) {
                    array_push($recordObjs, $record->asXML());
                    foreach($record->children() as $fields) {
                        array_push($tagNames, $fields->getName());
                    }
                }

                $tagNames = array_unique($tagNames);
                break;
            case self::JSON:
                $json = json_decode(file_get_contents($request->file('records')), true);

                foreach($json as $kid => $record) {
                    $recordObjs[$kid] = $record;
                    foreach(array_keys($record) as $field) {
                        array_push($tagNames, $field);
                    }
                }

                $tagNames = array_unique($tagNames);
                break;
        }

        $fields = $form->fields()->get();
        $table = '';
        $first = true;

        foreach($fields as $field) {
            $table .= '<div class="form-group mt-xl half">';
            if($first)
                $table .= '<label>Form Field Names</label>';
            $table .= '<div class="solid-box get-slug-js" slug="'.$field->slug.'">';
            $table .= $field->name.' ('.$field->slug.')';
            $table .= '</div></div>';
            $table .= '<div class="form-group mt-xl half">';
            if($first)
                $table .= '<label>Select Uploaded Field to Match</label>';
            $table .= '<select class="single-select get-tag-js" data-placeholder="Select field if applicable">';
            $table .= '<option></option>';
            foreach($tagNames as $name) {
                if($field->slug==$name)
                    $table .= '<option val="'.$name.'" selected>' . $name . '</option>';
                else
                    $table .= '<option val="'.$name.'">'.$name.'</option>';
            }
            $table .= '</select>';
            $table .= '</div>';
            $table .= '<div class="form-group"></div>';

            $first = false;
        }

        $table .= '<div class="form-group mt-xxxl">';
        $table .= '<input type="button" class="btn final-import-btn-js" value="Upload Records">';
        $table .= '</div>';

        $result = array();
        $result['records'] = $recordObjs;
        $result['matchup'] = $table;
        $result['type'] = $type;

        return $result;
    }

    /**
     * Import Kora 3 records via XML of JSON file. We will leave field specific stuff here because it's too specific.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  Request $request
     */
    public function importRecord($pid, $fid, Request $request) {
        $form = FormController::getForm($fid);

        if(!(\Auth::user()->isFormAdmin($form)))
            return redirect('projects/'.$pid)->with('k3_global_error', 'not_form_admin');

        $matchup = $request->table;

        $record = $request->record;

        $recRequest = new Request();
        $recRequest['userId'] = \Auth::user()->id;

        if($request->type==self::XML) {
            $record = simplexml_load_string($record);

            $originKid = $record->attributes()->kid;
            if(!is_null($originKid))
                $originRid = explode('-', $originKid)[2];
            else
                $originRid = null;

            foreach($record->children() as $key => $field) {
                $fieldSlug = $matchup[$key];
                $flid = Field::where('slug', '=', $fieldSlug)->get()->first()->flid;
                $type = $field->attributes()->type;

                //Type wasnt provided so we have to hunt for it
                if(is_null($type))
                    $type = Field::where('slug', '=', $fieldSlug)->get()->first()->type;

                //TODO::modular?
                //TODO::add assoc

                if($type == 'Text' | $type == 'Rich Text' | $type == 'Number' | $type == 'List')
                    $recRequest[$flid] = (string)$field;
                else if($type == 'Multi-Select List') {
                    $recRequest[$flid] = (array)$field->value;
                } else if($type == 'Generated List') {
                    $recRequest[$flid] = (array)$field->value;
                } else if($type == 'Combo List') {
                    $values = array();
                    $nameone = str_replace(" ","_",ComboListField::getComboFieldName(FieldController::getField($flid), 'one'));
                    $nametwo = str_replace(" ","_",ComboListField::getComboFieldName(FieldController::getField($flid), 'two'));
                    foreach($field->Value as $val) {
                        if((string)$val->{$nameone} != '')
                            $fone = '[!f1!]' . (string)$val->{$nameone} . '[!f1!]';
                        else if(sizeof($val->{$nameone}->value) == 1)
                            $fone = '[!f1!]' . (string)$val->{$nameone}->value . '[!f1!]';
                        else
                            $fone = '[!f1!]' . implode("[!]",(array)$val->{$nameone}->value) . '[!f1!]';


                        if((string)$val->{$nametwo} != '')
                            $ftwo = '[!f2!]' . (string)$val->{$nametwo} . '[!f2!]';
                        else if(sizeof($val->{$nametwo}->value) == 1)
                            $ftwo = '[!f2!]' . (string)$val->{$nametwo}->value . '[!f2!]';
                        else
                            $ftwo = '[!f2!]' . implode("[!]",(array)$val->{$nametwo}->value) . '[!f2!]';

                        array_push($values, $fone . $ftwo);
                    }
                    $recRequest[$flid] = '';
                    $recRequest[$flid . '_val'] = $values;
                } else if($type == 'Date') {
                    $recRequest['circa_' . $flid] = (string)$field->Circa;
                    $recRequest['month_' . $flid] = (string)$field->Month;
                    $recRequest['day_' . $flid] = (string)$field->Day;
                    $recRequest['year_' . $flid] = (string)$field->Year;
                    $recRequest['era_' . $flid] = (string)$field->Era;
                    $recRequest[$flid] = '';
                } else if($type == 'Schedule') {
                    $events = array();
                    foreach($field->Event as $event) {
                        $string = $event->Title . ': ' . $event->Begin . ' - ' . $event->End;
                        array_push($events, $string);
                    }
                    $recRequest[$flid] = $events;
                } else if($type == 'Geolocator') {
                    $geo = array();
                    foreach($field->Location as $loc) {
                        $string = '[Desc]' . $loc->Desc . '[Desc]';
                        $string .= '[LatLon]' . $loc->Lat . ',' . $loc->Lon . '[LatLon]';
                        $string .= '[UTM]' . $loc->Zone . ':' . $loc->East . ',' . $loc->North . '[UTM]';
                        $string .= '[Address]' . $loc->Address . '[Address]';
                        array_push($geo, $string);
                    }
                    $recRequest[$flid] = $geo;
                } else if($type == 'Documents' | $type == 'Playlist' | $type == 'Video' | $type == '3D-Model') {
                    $files = array();
                    if(is_null($originRid))
                        $currDir = config('app.base_path') . 'storage/app/tmpFiles/impU' . \Auth::user()->id;
                    else
                        $currDir = config('app.base_path') . 'storage/app/tmpFiles/impU' . \Auth::user()->id . '/r' . $originRid . '/fl' . $flid;
                    $newDir = config('app.base_path') . 'storage/app/tmpFiles/f' . $flid . 'u' . \Auth::user()->id;
                    if(file_exists($newDir)) {
                        foreach(new \DirectoryIterator($newDir) as $file) {
                            if($file->isFile()) {
                                unlink($newDir . '/' . $file->getFilename());
                            }
                        }
                    } else {
                        mkdir($newDir, 0775, true);
                    }
                    foreach($field->File as $file) {
                        $name = (string)$file->Name;
                        //move file from imp temp to tmp files
                        copy($currDir . '/' . $name, $newDir . '/' . $name);
                        //add input for this file
                        array_push($files, $name);
                    }
                    $recRequest['file' . $flid] = $files;
                    $recRequest[$flid] = 'f' . $flid . 'u' . \Auth::user()->id;
                } else if($type == 'Gallery') {
                    $files = array();
                    if(is_null($originRid))
                        $currDir = config('app.base_path') . 'storage/app/tmpFiles/impU' . \Auth::user()->id;
                    else
                        $currDir = config('app.base_path') . 'storage/app/tmpFiles/impU' . \Auth::user()->id . '/r' . $originRid . '/fl' . $flid;
                    $newDir = config('app.base_path') . 'storage/app/tmpFiles/f' . $flid . 'u' . \Auth::user()->id;
                    if(file_exists($newDir)) {
                        foreach(new \DirectoryIterator($newDir) as $file) {
                            if($file->isFile()) {
                                unlink($newDir . '/' . $file->getFilename());
                            }
                        }
                        if(file_exists($newDir . '/thumbnail')) {
                            foreach(new \DirectoryIterator($newDir . '/thumbnail') as $file) {
                                if($file->isFile()) {
                                    unlink($newDir . '/thumbnail/' . $file->getFilename());
                                }
                            }
                        }
                        if(file_exists($newDir . '/medium')) {
                            foreach(new \DirectoryIterator($newDir . '/medium') as $file) {
                                if($file->isFile()) {
                                    unlink($newDir . '/medium/' . $file->getFilename());
                                }
                            }
                        }
                    } else {
                        mkdir($newDir, 0775, true);
                        mkdir($newDir . '/thumbnail', 0775, true);
                        mkdir($newDir . '/medium', 0775, true);
                    }
                    foreach($field->File as $file) {
                        $name = (string)$file->Name;
                        //move file from imp temp to tmp files
                        copy($currDir . '/' . $name, $newDir . '/' . $name);
                        copy($currDir . '/thumbnail/' . $name, $newDir . '/thumbnail/' . $name);
                        copy($currDir . '/medium/' . $name, $newDir . '/medium/' . $name);
                        if(file_exists($currDir . '/thumbnail'))
                            copy($currDir . '/thumbnail/' . $name, $newDir . '/thumbnail/' . $name);
                        else {
                            $smallParts = explode('x',FieldController::getFieldOption($field,'ThumbSmall'));
                            $tImage = new \Imagick($newDir . '/' . $name);
                            $tImage->thumbnailImage($smallParts[0],$smallParts[1],true);
                            $tImage->writeImage($newDir . '/thumbnail/' . $name);
                        }
                        if(file_exists($currDir . '/medium'))
                            copy($currDir . '/medium/' . $name, $newDir . '/medium/' . $name);
                        else {
                            $largeParts = explode('x',FieldController::getFieldOption($field,'ThumbLarge'));
                            $mImage = new \Imagick($newDir . '/' . $name);
                            $mImage->thumbnailImage($largeParts[0],$largeParts[1],true);
                            $mImage->writeImage($newDir . '/medium/' . $name);
                        }
                        //add input for this file
                        array_push($files, $name);
                    }
                    $recRequest['file' . $flid] = $files;
                    $recRequest[$flid] = 'f' . $flid . 'u' . \Auth::user()->id;
                } else if($type == 'Associator') {
                    $recRequest[$flid] = (array)$field->Record;
                }
            }
        } else if($request->type==self::JSON) {
            $originKid = $request->kid;
            if(Record::isKIDPattern($originKid))
                $originRid = explode('-', $originKid)[2];
            else
                $originRid = null;

            foreach($record as $slug => $field) {
                $fieldSlug = $matchup[$slug];
                $flid = Field::where('slug', '=', $fieldSlug)->get()->first()->flid;
                $type = $field['type'];

                //Type wasnt provided so we have to hunt for it
                if(is_null($type))
                    $type = Field::where('slug', '=', $fieldSlug)->get()->first()->type;

                if($type == 'Text') {
                    $recRequest[$flid] = $field['value'];
                } else if($type == 'Rich Text') {
                    $recRequest[$flid] = $field['value'];
                } else if($type == 'Number') {
                    $recRequest[$flid] = $field['value'];
                } else if($type == 'List') {
                    $recRequest[$flid] = $field['value'];
                } else if($type == 'Multi-Select List') {
                    $recRequest[$flid] = $field['value'];
                } else if($type == 'Generated List') {
                    $recRequest[$flid] = $field['value'];
                } else if($type == 'Combo List') {
                    $values = array();
                    $nameone = ComboListField::getComboFieldName(FieldController::getField($flid), 'one');
                    $nametwo = ComboListField::getComboFieldName(FieldController::getField($flid), 'two');
                    foreach($field['value'] as $val) {
                        if(!is_array($val[$nameone]))
                            $fone = '[!f1!]' . $val[$nameone] . '[!f1!]';
                        else
                            $fone = '[!f1!]' . implode("[!]",$val[$nameone]) . '[!f1!]';


                        if(!is_array($val[$nametwo]))
                            $ftwo = '[!f2!]' . $val[$nametwo] . '[!f2!]';
                        else
                            $ftwo = '[!f2!]' . implode("[!]",$val[$nametwo]) . '[!f2!]';

                        array_push($values, $fone . $ftwo);
                    }
                    $recRequest[$flid] = '';
                    $recRequest[$flid . '_val'] = $values;
                } else if($type == 'Date') {
                    $recRequest['circa_' . $flid] = $field['value']['circa'];
                    $recRequest['month_' . $flid] = $field['value']['month'];
                    $recRequest['day_' . $flid] = $field['value']['day'];
                    $recRequest['year_' . $flid] = $field['value']['year'];
                    $recRequest['era_' . $flid] = $field['value']['era'];
                    $recRequest[$flid] = '';
                } else if($type == 'Schedule') {
                    $events = array();
                    foreach($field['value'] as $event) {
                        $string = $event['desc'] . ': ' . $event['begin'] . ' - ' . $event['end'];
                        array_push($events, $string);
                    }
                    $recRequest[$flid] = $events;
                } else if($type == 'Geolocator') {
                    $geo = array();
                    foreach($field['value'] as $loc) {
                        $string = '[Desc]' . $loc['desc'] . '[Desc]';
                        $string .= '[LatLon]' . $loc['lat'] . ',' . $loc['lon'] . '[LatLon]';
                        $string .= '[UTM]' . $loc['zone'] . ':' . $loc['east'] . ',' . $loc['north'] . '[UTM]';
                        $string .= '[Address]' . $loc['address'] . '[Address]';
                        array_push($geo, $string);
                    }
                    $recRequest[$flid] = $geo;
                } else if($type == 'Documents' | $type == 'Playlist' | $type == 'Video' | $type == '3D-Model') {
                    $files = array();
                    if(is_null($originRid))
                        $currDir = config('app.base_path') . 'storage/app/tmpFiles/impU' . \Auth::user()->id;
                    else
                        $currDir = config('app.base_path') . 'storage/app/tmpFiles/impU' . \Auth::user()->id . '/r' . $originRid . '/fl' . $flid;
                    $newDir = config('app.base_path') . 'storage/app/tmpFiles/f' . $flid . 'u' . \Auth::user()->id;
                    if(file_exists($newDir)) {
                        foreach(new \DirectoryIterator($newDir) as $file) {
                            if($file->isFile()) {
                                unlink($newDir . '/' . $file->getFilename());
                            }
                        }
                    } else {
                        mkdir($newDir, 0775, true);
                    }
                    foreach($field['value'] as $file) {
                        $name = $file['name'];
                        //move file from imp temp to tmp files
                        copy($currDir . '/' . $name, $newDir . '/' . $name);
                        //add input for this file
                        array_push($files, $name);
                    }
                    $recRequest['file' . $flid] = $files;
                    $recRequest[$flid] = 'f' . $flid . 'u' . \Auth::user()->id;
                } else if($type == 'Gallery') {
                    $files = array();
                    if(is_null($originRid))
                        $currDir = config('app.base_path') . 'storage/app/tmpFiles/impU' . \Auth::user()->id;
                    else
                        $currDir = config('app.base_path') . 'storage/app/tmpFiles/impU' . \Auth::user()->id . '/r' . $originRid . '/fl' . $flid;
                    $newDir = config('app.base_path') . 'storage/app/tmpFiles/f' . $flid . 'u' . \Auth::user()->id;
                    if(file_exists($newDir)) {
                        foreach(new \DirectoryIterator($newDir) as $file) {
                            if($file->isFile()) {
                                unlink($newDir . '/' . $file->getFilename());
                            }
                        }
                        if(file_exists($newDir . '/thumbnail')) {
                            foreach(new \DirectoryIterator($newDir . '/thumbnail') as $file) {
                                if($file->isFile()) {
                                    unlink($newDir . '/thumbnail/' . $file->getFilename());
                                }
                            }
                        }
                        if(file_exists($newDir . '/medium')) {
                            foreach(new \DirectoryIterator($newDir . '/medium') as $file) {
                                if($file->isFile()) {
                                    unlink($newDir . '/medium/' . $file->getFilename());
                                }
                            }
                        }
                    } else {
                        mkdir($newDir, 0775, true);
                        mkdir($newDir . '/thumbnail', 0775, true);
                        mkdir($newDir . '/medium', 0775, true);
                    }
                    foreach($field['value'] as $file) {
                        $name = $file['name'];
                        //move file from imp temp to tmp files
                        copy($currDir . '/' . $name, $newDir . '/' . $name);
                        copy($currDir . '/thumbnail/' . $name, $newDir . '/thumbnail/' . $name);
                        copy($currDir . '/medium/' . $name, $newDir . '/medium/' . $name);
                        if(file_exists($currDir . '/thumbnail'))
                            copy($currDir . '/thumbnail/' . $name, $newDir . '/thumbnail/' . $name);
                        else {
                            $smallParts = explode('x',FieldController::getFieldOption($field,'ThumbSmall'));
                            $tImage = new \Imagick($newDir . '/' . $name);
                            $tImage->thumbnailImage($smallParts[0],$smallParts[1],true);
                            $tImage->writeImage($newDir . '/thumbnail/' . $name);
                        }
                        if(file_exists($currDir . '/medium'))
                            copy($currDir . '/medium/' . $name, $newDir . '/medium/' . $name);
                        else {
                            $largeParts = explode('x',FieldController::getFieldOption($field,'ThumbLarge'));
                            $mImage = new \Imagick($newDir . '/' . $name);
                            $mImage->thumbnailImage($largeParts[0],$largeParts[1],true);
                            $mImage->writeImage($newDir . '/medium/' . $name);
                        }
                        //add input for this file
                        array_push($files, $name);
                    }
                    $recRequest['file' . $flid] = $files;
                    $recRequest[$flid] = 'f' . $flid . 'u' . \Auth::user()->id;
                } else if($type == 'Associator') {
                    $recRequest[$flid] = $field['value'];
                }
            }
        }

        $recCon = new RecordController();
        $recCon->store($pid,$fid,$recRequest);
    }

    /**
     * Import a k3Form file into Kora3.
     *
     * @param  int $pid - Project ID
     * @param  Request $request
     * @return Redirect
     */
	public function importForm($pid, Request $request) {
        $project = ProjectController::getProject($pid);

        if(!\Auth::user()->isProjectAdmin($project))
            return redirect('projects')->with('k3_global_error', 'not_project_admin');

        $file = $request->file('form');
        $fName = $request->name;
        $fSlug = $request->slug;
        $fDesc = $request->description;

        $fileArray = json_decode(file_get_contents($file));

        $form = new Form();

        if($fName == "")
            $form->name = $fileArray->name;
        else
            $form->name = $fName;

        if($fSlug == "")
            $finalSlug = $fileArray->slug;
        else
            $finalSlug = $fSlug;

        $form->pid = $project->pid;
        if(Form::where('slug', '=', $finalSlug)->exists()) {
            $unique = false;
            $i=1;
            while(!$unique) {
                if(Form::where('slug', '=', $finalSlug.$i)->exists()) {
                    $i++;
                } else {
                    $form->slug = $finalSlug.$i;
                    $unique = true;
                }
            }
        } else {
            $form->slug = $finalSlug;
        }

        if($fDesc == "")
            $form->description = $fileArray->desc;
        else
            $form->description = $fDesc;

        $form->preset = $fileArray->preset;
        $form->public_metadata = $fileArray->metadata;

        $form->save();

        //make admin group
        $admin = FormGroup::makeAdminGroup($form, $request);
        FormGroup::makeDefaultGroup($form);
        $form->adminGID = $admin->id;
        $form->save();

        //pages
        $pages = $fileArray->pages;
        $pConvert = array();

        foreach($pages as $page) {
            $p = new Page();

            $p->fid = $form->fid;
            $p->title = $page->title;
            $p->sequence = $page->sequence;

            $p->save();

            $pConvert[$page->id] = $p->id;
        }

        //record presets
        $recPresets = $fileArray->recPresets;

        foreach($recPresets as $pre) {
            $rec = new RecordPreset();

            $rec->fid = $form->fid;
            $rec->name = $pre->name;
            $rec->preset = $pre->preset;

            $rec->save();
        }

        $fields = $fileArray->fields;

        foreach($fields as $fieldArray) {
            $field = new Field();

            $field->pid = $project->pid;
            $field->fid = $form->fid;
            $field->page_id = $pConvert[$fieldArray->page_id];
            $field->sequence = $fieldArray->sequence;
            $field->type = $fieldArray->type;
            $field->name = $fieldArray->name;
            if(Field::where('slug', '=', $fieldArray->slug)->exists()) {
                $unique = false;
                $i=1;
                while(!$unique) {
                    if(Field::where('slug', '=', $fieldArray->slug.$i)->exists()) {
                        $i++;
                    } else {
                        $field->slug = $fieldArray->slug.$i;
                        $unique = true;
                    }
                }
            } else {
                $field->slug = $fieldArray->slug;
            }
            $field->desc = $fieldArray->desc;
            $field->required = $fieldArray->required;
            $field->searchable = $fieldArray->searchable;
            $field->advsearch = $fieldArray->advsearch;
            $field->extsearch = $fieldArray->extsearch;
            $field->viewable = $fieldArray->viewable;
            $field->viewresults = $fieldArray->viewresults;
            $field->extview = $fieldArray->extview;
            $field->default = $fieldArray->default;
            $field->options = $fieldArray->options;

            $field->save();

            //metadata
            if($fieldArray->metadata!="") {
                $meta = new Metadata();
                $meta->flid = $field->flid;
                $meta->pid = $project->pid;
                $meta->fid = $form->fid;
                $meta->name = $fieldArray->metadata;
                $meta->save();
            }
        }

        flash()->overlay("Your form has been successfully created!","Good job!");

        return redirect('projects/'.$form->pid);
    }

    /**
     * Import a Kora 2 scheme into Kora3.
     *
     * @param  int $pid  - Project ID
     * @param  Request $request
     * @return Redirect
     */
    public function importFormK2($pid, Request $request) {
        $project = ProjectController::getProject($pid);

        if(!\Auth::user()->isProjectAdmin($project))
            return redirect('projects')->with('k3_global_error', 'not_project_admin');

        $file = $request->file('form');
        $scheme = simplexml_load_file($file);
        $collToPage = array();
        $fieldNameArrayForRecordInsert = array();

        $fName = $request->name;
        $fSlug = $request->slug;
        $fDesc = $request->description;

        //init form
        $form = new Form();

        $form->pid = $pid;
        $form->preset = 0;
        $form->public_metadata = 0;
        $form->save();

        $admin = FormGroup::makeAdminGroup($form, $request);
        FormGroup::makeDefaultGroup($form);
        $form->adminGID = $admin->id;
        $form->save();

        //do stuff
        foreach($scheme->children() as $category => $value) {
            if($category=='SchemeDesc') {
                $name = $value->Name->__toString();
                if($fName != "")
                    $name = $fName;
                $desc = $value->Description->__toString();
                if($fDesc != "")
                    $desc = $fDesc;

                $form->name = $name;
                $slug = str_replace(' ','_',$name);
                if($fSlug != "")
                    $slug = $fSlug;
                $z=1;
                while(Form::slugExists($slug)) {
                    $slug .= $z;
                    $z++;
                }
                $form->slug = $slug;
                $form->description = $desc;
                $form->save();
            } else if($category=='Collections') {
                $pIndex = 0;
                foreach($value->children() as $collection) {
                    $page = new Page();
                    $page->fid = $form->fid;
                    $page->title = $collection->Name->__toString();
                    $page->sequence = $pIndex;
                    $pIndex++;

                    $page->save();

                    $collToPage[(int)$collection->id] = $page->id;
                    //Each page needs to keep track of its own sequence for fields
                    $collToPage[(int)$collection->id."_seq"] = 0;
                }
            } else if($category=='Controls') {
                foreach($value->children() as $name => $control) {
                    if($name != 'systimestamp' && $name != 'recordowner') {
                        $type = $control->Type->__toString();
                        $collid = (int)$control->CollId;
                        $desc = $control->Description->__toString();
                        $req = (int)$control->Required;
                        $search = (int)$control->Searchable;
                        $advsearch = (int)$control->advSearchable;
                        $showresults = (int)$control->showInResults;
                        $options = $control->options->__toString();
                        $optXML = simplexml_load_string($options);
                        $newOpts = '';
                        $newDef = '';
                        $newType = '';

                        switch($type) {
                            case 'TextControl':
                                $def = $optXML->defaultValue->__toString();
                                $textType = $optXML->textEditor->__toString();
                                if($textType=='plain') {
                                    $regex = $optXML->regex->__toString();
                                    $rows = (int)$optXML->rows;
                                    $multiline = 0;
                                    if($rows>1)
                                        $multiline = 1;

                                    $newOpts = "[!Regex!]".$regex."[!Regex!][!MultiLine!]".$multiline."[!MultiLine!]";
                                    $newDef = $def;
                                    $newType = "Text";
                                } else if($textType=='rich') {
                                    $newOpts = "";
                                    $newDef = $def;
                                    $newType = "Rich Text";
                                }
                                break;
                            case 'MultiTextControl':
                                $def = (array)$optXML->defaultValue->value;
                                $defOpts = '';
                                if(isset($def[0])) {
                                    $defOpts = implode("[!]",$def);
                                }
                                $regex = $optXML->regex->__toString();

                                $newOpts = "[!Regex!]".$regex."[!Regex!][!Options!]".$defOpts."[!Options!]";
                                $newDef = $defOpts;
                                $newType = "Generated List";
                                break;
                            case 'DateControl':
                                $startY = (int)$optXML->startYear;
                                $endY = (int)$optXML->endYear;
                                $era = $optXML->era->__toString();
                                $format = $optXML->displayFormat->__toString();
                                $defYear = (int)$optXML->defaultValue->year;
                                $defMon = (int)$optXML->defaultValue->month;
                                $defDay = (int)$optXML->defaultValue->day;
                                $prefix = $optXML->prefixes->__toString();
                                $circa = 'No';
                                $for = 'MMDDYYYY';
                                if($prefix=="circa") {$circa="Yes";}
                                if($format=="MDY") {$for="MMDDYYYY";}
                                else if($format=="DMY") {$for="DDMMYYYY";}
                                else if($format=="YMD") {$for="YYYYMMDD";}

                                $newOpts = "[!Circa!]".$circa."[!Circa!][!Start!]".$startY."[!Start!][!End!]".$endY."[!End!][!Format!]".$for."[!Format!][!Era!]".$era."[!Era!]";
                                $newDef = "[M]".$defMon."[M][D]".$defDay."[D][Y]".$defYear."[Y]";
                                $newType = "Date";
                                break;
                            case 'MultiDateControl':
                                $startY = (int)$optXML->startYear;
                                $endY = (int)$optXML->endYear;
                                $def = (array)$optXML->defaultValue;
                                if(isset($def["date"]))
                                    $def = $def["date"];
                                else
                                    $def=array();
                                $defOpts = '';
                                if(isset($def[0])) {
                                    $defOpts = "Event 1: " . $def[0]->month . "/" . $def[0]->day . "/" . $def[0]->year . " - " . $def[0]->month . "/" . $def[0]->day . "/" . $def[0]->year;
                                    for($i = 1; $i < sizeof($def); $i++) {
                                        $defOpts .= '[!]' . "Event " . ($i + 1) . ": " . $def[$i]->month . "/" . $def[$i]->day . "/" . $def[$i]->year . " - " . $def[$i]->month . "/" . $def[$i]->day . "/" . $def[$i]->year;
                                    }
                                }

                                $newOpts = "[!Start!]".$startY."[!Start!][!End!]".$endY."[!End!][!Calendar!]No[!Calendar!]";
                                $newDef = $defOpts;
                                $newType = "Schedule";
                                break;
                            case 'FileControl':
                                $maxSize = (int)$optXML->maxSize;
                                $allowed = (array)$optXML->allowedMIME->mime;
                                $allOpts = '';
                                if(isset($allowed[0])) {
                                    $allOpts = implode("[!]",$allowed);
                                }

                                $newOpts = "[!FieldSize!]".$maxSize."[!FieldSize!][!MaxFiles!]0[!MaxFiles!][!FileTypes!]".$allOpts."[!FileTypes!]";
                                $newType = "Documents";
                                break;
                            case 'ImageControl':
                                $maxSize = (int)$optXML->maxSize;
                                $allowed = (array)$optXML->allowedMIME->mime;
                                $allOpts = '';
                                if(isset($allowed[0])) {
                                    $allOpts = $allowed[0];
                                    for($i = 1; $i < sizeof($allowed); $i++) {
                                        if ($allowed[$i] != "image/pjpeg" && $allowed[$i] != "image/x-png")
                                            $allOpts .= '[!]' . $allowed[$i];
                                    }
                                }
                                $thumbW = (int)$optXML->thumbWidth;
                                $thumbH = (int)$optXML->thumbHeight;

                                $newOpts = "[!FieldSize!]".$maxSize."[!FieldSize!][!ThumbSmall!]".$thumbW."x".$thumbH."[!ThumbSmall!][!ThumbLarge!]".($thumbW*2)."x".($thumbH*2)."[!ThumbLarge!][!MaxFiles!]0[!MaxFiles!][!FileTypes!]".$allOpts."[!FileTypes!]";
                                $newType = "Gallery";
                                break;
                            case 'ListControl':
                                $opts = (array)$optXML->option;
                                $allOpts = '';
                                if(isset($opts[0])) {
                                    $allOpts = implode("[!]",$opts);
                                }
                                $def = $optXML->defaultValue->__toString();

                                $newOpts = "[!Options!]".$allOpts."[!Options!]";
                                $newDef = $def;
                                $newType = "List";
                                break;
                            case 'MultiListControl':
                                $opts = (array)$optXML->option;
                                $allOpts = '';
                                if(isset($opts[0])) {
                                    $allOpts = implode("[!]",$opts);
                                }
                                $def = (array)$optXML->defaultValue->option;
                                $defOpts = '';
                                if(isset($def[0])) {
                                    $defOpts = implode("[!]",$def);
                                }

                                $newOpts = "[!Options!]".$allOpts."[!Options!]";
                                $newDef = $defOpts;
                                $newType = "Multi-Select List";
                                break;
                            case 'AssociatorControl':
                                $newOpts = "[!SearchForms!][!SearchForms!]";
                                $newType = "Associator";
                                break;
                        }

                        //save it
                        $field = new Field();
                        $field->pid = $form->pid;
                        $field->fid = $form->fid;
                        $field->page_id = $collToPage[$collid];
                        $field->sequence = $collToPage[$collid."_seq"];
                        $collToPage[$collid."_seq"] += 1;
                        $field->type = $newType;
                        $field->name = $name;
                        $slug = str_replace(' ','_',$name).'_'.$form->pid.'_'.$form->fid.'_';
                        $field->slug = $slug;
                        $fieldNameArrayForRecordInsert[$name] = $slug;
                        $field->desc = $desc;
                        $field->required = $req;
                        $field->searchable = $search;
                        $field->advsearch = $advsearch;
                        $field->extsearch = $search;
                        $field->viewable = $showresults;
                        $field->viewresults = $showresults;
                        $field->extview = $showresults;
                        $field->default = $newDef;
                        $field->options = $newOpts;
                        $field->save();
                    }
                }
            }
        }

        //NOW WE LOOK FOR RECORDS
        if(!is_null($request->file('records'))) {
            $file = $request->file('records');
            $records = simplexml_load_file($file);
            $zipDir = config('app.base_path').'storage/app/tmpFiles/f'.$form->fid.'u'.\Auth::user()->id.'/';
            $filesProvided = false;

            if(!is_null($request->file('files'))) {
                $filesProvided = true;
                $fileZIP = $request->file('files');

                $zip = new \ZipArchive();
                if($zip->open($fileZIP) === TRUE) {
                    if(mkdir($zipDir)) {
                        $zip->extractTo($zipDir);
                        $zip->close();
                    }
                }
            }

            foreach($records->Record as $record) {
                $recModel = new Record();
                $recModel->pid = $form->pid;
                $recModel->fid = $form->fid;
                $recModel->owner = \Auth::user()->id;
                $recModel->save();

                $recModel->kid = $recModel->pid."-".$recModel->fid."-".$recModel->rid;
                $recModel->save();

                $usedMultiples = array();

                foreach($record->children() as $name => $value) {
                    //for multi style controls, move on if name already user
                    if(in_array($name,$usedMultiples)) {continue;}
                    //ignore standard control types and process
                    if($name != 'systimestamp' && $name != 'recordowner') {
                        $slug = $fieldNameArrayForRecordInsert[$name];
                        $field = Field::where('slug','=',$slug)->get()->first();

                        //We leave this code here (instead of in the Field model) because they are heavily specific to
                        // the conversion of Kora 2 data and will probably never change.
                        //TODO::modular?
                        switch($field->type) {
                            case 'Text':
                                $value = (string)$value;

                                if($value!="") {
                                    $text = new TextField();
                                    $text->rid = $recModel->rid;
                                    $text->fid = $recModel->fid;
                                    $text->flid = $field->flid;
                                    $text->text = $value;
                                    $text->save();
                                }
                                break;
                            case 'Rich Text':
                                $value = (string)$value;

                                if($value!="") {
                                    $rich = new RichTextField();
                                    $rich->rid = $recModel->rid;
                                    $rich->fid = $recModel->fid;
                                    $rich->flid = $field->flid;
                                    $rich->rawtext = $value;
                                    $rich->save();
                                }
                                break;
                            case 'Generated List':
                                array_push($usedMultiples,$name);
                                $opts = (array)$record->$name;
                                if(isset($opts[0])) {
                                    $optStr = implode("[!]",$opts);

                                    $gen = new GeneratedListField();
                                    $gen->rid = $recModel->rid;
                                    $gen->fid = $recModel->fid;
                                    $gen->flid = $field->flid;
                                    $gen->options = $optStr;
                                    $gen->save();
                                }
                                break;
                            case 'Date':
                                $circa=0;
                                if(isset($value->attributes()["prefix"])) {
                                    if($value->attributes()["prefix"] == "circa") {
                                        $circa=1;
                                    }
                                }
                                $dateStr = (string)$value;
                                if($dateStr!="") {
                                    $dateArray = explode(' ',$dateStr);
                                    if(FieldController::getFieldOption($field,'Era')=='Yes')
                                        $era = $dateArray[1];
                                    else
                                        $era = 'CE';
                                    $dateParts = explode("/",$dateArray[0]);

                                    $date = new DateField();
                                    $date->rid = $recModel->rid;
                                    $date->fid = $recModel->fid;
                                    $date->flid = $field->flid;
                                    $date->circa = $circa;
                                    $date->month = $dateParts[0];
                                    $date->day = $dateParts[1];
                                    $date->year = $dateParts[2];
                                    $date->era = $era;
                                    $date->save();
                                }
                                break;
                            case 'Schedule':
                                array_push($usedMultiples,$name);
                                $opts = (array)$record->$name;
                                if(isset($opts[0])) {
                                    //CREATE THE VALUE
                                    $z=1;
                                    $dateStr = explode(' ',$opts[0])[0];
                                    $eventStr = 'Event '.$z.': '.$dateStr.' - '.$dateStr;
                                    $z++;
                                    for($i = 1; $i < sizeof($opts); $i++) {
                                        $dateStr = explode(' ',$opts[$i])[0];
                                        $eventStr .= '[!]Event '.$z.': '.$dateStr.' - '.$dateStr;
                                        $z++;
                                    }

                                    $sched = new ScheduleField();
                                    $sched->rid = $recModel->rid;
                                    $sched->fid = $recModel->fid;
                                    $sched->flid = $field->flid;
                                    $sched->save();

                                    $sched->addEvents(explode("[!]", $eventStr));
                                }
                                break;
                            case 'Documents':
                                //If the user didn't provide files, bounce
                                if(!$filesProvided)
                                    break;

                                $realname='';
                                if(isset($value->attributes()["originalName"]))
                                    $realname = $value->attributes()["originalName"];
                                $localname = (string)$value;

                                if($localname!='') {
                                    $docs = new DocumentsField();
                                    $docs->rid = $recModel->rid;
                                    $docs->fid = $recModel->fid;
                                    $docs->flid = $field->flid;

                                    //Make folder
                                    $newPath = config('app.base_path') . 'storage/app/files/p' . $form->pid . '/f' . $form->fid . '/r' . $recModel->rid . '/fl' . $field->flid.'/';
                                    mkdir($newPath, 0775, true);

                                    //Move file
                                    rename($zipDir.$localname,$newPath.$realname);

                                    //Get file info
                                    $mimes = FileTypeField::getMimeTypes();
                                    $ext = pathinfo($newPath.$realname,PATHINFO_EXTENSION);
                                    if(!array_key_exists($ext, $mimes))
                                        $type = 'application/octet-stream';
                                    else
                                        $type = $mimes[$ext];

                                    $name = '[Name]'.$realname.'[Name]';
                                    $size = '[Size]'.filesize($newPath.$realname).'[Size]';
                                    $typeS = '[Type]'.$type.'[Type]';
                                    //Build file string
                                    $info = $name.$size.$typeS;
                                    $docs->documents = $info;
                                    $docs->save();
                                }
                                break;
                            case 'Gallery':
                                //If the user didn't provide files, bounce
                                if(!$filesProvided)
                                    break;

                                $realname='';
                                if(isset($value->attributes()["originalName"]))
                                    $realname = $value->attributes()["originalName"];
                                $localname = (string)$value;

                                if($localname!='') {
                                    $gal = new GalleryField();
                                    $gal->rid = $recModel->rid;
                                    $gal->fid = $recModel->fid;
                                    $gal->flid = $field->flid;

                                    //Make folder
                                    $newPath = config('app.base_path') . 'storage/app/files/p' . $form->pid . '/f' . $form->fid . '/r' . $recModel->rid . '/fl' . $field->flid.'/';
                                    $newPathM = $newPath.'medium/';
                                    $newPathT = $newPath.'thumbnail/';
                                    mkdir($newPath, 0775, true);
                                    mkdir($newPathM, 0775, true);
                                    mkdir($newPathT, 0775, true);

                                    //Move files
                                    rename($zipDir.$localname,$newPath.$realname);

                                    //Create thumbs
                                    $smallParts = explode('x',FieldController::getFieldOption($field,'ThumbSmall'));
                                    $largeParts = explode('x',FieldController::getFieldOption($field,'ThumbLarge'));
                                    $tImage = new \Imagick($newPath.$realname);
                                    $mImage = new \Imagick($newPath.$realname);
                                    $tImage->thumbnailImage($smallParts[0],$smallParts[1],true);
                                    $mImage->thumbnailImage($largeParts[0],$largeParts[1],true);
                                    $tImage->writeImage($newPathT.$realname);
                                    $mImage->writeImage($newPathM.$realname);

                                    //Get file info
                                    $mimes = FileTypeField::getMimeTypes();
                                    $ext = pathinfo($newPath.$realname,PATHINFO_EXTENSION);
                                    if(!array_key_exists($ext, $mimes))
                                        $type = 'application/octet-stream';
                                    else
                                        $type = $mimes[$ext];

                                    $name = '[Name]'.$realname.'[Name]';
                                    $size = '[Size]'.filesize($newPath.$realname).'[Size]';
                                    $typeS = '[Type]'.$type.'[Type]';
                                    //Build file string
                                    $info = $name.$size.$typeS;
                                    $gal->images = $info;
                                    $gal->save();
                                }
                                break;
                            case 'List':
                                $value = (string)$value;

                                if($value!="") {
                                    $list = new ListField();
                                    $list->rid = $recModel->rid;
                                    $list->fid = $recModel->fid;
                                    $list->flid = $field->flid;
                                    $list->option = $value;
                                    $list->save();
                                }
                                break;
                            case 'Multi-Select List':
                                array_push($usedMultiples,$name);
                                $opts = (array)$record->$name;
                                if(isset($opts[0])) {
                                    $optStr = implode("[!]",$opts);

                                    $msl = new MultiSelectListField();
                                    $msl->rid = $recModel->rid;
                                    $msl->fid = $recModel->fid;
                                    $msl->flid = $field->flid;
                                    $msl->options = $optStr;
                                    $msl->save();
                                }
                                break;
                        }
                    }
                }
            }

            //clean tmp folder
            if(file_exists($zipDir))
                rmdir($zipDir);
        }

        return redirect('projects/'.$pid)->with('k3_global_success', 'form_created');
    }

    /**
     * Project import uses this to import its forms without the need for a k3Form file.
     *
     * @param  int $pid - Project ID
     * @param  array $fileArray - Form structure info
     */
    public function importFormNoFile($pid, $fileArray) {
        $project = ProjectController::getProject($pid);

        $form = new Form();

        $form->pid = $project->pid;
        $form->name = $fileArray->name;
        if(Form::where('slug', '=', $fileArray->slug)->exists()) {
            $unique = false;
            $i=1;
            while(!$unique) {
                if(Form::where('slug', '=', $fileArray->slug.$i)->exists()) {
                    $i++;
                } else {
                    $form->slug = $fileArray->slug.$i;
                    $unique = true;
                }
            }
        } else {
            $form->slug = $fileArray->slug;
        }
        $form->description = $fileArray->desc;
        $form->preset = $fileArray->preset;
        $form->public_metadata = $fileArray->metadata;

        $form->save();

        //make admin group
        $admin = FormGroup::makeAdminGroup($form);
        FormGroup::makeDefaultGroup($form);
        $form->adminGID = $admin->id;
        $form->save();

        //pages
        $pages = $fileArray->pages;
        $pConvert = array();

        foreach($pages as $page) {
            $p = new Page();

            $p->fid = $form->fid;
            $p->title = $page->title;
            $p->sequence = $page->sequence;

            $p->save();

            $pConvert[$page->id] = $p->id;
        }

        //record presets
        $recPresets = $fileArray->recPresets;

        foreach($recPresets as $pre) {
            $rec = new RecordPreset();

            $rec->fid = $form->fid;
            $rec->name = $pre->name;
            $rec->preset = $pre->preset;

            $rec->save();
        }

        $fields = $fileArray->fields;

        foreach($fields as $fieldArray) {
            $field = new Field();

            $field->pid = $project->pid;
            $field->fid = $form->fid;
            $field->page_id = $pConvert[$fieldArray->page_id];
            $field->sequence = $fieldArray->sequence;
            $field->type = $fieldArray->type;
            $field->name = $fieldArray->name;
            if(Field::where('slug', '=', $fieldArray->slug)->exists()) {
                $unique = false;
                $i=1;
                while(!$unique) {
                    if(Field::where('slug', '=', $fieldArray->slug.$i)->exists()) {
                        $i++;
                    } else {
                        $field->slug = $fieldArray->slug.$i;
                        $unique = true;
                    }
                }
            } else {
                $field->slug = $fieldArray->slug;
            }
            $field->desc = $fieldArray->desc;
            $field->required = $fieldArray->required;
            $field->searchable = $fieldArray->searchable;
            $field->advsearch = $fieldArray->advsearch;
            $field->extsearch = $fieldArray->extsearch;
            $field->viewable = $fieldArray->viewable;
            $field->viewresults = $fieldArray->viewresults;
            $field->extview = $fieldArray->extview;
            $field->default = $fieldArray->default;
            $field->options = $fieldArray->options;

            $field->save();

            //metadata
            if($fieldArray->metadata!="") {
                $meta = new Metadata();
                $meta->flid = $field->flid;
                $meta->pid = $project->pid;
                $meta->fid = $form->fid;
                $meta->name = $fieldArray->metadata;
                $meta->save();
            }
        }
    }



    /**
     * Import a k3Proj file into Kora3.
     *
     * @param  Request $request
     * @return Redirect
     */
    public function importProject(Request $request) {
        if(!\Auth::user()->admin)
            return redirect('projects/')->with('k3_global_error', 'not_admin');

        $file = $request->file('project');
        $pName = $request->name;
        $pSlug = $request->slug;
        $pDesc = $request->description;

        $fileArray = json_decode(file_get_contents($file));

        $proj = new Project();

        if($pName == "")
            $proj->name = $fileArray->name;
        else
            $proj->name = $pName;

        if($pSlug == "")
            $finalSlug = $fileArray->slug;
        else
            $finalSlug = $pSlug;

        if(Project::where('slug', '=', $finalSlug)->exists()) {
            $unique = false;
            $i=1;
            while(!$unique) {
                if(Project::where('slug', '=', $finalSlug.$i)->exists()) {
                    $i++;
                } else {
                    $proj->slug = $finalSlug.$i;
                    $unique = true;
                }
            }
        } else {
            $proj->slug = $finalSlug;
        }

        if($pDesc == "")
            $proj->description = $fileArray->description;
        else
            $proj->description = $pDesc;

        $proj->active = 1;

        $proj->save();

        //make admin group
        $admin = ProjectGroup::makeAdminGroup($proj, $request);
        ProjectGroup::makeDefaultGroup($proj);
        $proj->adminGID = $admin->id;
        $proj->save();

        $optPresets = $fileArray->optPresets;

        foreach($optPresets as $opt) {
            $pre = new OptionPreset();

            $pre->pid = $proj->pid;
            $pre->type = $opt->type;
            $pre->name = $opt->name;
            $pre->preset = $opt->preset;
            $pre->shared = $opt->shared;

            $pre->save();
        }

        $forms = $fileArray->forms;

        foreach($forms as $form) {
            $this->importFormNoFile($proj->pid,$form);
        }

        return redirect('projects')->with('k3_global_success', 'project_created');
    }
}
