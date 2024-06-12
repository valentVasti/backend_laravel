<?php

namespace App\Listeners;

use App\Events\NotifyNextOrDoneQueue;
use App\Http\Controllers\QueueController;
use App\Jobs\CheckThresholdTime;
use App\Jobs\ProcessingQueue;
use App\Models\ThresholdTime;
use App\Models\TransactionToken;
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
        if ($event->status != 'IDLE') {
            $queueController = new QueueController();
            $response = $queueController->isServicesComplete($event->id);
            $thresholdTime = ThresholdTime::first();
            $thresholdTime = Carbon::createFromFormat('Y-m-d H:i:s', $event->updated_at->addMinutes($thresholdTime->threshold_time));
            $transactionToken = TransactionToken::where('transaction_id', $event->id_transaction)->first();
            Log::info('Start Time: '.$event->updated_at.' | Check Threshold Time Until: '.$thresholdTime);

            if ($transactionToken != null) {
                $transactionToken->expires_at = $thresholdTime;
                $transactionToken->save();
            }

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
}
