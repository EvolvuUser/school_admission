<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionInstructionNote extends Model
{
    protected $table = 'admission_instruction_notes';

    protected $primaryKey = 'instruction_note_id';

    protected $fillable = [
        'admission_instruction_id',
        'note_title',
        'note',
        'note_number',
        'icon',
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