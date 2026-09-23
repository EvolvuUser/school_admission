<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_signatures', function (Blueprint $table) {

            $table->id('signature_id');

            /*
            |--------------------------------------------------------------------------
            | Application Form
            |--------------------------------------------------------------------------
            */

            $table->string('form_id', 50)->unique();


            /*
            |--------------------------------------------------------------------------
            | Signature Type
            |--------------------------------------------------------------------------
            |
            | typed = signature entered as name
            | pdf   = uploaded signature PDF
            |
            */

            $table->enum('signature_type', [
                'typed',
                'pdf'
            ]);


            /*
            |--------------------------------------------------------------------------
            | Typed Signature
            |--------------------------------------------------------------------------
            */

            $table->string('signature_name', 255)->nullable();


            /*
            |--------------------------------------------------------------------------
            | PDF Signature
            |--------------------------------------------------------------------------
            |
            | Store only the file path in DB.
            | Actual PDF is stored in storage/app/public.
            |
            */

            $table->string('signature_file_path', 500)->nullable();


            /*
            |--------------------------------------------------------------------------
            | Declaration
            |--------------------------------------------------------------------------
            |
            | These correspond to the three checkboxes shown
            | in your screenshot.
            |
            */

            $table->boolean('declaration_confirmed')->default(false);

            $table->boolean('terms_accepted')->default(false);

            $table->boolean('privacy_accepted')->default(false);


            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */

            $table->index('form_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_signatures');
    }
};