<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionInstructionAgeCriteria extends Model
{
    protected $table = 'admission_instruction_age_criteria';

    protected $primaryKey = 'age_criteria_id';

    protected $fillable = [
        'admission_instruction_id',
        'class_id',
        'heading',
        'minimum_age',
        'maximum_age',
        'cutoff_date',
        'description',
        'display_order',
        'is_active',
    ];

    public function admissionInstruction(): BelongsTo
    {
        return $this->belongsTo(
            AdmissionInstruction::class,
            'admission_instruction_id',
            'admission_instruction_id'
        );
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(
            SchoolClass::class,
            'class_id',
            'class_id'
        );
    }
}