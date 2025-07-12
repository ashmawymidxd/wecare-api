<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GeneralSettings;

class GeneralSettingsController extends Controller
{
    public function index(){
        $settings = GeneralSettings::all();
        return response()->json($settings);
    }
}
