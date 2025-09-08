<?php

namespace App\Http\Controllers;

use App\Models\GeneralSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GeneralSettingController extends Controller
{
    /**
     * Display the current general settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get the first settings record or create a default one if none exists
        $settings = GeneralSetting::firstOrCreate([]);

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update the specified general settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $settings = GeneralSetting::firstOrCreate([]);

        $validator = Validator::make($request->all(), [
            'language' => 'sometimes|string|max:10',
            'currency' => 'sometimes|string|max:3',
            'date_format' => 'sometimes|string|max:20',
            'default_contract_duration' => 'sometimes|integer|min:1',
            'renewal_reminder' => 'sometimes|integer|min:0',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'late_payment_alert' => 'sometimes|boolean',
            'grace_period' => 'sometimes|integer|min:0',
            'late_payment_fee' => 'sometimes|numeric|min:0',
            'maximum_commission' => 'sometimes|numeric|min:0',
            'maximum_sale' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $settings->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => $settings
        ]);
    }
}
