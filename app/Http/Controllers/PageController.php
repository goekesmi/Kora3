<?php namespace App\Http\Controllers;

use App\Field;
use App\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller {

    /*
    |--------------------------------------------------------------------------
    | Page Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the page layout of forms
    |
    */

    /**
     * @var int - The type of page modification to perform
     */
    const _UP = 0;
    const _DOWN = 1;
    const _DELETE = 2;
    const _ADD = 3;
    const _RENAME = 4;

    /**
     * Constructs controller and makes sure user is authenticated.
     */
    public function __construct() {
        $this->middleware('auth');
        $this->middleware('active');
    }

    /**
     * Creates a page on the form.
     *
     * @param  int $fid - Form ID
     * @param  string $name - Name of page
     * @param  bool $resize - Determines if we need to reindex pages
     * @param  int $resizeIndex - What index new page will take
     */
    public static function makePageOnForm($fid,$name,$resize=false,$resizeIndex=0) {
        $page = new Page();

        $page->title = $name;
        $page->fid = $fid;

        $form = FormController::getForm($fid);
        $currPages = $form->pages()->get();

        //In this case, we are placing a page in between pages
        if($resize) {
            $found = false;
            foreach($currPages as $cPage) {
                if($found) {
                    //Once we've found the page we are placing after, we need to change the sequence of any
                    // pages that follow.
                    $cPage->sequence += 1;
                    $cPage->save();
                }

                if($cPage->id == $resizeIndex)
                    $page->sequence = $cPage->sequence + 1;
            }
        } else { //Here we just add it to the end
            $page->sequence = $currPages->count();
        }

        $page->save();
    }

    /**
     * Gets the layout sequence of the form.
     *
     * @param  int $fid - Form ID
     * @return array - The layout structure
     */
    public static function getFormLayout($fid) {
        $form = FormController::getForm($fid);

        $pages = $form->pages()->get();
        $layout = array();

        foreach($pages as $page) {
            $pArr = array();

            $pArr["fields"] = $page->fields()->get();

            $pArr["title"] = $page->title;
            $pArr["id"] = $page->id;
            $seq = $page->sequence;

            $layout[$seq] = $pArr;
        }

        return $layout;
    }

    /**
     * Gets an reindexes all the fields in a page.
     *
     * @param  int $pageID - Page ID
     */
    public static function restructurePageSequence($pageID) {
        $page = self::getPage($pageID);

        $fields = $page->fields()->get();
        $index = 0;

        foreach($fields as $field) {
            $field->sequence = $index;
            $field->save();
            $index++;
        }
    }

    /**
     * Gets a particular page model.
     *
     * @param  int $pageID - Page ID
     * @return Page - The requested page
     */
    public static function getPage($pageID) {
        $page = Page::where('id','=',$pageID)->first();

        return $page;
    }

    /**
     * Gets the next field sequence value for a particular page.
     *
     * @param  int $pageID - Page ID
     * @return int - Sequence value
     */
    public static function getNewPageFieldSequence($pageID) {
        $page = self::getPage($pageID);

        $lField = $page->fields()->get()->last();

        if(is_null($lField))
            return 0;
        else
            return $lField->sequence+1;
    }

    /**
     * Modify a form by adding, removing, and moving pages.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  Request $request
     * @return JsonResponse
     */
    public function modifyFormPage($pid, $fid, Request $request) {
        if(!FieldController::checkPermissions($fid, 'edit'))
            return response()->json(["status"=>false,"message"=>"cant_edit_field"],500);

        $method = $request->method;
        $form = FormController::getForm($fid);
        $pages = $form->pages()->get();

        switch($method) {
            case self::_UP:
                $id = $request->pageID;
                $page = self::getPage($id);
                $currSeq = $page->sequence;

                if($currSeq != 0) {
                    $aPage = Page::where('sequence','=',$currSeq-1)->where('fid','=',$fid)->get()->first();

                    $page->sequence = $currSeq-1;
                    $aPage->sequence = $currSeq;

                    $page->save();
                    $aPage->save();
                }

                break;
            case self::_DOWN:
                $id = $request->pageID;
                $page = self::getPage($id);
                $currSeq = $page->sequence;

                if($currSeq != ($pages->count()-1)) {
                    $aPage = Page::where('sequence','=',$currSeq+1)->where('fid','=',$fid)->get()->first();

                    $page->sequence = $currSeq+1;
                    $aPage->sequence = $currSeq;

                    $page->save();
                    $aPage->save();
                }

                break;
            case self::_DELETE:
                $id = $request->pageID;

                $found = false;
                $delPage = null;
                foreach($pages as $page) {
                    if($found) {
                        //Once we've found the page we are deleting, we need to change the sequence of any
                        // pages that follow.
                        $page->sequence -= 1;
                        $page->save();
                    }

                    if($page->id == $id) {
                        $found = true;
                        $delPage = $page;
                        $page->sequence = 1337;
                        $page->save();
                    }
                }

                if(!is_null($delPage))
                    $delPage->delete();
                break;
            case self::_ADD:
                $name = $request->newPageName;
                if($name=='')
                    response()->json(["status"=>false,"message"=>"page_name_required"],500);
                $aboveID = $request->aboveID;

                $found = false;
                foreach($pages as $page) {
                    if($found) {
                        //Once we've found the page we are placing after, we need to change the sequence of any
                        // pages that follow.
                        $page->sequence += 1;
                        $page->save();
                    }

                    if($page->id == $aboveID) {
                        $found = true;
                        $nPage = new Page();

                        $nPage->title = $name;
                        $nPage->fid = $fid;
                        $nPage->sequence = $page->sequence + 1;

                        $nPage->save();
                    }
                }
                break;
            case self::_RENAME:
                $id = $request->pageID;
                $name = $request->updatedName;
                $page = self::getPage($id);

                $page->title = $name;
                $page->save();
                break;
            default:
                return response()->json(["status"=>false,"message"=>"illegal_page_method"],500);
                break;
        }

        return response()->json(["status"=>true,"message"=>"page_layout_modified"],200);
    }

    /**
     * Move a field up and down within a page. If at top or bottom, field will move to next page.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  int $flid - Field ID
     * @param  Request $request
     * @return JsonResponse
     */
    public function moveField($pid,$fid,$flid,Request $request) {
        if(!FieldController::checkPermissions($fid, 'edit'))
            return response()->json(["status"=>false,"message"=>"cant_edit_field"],500);

        $direction = $request->direction;
        $field = FieldController::getField($flid);
        $seq = $field->sequence;

        $page = self::getPage($field->page_id);
        $fieldsInPage = Field::where("page_id","=",$page->id)->max("sequence");

        //We will need to see if we can move to a new page so we wan
        $form = FormController::getForm($fid);
        $numPagesInForm = $form->pages()->count();

        switch($direction) {
            case self::_UP:
                if($seq == 0) {
                    //We need to move to a new page potentially
                    $pageSeq = $page->sequence;
                    if($pageSeq==0) {
                        return response()->json(["status"=>false,"message"=>"no_page_above"],500);
                    } else {
                        $nPage = Page::where('sequence','=',$pageSeq-1)->where('fid','=',$fid)->first();
                        $field->page_id = $nPage->id;
                        $field->sequence = self::getNewPageFieldSequence($nPage->id);
                        $field->save();

                        //get fields from old page ordered by sequence
                        $oldFields = $page->fields()->get();
                        $index = 0;
                        foreach($oldFields as $f) {
                            $f->sequence = $index;
                            $f->save();
                            $index++;
                        }
                    }
                } else {
                    //Move it on up
                    $aFieldSeq = $seq-1;
                    $aField = Field::where('sequence','=',$aFieldSeq)->where('page_id','=',$field->page_id)->first();

                    $field->sequence = $seq-1;
                    $aField->sequence = $seq;
                    $field->save();
                    $aField->save();
                }
                break;
            case self::_DOWN:
                if($seq == $fieldsInPage) {
                    //We need to move to a new page potentially
                    $pageSeq = $page->sequence;
                    $maxPageSeq = Page::where("fid","=",$fid)->max("sequence");;
                    if($pageSeq==$maxPageSeq) {
                        return response()->json(["status"=>false,"message"=>"no_page_below"],500);
                    } else {
                        $nPage = Page::where('sequence','=',$pageSeq+1)->where('fid','=',$fid)->first();
                        $field->page_id = $nPage->id;
                        $field->sequence = self::getNewPageFieldSequence($nPage->id);
                        $field->save();

                        //get fields from old page ordered by sequence
                        $oldFields = $page->fields()->get();
                        $index = 0;
                        foreach($oldFields as $f) {
                            $f->sequence = $index;
                            $f->save();
                            $index++;
                        }
                    }
                } else {
                    //Move it on down
                    $aFieldSeq = $seq+1;
                    $aField = Field::where('sequence','=',$aFieldSeq)->where('page_id','=',$field->page_id)->first();

                    $field->sequence = $seq+1;
                    $aField->sequence = $seq;
                    $field->save();
                    $aField->save();
                }
                break;
            default:
                break;
        }

        return response()->json(["status"=>true,"message"=>"page_moved"],200);
    }

    /**
     * Save entire form layout via array.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  Request $request
     * @return JsonResponse
     */
    public function saveFullFormLayout($pid, $fid, Request $request) {
        if(!FieldController::checkPermissions($fid, 'edit'))
            return response()->json(["status"=>false,"message"=>"cant_edit_field"],500);

        $formLayout = $request->layout;
        $pSeq = 0;

        foreach($formLayout as $pageID => $fields) {
            $page = Page::where('id',$pageID)->first();
            $page->sequence = $pSeq;
            $page->save();
            $pSeq++;

            $fSeq = 0;
            foreach($fields as $flid) {
                $field = FieldController::getField($flid);
                $field->page_id = $pageID;
                $field->sequence = $fSeq;
                $field->save();
                $fSeq++;
            }
        }

        return response()->json(["status"=>true,"message"=>"form_layout_saved"],200);
    }
}
