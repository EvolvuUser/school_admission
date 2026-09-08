<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionInstructionInstallment extends Model
{
    protected $table = 'admission_instruction_installments';

    protected $primaryKey = 'instruction_installment_id';

    protected $fillable = [
        'admission_instruction_id',
        'first_installment',
        'second_installment',
        'third_installment',
        'payment_instructions',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'first_installment' => 'decimal:2',
        'second_installment' => 'decimal:2',
        'third_installment' => 'decimal:2',
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