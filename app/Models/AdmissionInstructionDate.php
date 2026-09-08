<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionInstructionDate extends Model
{
    protected $table = 'admission_instruction_dates';

    protected $primaryKey = 'instruction_date_id';

    protected $fillable = [
        'admission_instruction_id',
        'event_date',
        'event_time',
        'description',
        'display_order',
        'is_active',
    ];

    public function instructionPage(): BelongsTo
    {
        return $this->belongsTo(
            AdmissionInstruction::class,
            'admission_instruction_id',
            'admission_instruction_id'
        );
    }
}