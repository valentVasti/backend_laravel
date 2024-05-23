<?php

namespace App\Jobs;

use App\Events\NotifyNextOrDoneQueue;
use App\Http\Controllers\QueueController;
use App\Models\TempQueue;
use App\Models\ThresholdTime;
use App\Models\TransactionToken;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class CheckThresholdTime implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue_id;
    public $action;

    /**
     * Create a new job instance.
     */
    public function __construct($queue_id, $action)
    {
        $this->queue_id = $queue_id;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('CheckThresholdTime queue_id: ' . $this->queue_id . ' action: ' . $this->action);
        $thresholdTime = ThresholdTime::first();
        $queue = TempQueue::find($this->queue_id);
        $id_transaction = $queue->id_transaction;
        $transaction_token = TransactionToken::where('transaction_id', $id_transaction)->first();
        $delayUntil = Carbon::createFromFormat('Y-m-d H:i:s', $queue->updated_at->addMinutes($queue->mesin->durasi_penggunaan - $thresholdTime->threshold_time));
        Log::info('CheckThresholdTime queue_id: ' . $this->queue_id . ' delayUntil: ' . $delayUntil . ' action: ' . $this->action);
        
        if ($transaction_token) {
            if ($transaction_token->is_used) {
                // lanjut ke berikutnya, kasi delay menit - threshold
                ProcessingQueue::dispatch($this->queue_id, $this->action)->delay($delayUntil);
            } else {
                // udah lewatin thereshold time tapi token belum used, asumsi user belom dateng
                Log::info('Token belum digunakan, queue_id: ' . $this->queue_id . ' expired');  
                $queueController = new QueueController();
                $queueController->nextActionQueue($this->queue_id, 'expired');
                broadcast(new NotifyNextOrDoneQueue('expired queue', 'queue-channel'))->toOthers();
            }
        } else {
            ProcessingQueue::dispatch($this->queue_id, $this->action)->delay($delayUntil);
        }
    }
}
