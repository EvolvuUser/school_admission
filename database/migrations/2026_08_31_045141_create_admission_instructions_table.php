<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_instructions', function (Blueprint $table) {

            $table->id('admission_instruction_id');

            // Class-wise instructions
            $table->Integer('class_id');

            // Academic year
            $table->string('academic_yr', 20);

            // Main page heading
            $table->string('title')->nullable();

            // Small heading displayed below the title
            $table->string('subtitle')->nullable();

            // Main instructions heading
            $table->string('instructions_heading')->nullable();

            // Important dates heading
            $table->string('dates_heading')->nullable();

            // Documents heading
            $table->string('documents_heading')->nullable();

            // Age criteria heading
            $table->string('age_criteria_heading')->nullable();

            // Important considerations heading
            $table->string('notes_heading')->nullable();

            // Contact details heading
            $table->string('contact_heading')->nullable();

            // Contact information
            $table->longText('contact_details')->nullable();

            // Principal name/designation
            $table->string('principal_name')->nullable();
            $table->string('principal_designation')->nullable();

            // Button
            $table->string('application_button_text')
                ->nullable()
                ->default('Proceed to Application Form');

            // Active/inactive
            $table->char('is_active', 1)
                ->default('Y');

            $table->timestamps();

            $table->foreign('class_id')
                ->references('class_id')
                ->on('class')
                ->restrictOnDelete();

            $table->index(['class_id', 'academic_yr']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_instructions');
    }
};