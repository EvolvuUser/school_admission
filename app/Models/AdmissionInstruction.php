<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\AdmissionInstructionAgeCriteria;

class AdmissionInstruction extends Model
{
    protected $table = 'admission_instructions';

    protected $primaryKey = 'admission_instruction_id';

    protected $fillable = [
        'class_id',
        'academic_yr',
        'title',
        'subtitle',
        'instructions_heading',
        'dates_heading',
        'documents_heading',
        'age_criteria_heading',
        'notes_heading',
        'contact_heading',
        'contact_details',
        'principal_name',
        'principal_designation',
        'application_button_text',
        'is_active',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(
            AdmissionInstructionItem::class,
            'admission_instruction_id',
            'admission_instruction_id'
        )->orderBy('display_order');
    }

    public function dates(): HasMany
    {
        return $this->hasMany(
            AdmissionInstructionDate::class,
            'admission_instruction_id',
            'admission_instruction_id'
        )->orderBy('display_order');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(
            AdmissionInstructionDocument::class,
            'admission_instruction_id',
            'admission_instruction_id'
        )->orderBy('display_order');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(
            AdmissionInstructionInstallment::class,
            'admission_instruction_id',
            'admission_instruction_id'
        )->orderBy('display_order');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(
            AdmissionInstructionNote::class,
            'admission_instruction_id',
            'admission_instruction_id'
        )->orderBy('display_order');
    }
    public function ageCriteria()
{
    return $this->hasMany(
        AdmissionInstructionAgeCriteria::class,
        'admission_instruction_id',
        'admission_instruction_id'
    )->orderBy('display_order');
}
}