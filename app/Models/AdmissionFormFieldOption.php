<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionFormFieldOption extends Model
{
    protected $table = 'admission_form_field_options';

    protected $primaryKey = 'field_option_id';

    protected $fillable = [
        'field_name',
        'option_value',
        'display_order',
        'is_active',
    ];
}