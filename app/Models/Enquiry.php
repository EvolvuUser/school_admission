<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    protected $table = 'enquiries';

    protected $primaryKey = 'id';

    protected $fillable = [

        'enquiry_number',

        'first_name',
        'last_name',
        'dob',
        'gender',

        'class',

        'father_name',
        'mother_name',

        'contact_no',
        'email',

        'current_school',

        'all_documents_available',

        'question',

        'source',
        'status',

        'notes',

        'conversion_reference',
    ];

    protected $casts = [

        'dob' => 'date',

        'all_documents_available' => 'boolean',
    ];
}