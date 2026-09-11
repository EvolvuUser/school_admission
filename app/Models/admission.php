<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    protected $table = 'new_adm_registration';

    protected $primaryKey = 'nar_id';

    public $timestamps = false;

    protected $fillable = [
        'nar_id',
        'parent_name',
        'email',
        'phone_no',
        'date',
    ];
}