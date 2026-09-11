<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_instruction_dates', function (Blueprint $table) {

            $table->id('instruction_date_id');

            $table->unsignedBigInteger('admission_instruction_id');

            // Date displayed on page
            $table->date('event_date');

            // Time can contain ranges like:
            // 8:30 AM TO 10:00 AM
            $table->string('event_time')->nullable();

            // Event description
            $table->longText('description');

            // Frontend display order
            $table->unsignedInteger('display_order')->default(1);

            $table->char('is_active', 1)->default('Y');

            $table->timestamps();

            $table->foreign('admission_instruction_id')
                ->references('admission_instruction_id')
                ->on('admission_instructions')
                ->cascadeOnDelete();

            $table->index([
                'admission_instruction_id',
                'display_order'
            ],
              'adm_instr_dates_order_idx'
            ); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_instruction_dates');
    }
};