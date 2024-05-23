<?php

namespace App\Http\Controllers;

use App\Models\MaxTimeTransaction;
use Illuminate\Http\Request;

class MaxTimeTransactionController extends Controller
{
    public function index()
    {
        $maxTimeTransaction = MaxTimeTransaction::first();

        return response()->json([
            'success' => true,
            'message' => 'Max Time Transaction Successfully Retrieved!',
            'data' => $maxTimeTransaction
        ], 201);
    }

    public function update(Request $request)
    {
        $maxTimeTransaction = MaxTimeTransaction::first();

        $maxTimeTransaction->update([
            'max_time' => $request->max_time
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Max Time Transaction Successfully Updated!',
            'data' => $maxTimeTransaction
        ], 201);
    }
}
