<?php

namespace App\Http\Middleware;

use App\Models\OpenCloseQueueLog;
use App\Models\QueuedQueue;
use App\Models\TempQueue;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckOpenCloseQueueLog
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $queueToday = OpenCloseQueueLog::whereDate('created_at', '!=', date('Y-m-d'))->where('closed_at', null)->get();

        if ($queueToday->count() > 0) {
            $queuedQueue = QueuedQueue::all();
            $tempQueue = TempQueue::all();

            if ($queuedQueue->count() > 0) {
                $queuedQueue->each->delete();
            }

            if ($tempQueue->count() > 0) {
                $tempQueue->each->delete();
            }

            if (auth()->user() != null) {
                $queueToday->each->update([
                    'closed_at' => date('Y-m-d H:i:s'),
                    'user_id_closer' => auth()->user()->id
                ]);
            }
        }

        return $next($request);
    }
}
