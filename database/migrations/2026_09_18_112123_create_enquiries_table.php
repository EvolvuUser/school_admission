<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Enquiry Number
            |--------------------------------------------------------------------------
            */

            $table->string('enquiry_number', 30)
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Student Details
            |--------------------------------------------------------------------------
            */

            $table->string('first_name', 100);

            $table->string('last_name', 100)
                ->nullable();

            $table->date('dob')
                ->nullable();

            /*
             * M = Male
             * F = Female
             * O = Other
             */

            $table->char('gender', 1)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Class
            |--------------------------------------------------------------------------
            |
            | This stores class_id from the existing `class` table.
            |
            */

            $table->unsignedInteger('class_id')
                ->nullable()
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Parent Details
            |--------------------------------------------------------------------------
            */

            $table->string('father_name', 150)
                ->nullable();

            $table->string('mother_name', 150)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Contact Details
            |--------------------------------------------------------------------------
            */

            $table->string('contact_no', 20);

            $table->string('email', 150)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | School Details
            |--------------------------------------------------------------------------
            */

            $table->string('current_school', 200)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Document Availability
            |--------------------------------------------------------------------------
            */

            $table->boolean('all_documents_available')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | User Question
            |--------------------------------------------------------------------------
            */

            $table->text('question')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Enquiry Management
            |--------------------------------------------------------------------------
            */

            $table->string('source', 50)
                ->default('WEBSITE');

            /*
             * NEW
             * IN_PROGRESS
             * CONVERTED
             * CLOSED
             */

            $table->string('status', 30)
                ->default('NEW')
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Admin Notes
            |--------------------------------------------------------------------------
            */

            $table->text('notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Conversion
            |--------------------------------------------------------------------------
            */

            $table->string('conversion_reference', 100)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('contact_no');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};