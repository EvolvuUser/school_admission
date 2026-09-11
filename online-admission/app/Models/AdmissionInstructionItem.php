<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionInstructionItem extends Model
{
    protected $table = 'admission_instruction_items';

    protected $primaryKey = 'instruction_item_id';

    protected $fillable = [
        'admission_instruction_id',
        'instruction',
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