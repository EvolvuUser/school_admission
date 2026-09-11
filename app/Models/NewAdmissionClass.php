<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewAdmissionClass extends Model
{
    protected $table = 'new_admission_class';

    protected $primaryKey = 'nac_id';

    public $timestamps = false;

    protected $fillable = [
        'class_id',
        'start_date',
        'end_date',
        'age_start_date',
        'age_end_date',
        'application_form_fee',
        'type',
        'account_id',
        'academic_yr',
        'publish',
    ];
}