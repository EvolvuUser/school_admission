<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionSignature extends Model
{
    protected $table = 'admission_signatures';

    protected $primaryKey = 'signature_id';

    protected $fillable = [

        'form_id',

        'signature_type',

        'signature_name',

        'signature_file_path',

        'declaration_confirmed',

        'terms_accepted',

        'privacy_accepted',

    ];

    protected $casts = [

        'declaration_confirmed' => 'boolean',

        'terms_accepted' => 'boolean',

        'privacy_accepted' => 'boolean',

    ];
}