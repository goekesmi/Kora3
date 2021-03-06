<?php namespace App\Http\Requests;

use App\Http\Controllers\FieldController;

class FieldRequest extends Request {

    /*
    |--------------------------------------------------------------------------
    | Field Request
    |--------------------------------------------------------------------------
    |
    | This request handles validation of request inputs for Fields
    |
    */

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        $id = $this->route('flid');
        $field = FieldController::getField($id);

        switch($this->method()) {
            case 'POST':
                return [
                    'pid' => 'required|numeric',
                    'fid' => 'required|numeric',
                    'type' => 'required',
                    'name' => 'required|min:3',
                    'slug' => 'required|alpha_dash|min:3|unique:fields',
                    'desc' => 'required|max:255'
                ];
            case 'PATCH':
                return [
                    'pid' => 'required|numeric',
                    'fid' => 'required|numeric',
                    'type' => 'required',
                    'name' => 'required|min:3',
                    'slug' => 'required|alpha_dash|min:3|unique:fields,slug,'.$field->flid.',flid',
                    'desc' => 'required|max:255'
                ];
            default:
                break;
        }
    }

    /**
     * Get the custom error messages for Fields.
     *
     * @return array
     */
    public function messages() {
        return [
            'slug.required' => "The reference name field is required.",
            'slug.alpha_dash' => "The reference name may only contain letters, numbers, underscores, and hyphens.",
            'slug.min' => "The reference name must be at least 3 characters.",
            'slug.unique' => "The reference name already exists. Please try another one."
        ];
    }

}