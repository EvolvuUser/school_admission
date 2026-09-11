<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_instruction_age_criteria', function (Blueprint $table) {
            $table->bigIncrements('age_criteria_id');

            $table->unsignedBigInteger('admission_instruction_id');

            $table->Integer('class_id');

            $table->string('heading')->nullable();

            $table->text('description')->nullable();

            $table->integer('display_order')->default(1);

            $table->char('is_active', 1)->default('Y');

            $table->timestamps();

            // Short custom foreign-key name
            $table->foreign(
                'admission_instruction_id',
                'age_criteria_instruction_fk'
            )
            ->references('admission_instruction_id')
            ->on('admission_instructions')
            ->onDelete('cascade');

            // class.class_id is INT, so use unsignedInteger
            $table->foreign(
                'class_id',
                'age_criteria_class_fk'
            )
            ->references('class_id')
            ->on('class')
            ->onDelete('restrict');

            // Short index name
            $table->index(
                ['admission_instruction_id', 'display_order'],
                'age_criteria_order_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_instruction_age_criteria');
    }
};