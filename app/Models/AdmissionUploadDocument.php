<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionUploadDocument extends Model
{
    protected $table = 'admission_upload_detail';

    public $timestamps = false;

    protected $fillable = [
        'form_id',
        'doc_type',
        'image_name',
    ];
}