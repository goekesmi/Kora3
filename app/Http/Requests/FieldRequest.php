<?php namespace App\Http\Requests;

use App\Http\Requests\Request;

class FieldRequest extends Request {

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'pid' => 'required|numeric',
            'fid' => 'required|numeric',
            'type' => 'required',
            'name' => 'required|min:3',
            'slug' => 'required|alpha_num|min:3',
            'desc' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'slug.required' => 'The reference name field is required.',
            'slug.alpha_num' => 'The reference name may only contain letters and numbers.',
            'slug.min' => 'The reference name must be at least 3 characters.'
        ];
    }

}