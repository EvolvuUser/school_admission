<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineAdmissionForm extends Model
{
    protected $table = 'online_admission_form';

    protected $primaryKey = 'adm_form_pk';

    public $timestamps = false;

    protected $fillable = [

        // Form details
        'form_id',
        'academic_yr',
        'class_id',
        'type',
        'nar_id',

        // Student details
        'first_name',
        'mid_name',
        'last_name',
        'dob',
        'birth_place',
        'gender',
        'religion',
        'caste',
        'subcaste',
        'nationality',
        'mother_tongue',
        'category',

        // Address details
        'locality',
        'city',
        'state',
        'pincode',
        'perm_address',

        // Additional details
        'stud_aadhar',
        'blood_group',
        'current_school_class',
        'acheivements',

        // Sibling details
        'sibling',
        'sibling_class_id',
        'sibling_student_id',

        // Father details
        'father_name',
        'father_occupation',
        'f_mobile',
        'f_email',
        'f_qualification',
        'f_designation',
        'f_nature_of_bussiness',
        'f_office_add',
        'f_aadhar_no',

        // Mother details
        'mother_name',
        'mother_occupation',
        'm_mobile',
        'm_emailid',
        'm_qualification',
        'm_designation',
        'm_nature_of_bussiness',
        'm_office_add',
        'm_aadhar_no',

        // Parent preferences
        'area_in_which_parent_can_contribute',
        'other_area',

        // Status
        'status',
        'admission_form_status',
        'sms_sending_phone_no',

        // Other
        'student_id',
        'application_date',
    ];
}