<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_document_types', function (Blueprint $table) {

            $table->id();

            $table->string('code', 20)->unique();

            $table->string('name', 150);

            $table->char('is_active', 1)->default('Y');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_document_types');
    }
};