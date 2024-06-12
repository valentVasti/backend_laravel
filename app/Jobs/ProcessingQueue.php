<?php

namespace App\Jobs;

use App\Events\NotifyNextOrDoneQueue;
use App\Http\Controllers\QueueController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessingQueue implements ShouldQueue
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
        $queueController = new QueueController();
        $response = $queueController->nextActionQueue($this->queue_id, $this->action);
        broadcast(new NotifyNextOrDoneQueue($response, 'queue-channel'))->toOthers();
    }
}
