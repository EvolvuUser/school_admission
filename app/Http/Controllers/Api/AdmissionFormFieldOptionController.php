<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdmissionFormFieldOption;

class AdmissionFormFieldOptionController extends Controller
{
    public function getOptions()
    {
        $options = AdmissionFormFieldOption::where('is_active', 'Y')
            ->orderBy('field_name')
            ->orderBy('display_order')
            ->get();

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