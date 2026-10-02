<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdmissionFormFieldOption;

class AdmissionFormFieldOptionController extends Controller
{
    public function getOptions()
    {
        $options = AdmissionFormFieldOption::whereRaw("UPPER(TRIM(is_active)) = 'Y'")
            ->orderBy('field_name')
            ->orderBy('display_order')
            ->get()
            ->unique(function ($option) {
                return strtolower(trim($option->field_name)) . ':' .
                    strtolower(trim($option->option_value));
            })
            ->values();

        $data = [];

        foreach ($options as $option) {

            $data[$option->field_name][] = [
                'field_option_id' => $option->field_option_id,
                'value' => $option->option_value,
                'display_order' => $option->display_order,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}