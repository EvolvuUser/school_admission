<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentStatusMappingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('online_admission_form', function ($table) {
            $table->increments('adm_form_pk');
            $table->string('form_id')->unique();
            $table->unsignedInteger('nar_id');
            $table->string('academic_yr')->nullable();
            $table->unsignedInteger('class_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('status')->nullable();
            $table->string('admission_form_status')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('sms_sending_phone_no')->nullable();
        });

        Schema::create('online_admfee', function ($table) {
            $table->increments('adfees_payment_id');
            $table->string('OrderId')->unique();
            $table->string('status')->nullable();
            $table->string('form_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('remark')->nullable();
            $table->date('payment_date')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('Trnx_ref_no')->nullable();
            $table->string('rrn')->nullable();
            $table->string('Status_code')->nullable();
            $table->string('Status_desc')->nullable();
            $table->string('synced_later')->nullable();
            $table->string('academic_yr')->nullable();
        });
    }

    public function test_payment_success_updates_payment_status_not_application_status(): void
    {
        $form = DB::table('online_admission_form')->insertGetId([
            'form_id' => 'TEST-FORM-001',
            'nar_id' => 123,
            'academic_yr' => '2026-2027',
            'class_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'father_name' => 'Father',
            'mother_name' => 'Mother',
            'status' => 'A',
            'admission_form_status' => 'Applied',
            'payment_status' => null,
            'sms_sending_phone_no' => '9876543210',
        ]);

        DB::table('online_admfee')->insert([
            'OrderId' => 'ADMTEST123',
            'status' => 'A',
            'form_id' => 'TEST-FORM-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'parent_name' => 'Father',
            'phone' => '9876543210',
            'email' => 'parent@example.com',
            'remark' => 'Admission Form Fee',
            'payment_date' => now()->toDateString(),
            'amount' => 1500,
            'Trnx_ref_no' => 0,
            'rrn' => null,
            'Status_code' => 'A',
            'Status_desc' => 'Payment Attempted',
            'synced_later' => 'N',
            'academic_yr' => '2026-2027',
        ]);

        $formRow = DB::table('online_admission_form')->where('form_id', 'TEST-FORM-001')->first();
        $this->assertSame('Applied', $formRow->admission_form_status);
        $this->assertNull($formRow->payment_status);

        DB::table('online_admission_form')
            ->where('form_id', 'TEST-FORM-001')
            ->update([
                'payment_status' => 'Success',
            ]);

        $updatedRow = DB::table('online_admission_form')->where('form_id', 'TEST-FORM-001')->first();
        $this->assertSame('Success', $updatedRow->payment_status);
        $this->assertSame('Applied', $updatedRow->admission_form_status);
    }
}
