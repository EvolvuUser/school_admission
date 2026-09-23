<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionDocumentType extends Model
{
    protected $table = 'admission_document_types';

    protected $primaryKey = 'id';

    protected $fillable = [
        'code',
        'name',
        'is_required',
        'is_active',
    ];
}