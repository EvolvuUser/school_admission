<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineAdmissionFee extends Model
{
    protected $table = 'online_admfee';

    protected $primaryKey = 'adfees_payment_id';

    public $timestamps = false;

    protected $fillable = [
        'OrderId',
        'status',
        'form_id',
        'first_name',
        'last_name',
        'parent_name',
        'phone',
        'email',
        'remark',
        'payment_date',
        'amount',
        'Trnx_ref_no',
        'rrn',
        'Status_code',
        'Status_desc',
        'synced_later',
        'academic_yr',
    ];
}