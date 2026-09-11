<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionForm extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Database Table
    |--------------------------------------------------------------------------
    */

    protected $table = 'admission_forms';


    /*
    |--------------------------------------------------------------------------
    | Primary Key
    |--------------------------------------------------------------------------
    */

    protected $primaryKey = 'form_id';


    /*
    |--------------------------------------------------------------------------
    | Timestamps
    |--------------------------------------------------------------------------
    |
    | Set to false if admission_forms does not contain:
    | created_at
    | updated_at
    |
    */

    public $timestamps = false;


    /*
    |--------------------------------------------------------------------------
    | Mass Assignable Fields
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        // Form identification
        'class_id',
        'academic_yr',

        // Form information
        'form_title',
        'form_description',

        // Form configuration
        'student_details_enabled',
        'address_details_enabled',
        'additional_details_enabled',

        // Status
        'is_active',
    ];
}
