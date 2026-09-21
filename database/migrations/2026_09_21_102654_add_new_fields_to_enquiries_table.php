<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {

            $table->string('middle_name', 100)
                ->nullable()
                ->after('first_name');

            $table->text('address')
                ->nullable()
                ->after('current_school');

            $table->string('pincode', 10)
                ->nullable()
                ->after('address');

            $table->char('sibling_currently_studying', 1)
                ->default('N')
                ->after('pincode');
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {

            $table->dropColumn([
                'middle_name',
                'address',
                'pincode',
                'sibling_currently_studying',
            ]);
        });
    }
};