<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_instruction_installments', function (Blueprint $table) {
            $table->dropColumn([
                'installment_title',
                'amount',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('admission_instruction_installments', function (Blueprint $table) {
            $table->string('installment_title');
            $table->decimal('amount', 10, 2);
        });
    }
};