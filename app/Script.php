<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Script extends Model {

    /*
    |--------------------------------------------------------------------------
    | Script
    |--------------------------------------------------------------------------
    |
    | This model represents an update script for Kora3
    |
    */

    /**
     * @var array - Attributes that can be mass assigned to model
     */
    protected $fillable = ['id', 'filename', 'hasRun'];

}
