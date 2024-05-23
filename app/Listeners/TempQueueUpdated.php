<?php

namespace App\Listeners;

use App\Events\NotifyNextOrDoneQueue;
use App\Http\Controllers\QueueController;
use App\Jobs\CheckThresholdTime;
use App\Jobs\ProcessingQueue;
use App\Models\ThresholdTime;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class TempQueueUpdated
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        Log::info('TempQueueUpdated event: ' . $event->id);
        $queueController = new QueueController();
        $response = $queueController->isServicesComplete($event->id);
        Log::info('TempQueueUpdated response: ' . $response);
        $thresholdTime = ThresholdTime::first();
        $thresholdTime = Carbon::createFromFormat('Y-m-d H:i:s', $event->updated_at->addMinutes($thresholdTime->threshold_time));
         
        switch ($response->getData(true)['data']['action']) {
            case 'Lanjutkan':
                CheckThresholdTime::dispatch($event->id, 'next')->delay($thresholdTime);
                break;

            case 'Selesai':
                CheckThresholdTime::dispatch($event->id, 'done')->delay($thresholdTime);
                break;

            default:
                break;
        }
        broadcast(new NotifyNextOrDoneQueue($response, 'queue-channel'))->toOthers();
    }
}
