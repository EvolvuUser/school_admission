<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_instruction_documents', function (Blueprint $table) {

            $table->id('instruction_document_id');

            $table->unsignedBigInteger('admission_instruction_id');

            // Document name/title
            $table->string('document_title');

            // Detailed explanation
            $table->longText('description')->nullable();

            // Mandatory / optional
            $table->char('is_mandatory', 1)->default('N');

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
                'adm_instr_documents_order_idx'
                );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_instruction_documents');
    }
};