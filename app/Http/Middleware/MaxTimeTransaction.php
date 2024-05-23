<?php

namespace App\Http\Middleware;

use App\Models\MaxTimeTransaction as ModelsMaxTimeTransaction;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MaxTimeTransaction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maxTimeTransaction = ModelsMaxTimeTransaction::first();
        $timeNow = Carbon::now();
        $maxTime = $maxTimeTransaction->max_time;

        if ($timeNow->format('H:i:s') > $maxTime) {
            return response()->json([
                'success' => false,
                'message' => 'Max Time Transaction has passed, you cannot make a transaction!',
                'data' => [
                    'max_time' => 'Max Time Transaction has passed, you cannot make a transaction!',
                ]
            ], 400);
        } else {
            return $next($request);
        }
    }
}
