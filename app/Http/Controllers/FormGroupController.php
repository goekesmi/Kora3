<?php namespace App\Http\Controllers;

use App\Form;
use App\ProjectGroup;
use App\User;
use App\FormGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class FormGroupController extends Controller {

    /*
    |--------------------------------------------------------------------------
    | Form Group Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles groups that manage user permissions for forms.
    |
    */

    /**
     * Constructs controller and makes sure user is authenticated.
     */
    public function __construct() {
        $this->middleware('auth');
        $this->middleware('active');
    }

    /**
     * Gets the view for managing form groups.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @return View
     */
    public function index($pid, $fid) {
        if(!FormController::validProjForm($pid, $fid))
            return redirect('projects/'.$pid)->with('k3_global_error', 'form_invalid');

        $form = FormController::getForm($fid);
        $project = $form->project()->first();

        if(!(\Auth::user()->isFormAdmin($form)))
            return redirect('projects/'.$pid)->with('k3_global_error', 'not_form_admin');

        $formGroups = $form->groups()->get();
        $users = User::pluck('username', 'id')->all();
        $all_users = User::all();
        return view('formGroups.index', compact('form', 'formGroups', 'users', 'all_users', 'project'));
    }

    /**
     * Creates a new form group.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  Request $request
     * @return Redirect
     */
    public function create($pid, $fid, Request $request) {
        if(!FormController::validProjForm($pid, $fid))
            return redirect('projects/'.$pid)->with('k3_global_error', 'form_invalid');

        $form = FormController::getForm($fid);

        if($request->name == "") {
            return redirect(action('FormGroupController@index', ['fid'=>$form->fid]))->with('k3_global_error', 'form_group_noname');
        }

        $group = self::buildGroup($pid, $form->fid, $request);

        if(!is_null($request->users)) {
            foreach($request->users as $uid) {
                //remove them from an old group if they have one
                //get any groups the user belongs to
                $currGroups = DB::table('form_group_user')->where('user_id', $uid)->get();
                $grp = null;
                $idOld = 0;
                $newUser = true;

                //foreach of the user's project groups, see if one belongs to the current project
                foreach($currGroups as $prev) {
                    $grp = FormGroup::where('id', '=', $prev->form_group_id)->first();
                    if($grp->fid == $group->fid) {
                        $idOld = $grp->id;
                        $newUser = false;
                        break;
                    }
                }

                if($newUser) {
                    //add them to the project if they don't exist
                    $inProj = false;
                    $form = FormController::getForm($group->fid);
                    $proj = ProjectController::getProject($form->pid);
                    //get all project groups for this project
                    $pGroups = ProjectGroup::where('pid','=', $form->pid)->get();

                    foreach($pGroups as $pg) {
                        //see if user belongs to project group
                        $uidPG = DB::table('project_group_user')->where('user_id', $uid)->where('project_group_id', $pg->id)->get();

                        if(!empty($uidPG))
                            $inProj = true;
                    }

                    //not in project, lets add them
                    if(!$inProj) {
                        $default = ProjectGroup::where('name','=',$proj->name.' Default Group')->first();
                        DB::table('project_group_user')->insert([
                            ['project_group_id' => $default->id, 'user_id' => $uid]
                        ]);
                    }
                }

                DB::table('form_group_user')->where('user_id', $uid)->where('form_group_id', $idOld)->delete();

                //After all this, lets make sure they get the custom form added
                $user = User::where("id","=",$uid)->first();
                $user->addCustomForm($fid);
            }

            $group->users()->attach($request->users);
        }

        return redirect(action('FormGroupController@index', ['pid'=>$form->pid, 'fid'=>$form->fid]))->with('k3_global_success', 'form_group_created');
    }

    /**
     * Remove user from a form group.
     *
     * @param  Request $request
     */
    public function removeUser(Request $request) {
        $instance = FormGroup::where('id', '=', $request->formGroup)->first();

        $user = User::where("id","=",$request->userId)->first();
        $user->removeCustomForm($instance->fid);

        $instance->users()->detach($request->userId);
    }

    /**
     * Add user to a form group.
     *
     * @param  Request $request
     */
    public function addUser(Request $request) {
        $instance = FormGroup::where('id', '=', $request->formGroup)->first();

        foreach ($request->userIDs as $userID) {
            //get any groups the user belongs to
            $currGroups = DB::table('form_group_user')->where('user_id', $userID)->get();
            $newUser = true;
            $group = null;
            $idOld = 0;

            //foreach of the user's form groups, see if one belongs to the current project
            foreach($currGroups as $prev) {
                $group = FormGroup::where('id', '=', $prev->form_group_id)->first();
                if($group->fid==$instance->fid) {
                    $newUser = false;
                    $idOld = $group->id;
                    break;
                }
            }

            if(!$newUser) {
                //remove from old group
                DB::table('form_group_user')->where('user_id', $userID)->where('form_group_id', $idOld)->delete();

                echo $idOld;
            } else if(!isset($request->dontLookBack)) {
                //add them to the project if they don't exist
                $inProj = false;
                $form = FormController::getForm($instance->fid);
                $proj = ProjectController::getProject($form->pid);
                //get all project groups for this project
                $pGroups = ProjectGroup::where('pid','=', $form->pid)->get();

                foreach($pGroups as $pg) {
                    //see if user belongs to project group
                    $uidPG = DB::table('project_group_user')->where('user_id', $userID)->where('project_group_id', $pg->id)->get();

                    if(!empty($uidPG))
                        $inProj = true;
                }

                //not in project, lets add them
                if(!$inProj) {
                    $default = ProjectGroup::where('name','=',$proj->name.' Default Group')->first();
                    DB::table('project_group_user')->insert([
                        ['project_group_id' => $default->id, 'user_id' => $userID]
                    ]);
                }
            }

            //After all this, lets make sure they get the custom form added
            $user = User::where("id","=",$userID)->first();
            $user->addCustomForm($instance->fid);

            $instance->users()->attach($userID);
        }
    }

    /**
     * Delete an entire form group.
     *
     * @param  Request $request
     */
    public function deleteFormGroup(Request $request) {
        $instance = FormGroup::where('id', '=', $request->formGroup)->first();

        $users = $instance->users()->get();
        foreach($users as $user) {
            //Remove their custom form connection
            $user->removeCustomForm($instance->fid);
        }

        $instance->delete();
    }

    /**
     * Updates the permission set of a particular form group.
     *
     * @param  Request $request
     */
    public function updatePermissions(Request $request) {
        $formGroup = FormGroup::where('id', '=', $request->formGroup)->first();

        //Because of some name convention problems in JavaScript we use a simple associative array to
        //relate the permissions passed by the request to the form group
        $permissions = [['permCreate', 'create'],
            ['permEdit', 'edit'],
            ['permDelete', 'delete'],
            ['permIngest', 'ingest'],
            ['permModify', 'modify'],
            ['permDestroy', 'destroy']
        ];

        foreach($permissions as $permission) {
            if($request[$permission[0]])
                $formGroup[$permission[1]] = 1;
            else
                $formGroup[$permission[1]] = 0;
        }
        $formGroup->save();
    }

    /**
     * Update the name of a form group.
     *
     * @param  Request $request
     */
    public function updateName(Request $request) {
        $instance = FormGroup::where('id', '=', $request->gid)->first();
        $instance->name = $request->name;

        $instance->save();
    }

    /**
     * The function that physically builds the new group.
     *
     * @param  int $pid - Project ID
     * @param  int $fid - Form ID
     * @param  Request $request
     * @return FormGroup - Returns the group model
     */
    private function buildGroup($pid, $fid, Request $request) {
        $group = new FormGroup();
        $group->name = $request->name;
        $group->fid = $fid;

        $permissions = ['create','edit','delete','ingest','modify','destroy'];

        foreach($permissions as $permission) {
            if (!is_null($request[$permission]))
                $group[$permission] = 1;
            else
                $group[$permission] = 0;
        }
        $group->save();
        return $group;
    }

    /**
     * Updates the names of the Admin and Default groups when the Form name changes.
     *
     * @param  Form $form - Form that group belongs to
     */
    public static function updateMainGroupNames($form) {
        $admin = FormGroup::where('fid', '=', $form->fid)->where('name', 'like', '% Admin Group')->get()->first();
        $default = FormGroup::where('fid', '=', $form->fid)->where('name', 'like', '% Default Group')->get()->first();

        $admin->name = $form->name.' Admin Group';
        $admin->save();

        $default->name = $form->name.' Default Group';
        $default->save();
    }
}
