<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_forms', function (Blueprint $table) {

            $table->id('form_id');

            $table->unsignedBigInteger('class_id');

            $table->string('academic_yr', 20);

            $table->string('form_title')->nullable();

            $table->text('form_description')->nullable();

            // Form configuration
            $table->char('student_details_enabled', 1)->default('Y');

            $table->char('address_details_enabled', 1)->default('Y');

            $table->char('additional_details_enabled', 1)->default('Y');

            $table->char('is_active', 1)->default('Y');

            $table->timestamps();

            $table->index([
                'class_id',
                'academic_yr'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_forms');
    }
};