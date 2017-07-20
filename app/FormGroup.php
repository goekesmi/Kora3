<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormGroup extends Model {

    /*
    |--------------------------------------------------------------------------
    | Form Group
    |--------------------------------------------------------------------------
    |
    | This model represents the data for a Form Group
    |
    */

    /**
     * @var array - Attributes that can be mass assigned to model
     */
	protected $fillable = ['name', 'create', 'edit', 'delete'];

    /**
     * Returns the users belonging to a form group.
     *
     * @return BelongsToMany
     */
    public function users() {
        return $this->belongsToMany('App\User');
    }

    /**
     * Returns a form group's form.
     *
     * @return BelongsTo
     */
    public function form() {
        return $this->belongsTo('App\Form');
    }

    /**
     * Returns a form group's project.
     *
     * @return Project
     */
    public function project() {
        $form = $this->form()->first();
        return $form->project();
    }

    /**
     * Determines if a user is in a form group.
     *
     * @param User $user - User to verify
     * @return bool - Is member
     */
    public function hasUser(User $user) {
        $thisUsers = $this->users()->get();
        return $thisUsers->contains($user);
    }

    /**
     * Delete's the connections between group and users, and then deletes self.
     */
    public function delete() {
        DB::table("form_group_user")->where("form_group_id", "=", $this->id)->delete();

        parent::delete();
    }

    /**
     * Creates the form's admin group.
     *
     * @param  Form $form - Form to create group for
     * @param  Request $request
     * @return FormGroup - The newly created group
     */
    public static function makeAdminGroup(Form $form, $request = null) {
        $groupName = $form->name;
        $groupName .= ' Admin Group';

        $adminGroup = new FormGroup();
        $adminGroup->name = $groupName;
        $adminGroup->fid = $form->fid;
        $adminGroup->save();

        $formProject = $form->project()->first();
        $projectAdminGroup = $formProject->adminGroup()->first();

        $projectAdmins = $projectAdminGroup->users()->get();
        $idArray = [];

        //Add all current project admins to the form's admin group.
        foreach($projectAdmins as $projectAdmin)
            $idArray[] .= $projectAdmin->id;

        if(!is_null($request['admins']))
            $idArray = array_unique(array_merge($request['admins'], $idArray));
        else
            $idArray = array_unique(array_merge(array(\Auth::user()->id), $idArray));

        if(!empty($idArray))
            $adminGroup->users()->attach($idArray);

        $adminGroup->create = 1;
        $adminGroup->edit = 1;
        $adminGroup->delete = 1;
        $adminGroup->ingest = 1;
        $adminGroup->modify = 1;
        $adminGroup->destroy = 1;

        $adminGroup->save();

        return $adminGroup;
    }

    /**
     * Creates the form's default group.
     *
     * @param  Form $form - Form to create group for
     */
    public static function makeDefaultGroup(Form $form) {
        $groupName = $form->name;
        $groupName .= ' Default Group';

        $defaultGroup = new FormGroup();
        $defaultGroup->name = $groupName;
        $defaultGroup->fid = $form->fid;
        $defaultGroup->save();

        $defaultGroup->create = 0;
        $defaultGroup->edit = 0;
        $defaultGroup->delete = 0;
        $defaultGroup->ingest = 0;
        $defaultGroup->modify = 0;
        $defaultGroup->destroy = 0;

        $defaultGroup->save();
    }
}
