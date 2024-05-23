<?php

namespace App\Http\Controllers;

use App\Models\ThresholdTime;
use Illuminate\Http\Request;

class ThresholdTimeController extends Controller
{
    public function index()
    {
        $threshold_time = ThresholdTime::first();
        
        return response()->json([
            'success' => true,
            'message' => 'Threshold time has been successfully retrieved',
            'data' => $threshold_time
        ]);
    }

    public function edit(Request $request)
    {
        $threshold_time = ThresholdTime::first();
        $threshold_time->threshold_time = $request->threshold_time;
        $threshold_time->save();

        return response()->json([
            'success' => true,
            'message' => 'Threshold time has been successfully updated',
            'data' => $threshold_time
        ]);
    }
}
