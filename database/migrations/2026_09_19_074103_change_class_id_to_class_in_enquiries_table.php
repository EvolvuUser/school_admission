<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->string('class', 100)->nullable()->after('gender');
        });

        // If class_id already contains data that must be preserved,
        // copy the class names before dropping class_id.
        DB::statement("
            UPDATE enquiries e
            LEFT JOIN class c ON c.class_id = e.class_id
            SET e.class = c.name
            WHERE e.class_id IS NOT NULL
        ");

        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropColumn('class_id');
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->unsignedBigInteger('class_id')->nullable()->after('gender');
        });

        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropColumn('class');
        });
    }
};