<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionUser extends Model
{
    protected $table = 'new_adm_user_master';

    protected $primaryKey = 'nar_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'password',
        'otp_generated_at',
        'nar_id',
        'IsDelete',
        'IsVerify',
        'special_user',
    ];
}
