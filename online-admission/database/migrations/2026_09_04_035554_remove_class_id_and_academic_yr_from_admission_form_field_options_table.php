<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_form_field_options', function (Blueprint $table) {
            $table->dropColumn([
                'class_id',
                'academic_yr'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('admission_form_field_options', function (Blueprint $table) {
            $table->unsignedBigInteger('class_id')->nullable();
            $table->string('academic_yr', 20)->nullable();
        });
    }
};