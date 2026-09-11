<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionInstructionDocument extends Model
{
    protected $table = 'admission_instruction_documents';

    protected $primaryKey = 'instruction_document_id';

    protected $fillable = [
        'admission_instruction_id',
        'document_title',
        'description',
        'is_mandatory',
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