<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_form_field_options', function (Blueprint $table) {

            $table->unsignedBigInteger('class_id')
                ->after('field_option_id');

            $table->string('academic_yr', 20)
                ->after('class_id');
        });
    }

    public function down(): void
    {
        Schema::table('admission_form_field_options', function (Blueprint $table) {

            $table->dropColumn([
                'class_id',
                'academic_yr'
            ]);
        });
    }
};