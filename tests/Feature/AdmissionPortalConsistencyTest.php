<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdmissionPortalConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('new_adm_registration', function ($table) {
            $table->increments('nar_id');
            $table->string('parent_name')->nullable();
            $table->string('email')->default(' ');
            $table->string('phone_no')->default(' ');
            $table->date('date')->nullable();
        });

        Schema::create('online_admission_form', function ($table) {
            $table->increments('adm_form_pk');
            $table->string('form_id')->nullable();
            $table->unsignedInteger('nar_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('status')->nullable();
        });

        Schema::create('school_settings', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('school_id');
            $table->string('institute_name')->nullable();
            $table->string('is_active')->default('Y');
        });
    }

    public function test_dashboard_count_matches_online_forms_for_same_registration(): void
    {
        $narId = DB::table('new_adm_registration')->insertGetId([
            'parent_name' => 'Test Parent',
            'email' => 'parent@example.com',
            'phone_no' => '9876543210',
            'date' => now()->toDateString(),
        ]);

        DB::table('online_admission_form')->insert([
            'form_id' => 'FORM-001',
            'nar_id' => $narId,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'status' => 'draft',
        ]);

        $dashboardResponse = $this->getJson('/api/admission/dashboard?nar_id=' . $narId);
        $dashboardResponse->assertOk()
            ->assertJsonPath('data.forms_count', 1)
            ->assertJsonPath('data.totalFormsRegistered', 1);

        $formsResponse = $this->getJson('/api/admission/online-forms?nar_id=' . $narId);
        $formsResponse->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame(1, $dashboardResponse->json('data.forms_count'));
        $this->assertSame(1, $formsResponse->json('data.0.nar_id'));
    }

    public function test_registration_reuses_existing_nar_id_for_same_phone_number(): void
    {
        $narId = DB::table('new_adm_registration')->insertGetId([
            'parent_name' => 'Existing Parent',
            'email' => ' ',
            'phone_no' => '9876543210',
            'date' => now()->toDateString(),
        ]);

        $response = $this->postJson('/api/admission/registration', [
            'parent_name' => 'Existing Parent',
            'phone_no' => '9876543210',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.nar_id', $nar_id)
            ->assertJsonPath('data.phone_no', '9876543210');

        $this->assertSame(1, DB::table('new_adm_registration')->count());
    }

    public function test_send_otp_accepts_email_payload_without_type_field(): void
    {
        DB::table('school_settings')->insert([
            'school_id' => 1,
            'institute_name' => 'Demo School',
            'is_active' => 'Y',
        ]);

        $response = $this->postJson('/api/admission/send-otp', [
            'email' => 'parent@example.com',
            'parent_name' => 'Sample Parent',
            'school_id' => 1,
        ]);

        $response->assertOk();
    }

    public function test_single_page_routes_do_not_404_on_refresh(): void
    {
        $this->get('/verify-otp')->assertOk();
    }
}
