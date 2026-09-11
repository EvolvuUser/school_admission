<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_instruction_age_criteria', function (Blueprint $table) {

            $table->decimal('minimum_age', 4, 2)
                ->nullable()
                ->after('heading');

            $table->decimal('maximum_age', 4, 2)
                ->nullable()
                ->after('minimum_age');

            $table->date('cutoff_date')
                ->nullable()
                ->after('maximum_age');
        });
    }

    public function down(): void
    {
        Schema::table('admission_instruction_age_criteria', function (Blueprint $table) {

            $table->dropColumn([
                'minimum_age',
                'maximum_age',
                'cutoff_date'
            ]);
        });
    }
};