<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {

            $table->unsignedBigInteger('nar_id')
                ->nullable()
                ->after('id');

            $table->index('nar_id');
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {

            $table->dropIndex(['nar_id']);

            $table->dropColumn('nar_id');
        });
    }
};