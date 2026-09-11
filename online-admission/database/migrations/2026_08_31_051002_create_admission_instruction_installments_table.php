<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_instruction_installments', function (Blueprint $table) {

            $table->id('instruction_installment_id');

            $table->unsignedBigInteger('admission_instruction_id');

            // Example: FIRST INSTALLMENT
            $table->string('installment_title');

            // Amount
            $table->decimal('amount', 10, 2)->nullable();

            // Example: "At the time of Admission in April 2026"
            $table->longText('payment_details')->nullable();

            // Frontend display order
            $table->unsignedInteger('display_order')->default(1);

            $table->char('is_active', 1)->default('Y');

            $table->timestamps();

            $table->foreign('admission_instruction_id',
                             'adm_instr_installments_fk'
                             )
                ->references('admission_instruction_id')
                ->on('admission_instructions')
                ->cascadeOnDelete();

            $table->index([
                'admission_instruction_id',
                'display_order'
            ],
                 'adm_instr_installments_order_idx'
                 );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_instruction_installments');
    }
};