<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    /**
     * Database table.
     */
    protected $table = 'enquiries';

    /**
     * Primary key.
     */
    protected $primaryKey = 'id';

    /**
     * Fields that can be mass assigned.
     */
    protected $fillable = [
    'enquiry_number',

    'first_name',
    'middle_name',
    'last_name',
    'dob',
    'gender',
    'class',

    'father_name',
    'mother_name',

    'contact_no',
    'email',

    'current_school',
    'address',
    'pincode',

    'sibling_currently_studying',

    'all_documents_available',

    'question',
];

    /**
     * Cast database fields to appropriate types.
     */
    protected $casts = [

        'dob' => 'date',

    ];
}