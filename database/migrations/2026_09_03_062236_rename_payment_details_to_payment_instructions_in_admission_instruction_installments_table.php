<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_instruction_installments', function (Blueprint $table) {
            $table->renameColumn(
                'payment_details',
                'payment_instructions'
            );
        });
    }

    public function down(): void
    {
        Schema::table('admission_instruction_installments', function (Blueprint $table) {
            $table->renameColumn(
                'payment_instructions',
                'payment_details'
            );
        });
    }
};