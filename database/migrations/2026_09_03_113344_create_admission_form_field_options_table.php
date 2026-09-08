<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_form_field_options', function (Blueprint $table) {

            $table->id('field_option_id');

            // Example: gender, religion, category, blood_group
            $table->string('field_name', 100);

            // Example: Male, Female, Hindu, General, A+
            $table->string('option_value', 255);

            $table->integer('display_order')->default(1);

            $table->char('is_active', 1)->default('Y');

            $table->timestamps();

            $table->index('field_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_form_field_options');
    }
};