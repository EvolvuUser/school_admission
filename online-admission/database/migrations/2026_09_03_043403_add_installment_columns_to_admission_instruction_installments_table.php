<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admission_instruction_installments', function (Blueprint $table) {

            $table->decimal('first_installment', 10, 2)
                  ->nullable()
                  ->after('admission_instruction_id');

            $table->decimal('second_installment', 10, 2)
                  ->nullable()
                  ->after('first_installment');

            $table->decimal('third_installment', 10, 2)
                  ->nullable()
                  ->after('second_installment');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_instruction_installments', function (Blueprint $table) {

            $table->dropColumn([
                'first_installment',
                'second_installment',
                'third_installment',
            ]);

        });
    }
};