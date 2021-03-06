<?php namespace App\Http\Controllers;

use App\Token;
use App\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class TokenController extends Controller {

    /*
    |--------------------------------------------------------------------------
    | Token Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles creation and management of data authentication tokens
    |
    */

    /**
     * Constructs controller and makes sure user is authenticated and a system admin.
     */
    public function __construct() {
        $this->middleware('auth');
        $this->middleware('active');
        $this->middleware('admin');
    }

    /**
     * Gets the view for the token management page.
     *
     * @return View
     */
    public function index() {
        $tokens = Token::all();
        $projects = Project::pluck('name', 'pid')->all();
        $all_projects = Project::all(); //Second variable created here to get around weird indexing needed for pivot table in $projects

        return view('tokens.index', compact('tokens', 'projects', 'all_projects'));
    }

    /**
     * Creates a new token.
     *
     * @param  Request $request
     * @return Redirect
     */
    public function create(Request $request) {
        $token = new Token();
        $token->token = self::tokenGen();
        $token->title = $request->token_name;
        $token->search = isset($request->token_search) ? true : false;
        $token->create = isset($request->token_create) ? true : false;
        $token->edit = isset($request->token_edit) ? true : false;
        $token->delete = isset($request->token_delete) ? true : false;
        $token->save();

        if (!is_null($request->token_projects))
            $token->projects()->attach($request->token_projects);

        return redirect('tokens')->with('k3_global_success', 'token_created');
    }

    /**
     * Edit a token's permission types and its name.
     *
     * @param  Request $request
     * @return Redirect
     */
    public function edit(Request $request) {
        $token = self::getToken($request->token);

        $token->title = $request->token_name;
        $token->search = isset($request->token_search) ? true : false;
        $token->create = isset($request->token_create) ? true : false;
        $token->edit = isset($request->token_edit) ? true : false;
        $token->delete = isset($request->token_delete) ? true : false;
        $token->save();

        return redirect('tokens')->with('k3_global_success', 'token_edited');
    }

    /**
     * Get a list of projects the token doesn't own.
     *
     * @param  Request $request
     * @return array - The project models
     */
    public function getUnassignedProjects(Request $request) {
        $token = self::getToken($request->token);

        $allProjects = Project::all();
        $results = array();

        foreach($allProjects as $project) {
            if(!$token->hasProject($project)) {
                array_push($results,$project);
            }
        }

        return $results;
    }

    /**
     * Removes project authentication from a token.
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function deleteProject(Request $request) {
        $token = self::getToken($request->token);
        $token->projects()->detach($request->pid);

        return redirect('tokens')->with('k3_global_success', 'token_projects_deleted');
    }

    /**
     * Adds project authentication from a token.
     *
     * @param  Request $request
     * @return Redirect
     */
    public function addProject(Request $request) {
        $token = self::getToken($request->token);
        $token->projects()->attach($request->token_projects);

        return redirect('tokens')->with('k3_global_success', 'token_projects_added');
    }

    /**
     * Deletes a token from Kora3.
     *
     * @param  Request $request
     * @return Redirect
     */
    public function deleteToken(Request $request) {
        $token = self::getToken($request->token);
        $token->delete();

        return redirect('tokens')->with('k3_global_success', 'token_deleted');
    }

    /**
     * Generates a new 24 character token.
     *
     * @return string - Newly created token
     */
    public static function tokenGen() {
        $valid = 'abcdefghijklmnopqrstuvwxyz';
        $valid .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $valid .= '0123456789';

        $token = '';
        for($i = 0; $i < 24; $i++) {
            $token .= $valid[( rand() % 62 )];
        }
        return $token;
    }

    /**
     * Gets a token based on ID.
     *
     * @param  int $id - Token ID
     * @return Token - Requested token
     */
    public static function getToken($id) {
        return Token::where('id', '=', $id)->first();
    }
}
