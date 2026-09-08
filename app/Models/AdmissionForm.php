<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionForm extends Model
{
    protected $table = 'admission_forms';

    protected $primaryKey = 'form_id';

    protected $fillable = [
        'class_id',
        'academic_yr',
        'form_title',
        'form_description',
        'student_details_enabled',
        'address_details_enabled',
        'additional_details_enabled',
        'is_active',
    ];
}