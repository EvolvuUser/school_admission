<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_instruction_notes', function (Blueprint $table) {

            $table->id('instruction_note_id');

            $table->unsignedBigInteger('admission_instruction_id');

            // Example: Limited Seats
            $table->string('note_title');

            // Full note
            $table->longText('note');

            // Optional number displayed on card
            $table->unsignedInteger('note_number')->nullable();

            // Optional icon/class name
            $table->string('icon')->nullable();

            // Order on frontend
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
                'adm_instr_notes_order_idx' );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_instruction_notes');
    }
};