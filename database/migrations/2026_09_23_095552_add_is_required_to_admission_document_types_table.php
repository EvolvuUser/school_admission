<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admission_document_types', function (Blueprint $table) {
            $table->char('is_required', 1)
                ->default('N')
                ->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_document_types', function (Blueprint $table) {
            $table->dropColumn('is_required');
        });
    }
};