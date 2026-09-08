<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('getSchoolSettingsData')) {

    function getSchoolSettingsData()
    {
        return DB::table('school_settings')
            ->where('is_active', 'Y')
            ->first();
    }
}