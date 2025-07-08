<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class LogsController extends Controller
{
    public function index(){
        $logs = ActivityLog::all();
        return response()->json($logs);
    }
}
