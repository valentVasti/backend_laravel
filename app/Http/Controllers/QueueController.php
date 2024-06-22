<?php

namespace App\Http\Controllers;

use App\Models\DoneQueue;
use App\Models\Mesin;
use App\Models\OpenCloseQueueLog;
use App\Models\QueuedQueue;
use App\Models\TempQueue;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use App\Events\NotifyNextOrDoneQueue;
use App\Models\FailedQueue;
use Carbon\Carbon;
use Dflydev\DotAccessData\Data;
use Illuminate\Support\Facades\Log;

class QueueController extends Controller
{
    public function getAll()
    {
        $queuedQueue = TempQueue::with('mesin', 'transaction.transactionToken')->get();

        return response()->json([
            'success' => true,
            'message' => 'All queue successfully retrieved!',
            'data' => $queuedQueue
        ], 200);
    }

    public function openCloseQueue($action)
    {
        $todayTimestamp = date('Y-m-d H:i:s');

        if ($action == 'open') {
            $checkQueue = OpenCloseQueueLog::whereNot('opened_at', null)->where('closed_at', null)->get();

            // * check if queue already opened
            if (count($checkQueue) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Queue already opened!',
                ], 400);
            }

            $idleMachine = Mesin::where('status_maintenance', 0)->orderBy('kode_mesin', 'asc')->get();
            $user_id = Auth::user()->id;

            // * check if no idle machine found, either pengering or pencuci
            if (count($idleMachine) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No idle machine found!',
                ], 404);
            }

            foreach ($idleMachine as $machine) {
                $layanan = $machine->jenis_mesin == 'PENCUCI' ? 'CUCI' : 'KERING';

                TempQueue::create([
                    'id_mesin' => $machine->id,
                    'nomor_antrian' => 0,
                    'layanan' => $layanan,
                    'status' => 'IDLE',
                ]);
            }

            $createdQueueList = TempQueue::all();

            // * check if created queue list and idle machine list is not equal
            if (count($idleMachine) != count($createdQueueList)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create queue!',
                ], 500);
            }

            $openCloseQueueLog = OpenCloseQueueLog::create([
                'user_id_opener' => $user_id,
                'opened_at' => $todayTimestamp
            ]);

            broadcast(new NotifyNextOrDoneQueue('open queue', 'queue-channel'))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Queue successfully opened!',
                'data' => $openCloseQueueLog
            ], 200);
        } else if ($action == 'close') {
            $queueToday = OpenCloseQueueLog::whereDate('created_at', date('Y-m-d'))->where('closed_at', null)->first();
            $queuedQueue = QueuedQueue::all();
            $checkTempQueueNull = TempQueue::whereNot('id_transaction', null)->where('status', 'ONWORK')->get();
            $tempQueue = TempQueue::all();

            // * check if today queue not found
            if ($queueToday == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Queue not found!',
                ], 404);
            }

            // * check if there is still queued queue
            if ($queuedQueue->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Masih ada antrian yang diantrikan!',
                ], 400);
            }

            // * check if there is still queue on work
            if ($checkTempQueueNull->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Masih ada antrian berjalan!',
                ], 400);
            }


            $tempQueue->each->delete();

            $queueToday->update([
                'closed_at' => date('Y-m-d H:i:s'),
                'user_id_closer' => auth()->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Queue successfully closed!',
                'data' => $queueToday
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid action!',
            ], 400);
        }
    }

    public function getAllOpenCloseQueueLog()
    {
        $queueLog = OpenCloseQueueLog::with('userOpener', 'userCloser')->get();

        return response()->json([
            'success' => true,
            'message' => 'All queue log successfully retrieved!',
            'data' => $queueLog
        ], 200);
    }

    public function isTodayQueueOpened()
    {
        $queueToday = OpenCloseQueueLog::whereDate('created_at', date('Y-m-d'))->first();

        if ($queueToday == null) {
            $opened_status = false;
            $closed_status = false;
        } else {
            $opened_status = $queueToday->opened_at != null ? true : false;
            $closed_status = $queueToday->closed_at != null ? true : false;
        }

        return response()->json([
            'success' => true,
            'message' => 'Today queue log successfully retrieved!',
            'data' => [
                'opened' => $opened_status,
                'closed' => $closed_status
            ]
        ], 200);
    }

    public function createTempQueue($id_transaction, $services)
    {
        switch (count($services)) {
            case 1: // cuma ngeringin aja
                if ($services[0] == 'Kering') {
                    $result = $this->getAvailableMachine('KERING');
                    $queueNow = TempQueue::where('id_transaction', null)->where('layanan', 'KERING')->get();
                } else if ($services[0] == 'Cuci') {
                    $queueNow = TempQueue::where('id_transaction', null)->where('layanan', 'CUCI')->get();
                    $result = $this->getAvailableMachine('CUCI');
                } else {
                    return false;
                }
                break;

            case 2: // komplit (cuci + kering)
                $result = $this->getAvailableMachine('CUCI');
                $queueNow = TempQueue::where('id_transaction', null)->where('layanan', 'CUCI')->get();
                # code...
                break;

            default:
                # code...
                break;
        }

        if ($queueNow->count() > 0) {
            // * update queue with id from getAvailableMachine
            $queue = TempQueue::with('mesin')->find($result['queue']->id);
            $queue->update([
                'id_transaction' => $id_transaction,
                'nomor_antrian' => $result['nomor_antrian'],
                'status' => 'ONWORK'
            ]);
        } else {
            // * create new queue in queued queue
            $queue = QueuedQueue::create([
                'queue_id' => $result['queue']->id,
                'nomor_antrian' => $result['nomor_antrian'],
                'transaction_id' => $id_transaction
            ]);
            $queue = QueuedQueue::with('todayQueue.mesin', 'transaction.transactionToken')->find($queue->id);
        }

        return $queue;
    }

    public function nextActionQueue($id, $action)
    {
        $queue = TempQueue::with('mesin')->find($id);
        $queuedQueue = QueuedQueue::where('queue_id', $id)->first();
        $doneQueue = DoneQueue::where('id_transaction', $queue->id_transaction)->first();

        if ($action == 'done') {
            $done_at = date('Y-m-d H:i:s');

            if ($doneQueue != null) {
                // update done queue, brrti dia done dari next
                $doneQueue->update([
                    $queue->mesin->jenis_mesin == 'PENCUCI' ? 'pencuci' : 'pengering' => $queue->mesin->id,
                    'done_at' => $done_at
                ]);
            } else {
                $mesinColumn = $queue->mesin->jenis_mesin == 'PENCUCI' ? 'pencuci' : 'pengering';
                // create done queue, brrti dia done dari idle
                $doneQueue = DoneQueue::create([
                    'id_transaction' => $queue->id_transaction,
                    $mesinColumn => $queue->mesin->id,
                    'nomor_antrian' => $queue->nomor_antrian,
                    'done_at' => $done_at
                ]);
            }

            if ($queuedQueue != null) {
                // ada antrian di queuedqueue, lsg isi sama yang terbaru
                $queue->update([
                    'transaction_id' => $queuedQueue->transaction_id,
                    'nomor_antrian' => $queuedQueue->nomor_antrian,
                    'status' => 'ONWORK'
                ]);
                $queuedQueue->delete();
            } else {
                // tidak ada antrian di queuedqueue, lsg kosongin
                $queue->update([
                    'id_transaction' => null,
                    'nomor_antrian' => 0,
                    'status' => 'IDLE'
                ]);
            }
            return response()->json([
                'success' => true,
                'message' => 'Queue successfully done!',
                'data' => $doneQueue
            ], 200);
        } else if ($action == 'next') {
            $emptyQueue = $this->getAvailableMachine('KERING')['queue'];

            $doneQueue = DoneQueue::create([
                'id_transaction' => $queue->id_transaction,
                'pencuci' => $queue->mesin->id,
                'nomor_antrian' => $queue->nomor_antrian,
                'done_at' => null
            ]);

            if ($emptyQueue->id_transaction == null) {
                // next services kosong antriannya, lsg update di pengering
                $emptyQueue->update([
                    'id_transaction' => $queue->id_transaction,
                    'nomor_antrian' => $queue->nomor_antrian,
                    'status' => 'ONWORK'
                ]);

                if ($queuedQueue != null) {
                    // ada antrian di mesin cuci yang barusan dipindah ke pengering
                    $queue->update([
                        'id_transaction' => $queuedQueue->transaction_id,
                        'nomor_antrian' => $queuedQueue->nomor_antrian,
                        'status' => 'ONWORK'
                    ]);
                    $queuedQueue->delete();
                } else {
                    // tidak ada antrian di queuedqueue, lsg kosongin
                    $queue->update([
                        'id_transaction' => null,
                        'nomor_antrian' => 0,
                        'status' => 'IDLE'
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Queue successfully onwork to next service!',
                    'data' => [
                        'action' => $action,
                        'old_queue' => $queue,
                        'new_queue' => $emptyQueue
                    ]
                ], 200);
            } else {

                // next services ada antriannya, masukin ke queued
                $newQue = QueuedQueue::create([
                    'queue_id' => $emptyQueue->id,
                    'nomor_antrian' => $queue->nomor_antrian,
                    'transaction_id' => $queue->id_transaction
                ]);

                if ($queuedQueue != null) {
                    $queue->update([
                        'id_transaction' => $queuedQueue->transaction_id,
                        'nomor_antrian' => $queuedQueue->nomor_antrian,
                        'status' => 'ONWORK'
                    ]);
                    $queuedQueue->delete();
                    $queuedQueue = $newQue;
                } else {
                    // tidak ada antrian di queuedqueue, lsg kosongin
                    $queue->update([
                        'id_transaction' => null,
                        'nomor_antrian' => 0,
                        'status' => 'IDLE'
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Queue successfully queued to next service!',
                    'data' => [
                        'action' => $action,
                        'old_queue' => $queue,
                        'new_queue' => $queuedQueue
                    ]
                ], 200);
            }
        } else if ($action == 'expired') {
            $done_at = date('Y-m-d H:i:s');

            $failedQueue = FailedQueue::create([
                'transaction_id' => $queue->id_transaction,
                'nomor_antrian' => $queue->nomor_antrian,
                'failed_at' => $done_at
            ]);

            if ($queuedQueue != null) {
                // ada antrian di mesin cuci yang barusan dicancel karna expired
                $queue->update([
                    'id_transaction' => $queuedQueue->transaction_id,
                    'nomor_antrian' => $queuedQueue->nomor_antrian,
                    'status' => 'ONWORK'
                ]);
                $queuedQueue->delete();
            } else {
                $queueStuck = QueuedQueue::with('todayQueue.mesin', 'transaction.transactionToken')->get();
                $queueStuck = $queueStuck->where('todayQueue.layanan', $queue->layanan);
                $queueStuck = $queueStuck->where('transaction.transactionToken.is_used', 1)->concat($queueStuck->where('transaction.transactionToken', null))->first();

                if ($queueStuck != null) {
                    $queue->update([
                        'id_transaction' => $queueStuck->transaction_id,
                        'nomor_antrian' => $queueStuck->nomor_antrian,
                        'status' => 'ONWORK'
                    ]);
                    $queuedQueueStuck = QueuedQueue::find($queueStuck->id);
                    $queuedQueueStuck->delete();
                } else {
                    // tidak ada antrian di queuedqueue, lsg kosongin
                    $queue->update([
                        'id_transaction' => null,
                        'nomor_antrian' => 0,
                        'status' => 'IDLE'
                    ]);
                }
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid action!',
            ], 400);
        }
    }

    public function getAvailableMachine($service)
    {
        switch ($service) {
            case 'CUCI':
                $emptyQueue = TempQueue::where('status', 'IDLE')->where('layanan', 'CUCI')->orderBy('updated_at', 'asc')->first();
                break;

            case 'KERING':
                $emptyQueue = TempQueue::where('status', 'IDLE')->where('layanan', 'KERING')->orderBy('updated_at', 'asc')->first();
                break;

            default:
                break;
        }

        if ($emptyQueue == null) {
            $queueToday = QueuedQueue::orderBy('updated_at', 'asc')->get();
            $emptyQueue = TempQueue::whereNotIn('id', $queueToday->pluck('queue_id'))->where('layanan', $service)->orderBy('updated_at', 'asc')->first();

            if ($emptyQueue == null) {
                $queuedQueue = QueuedQueue::with('todayQueue')->whereHas('todayQueue', function ($query) use ($service) {
                    $query->where('layanan', $service);
                })->orderBy('updated_at', 'desc')->first();

                $emptyQueue = TempQueue::where('id', $queuedQueue->queue_id + 1)->where('layanan', $service)->first();

                if ($emptyQueue == null) {
                    $emptyQueue = TempQueue::where('layanan', $service)->orderBy('updated_at', 'asc')->first();
                }
            }
        }

        $queuedMaxNomorAntrian = QueuedQueue::max('nomor_antrian');
        $onWorkMaxNomorAntrian = TempQueue::max('nomor_antrian');
        $doneQueueMaxNomorAntrian = DoneQueue::whereDate('created_at', date('Y-m-d'))->max('nomor_antrian');
        $failedQueueMaxNomorAntrian = FailedQueue::whereDate('created_at', date('Y-m-d'))->max('nomor_antrian');

        $nomor_antrian = max([$queuedMaxNomorAntrian, $onWorkMaxNomorAntrian, $doneQueueMaxNomorAntrian, $failedQueueMaxNomorAntrian]);

        $result = [
            'nomor_antrian' => $nomor_antrian + 1,
            'queue' => $emptyQueue
        ];

        return $result;
    }

    public function isServicesComplete($id_queue)
    {
        $queue = TempQueue::find($id_queue);

        if ($queue->id_transaction == null) {
            return response()->json([
                'success' => true,
                'message' => 'Queue not yet have transaction!',
                'data' => [
                    'action' => '',
                ]
            ], 200);
        }

        $transaction = Transaction::with('detailTransaction.product')->find($queue->id_transaction);
        $detailTransaction = $transaction->detailTransaction;

        foreach ($detailTransaction as $item) {
            if ($item->product->product_name == 'Cuci' || $item->product->product_name == 'Kering') {
                $services[] = $item->product->product_name;
            }
        }

        $result = count($services) == 2 ?
            [
                'action' => 'Lanjutkan',
            ] : [
                'action' => 'Selesai',
            ];

        if ($queue->layanan == 'KERING') {
            $result = [
                'action' => 'Selesai',
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Queue successfully checked!',
            'data' => $result,
            'services' => $services
        ], 200);
    }

    public function getOnWorkQueueByMachine($kode_mesin)
    {
        $mesin = Mesin::where('kode_mesin', $kode_mesin)->first();
        $queue = TempQueue::where('id_mesin', $mesin->id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Queue successfully retrieved!',
            'data' => $queue
        ], 200);
    }

    public function getQueueByStatus($status)
    {
        $queue = TempQueue::with('mesin')->where('status', $status)->get();

        return response()->json([
            'success' => true,
            'message' => 'All queue with status ' . $status . ' successfully retrieved!',
            'data' => $queue
        ], 200);
    }

    public function getQueuedQueue()
    {
        $queue = QueuedQueue::with('todayQueue.mesin', 'transaction.transactionToken')->get();

        return response()->json([
            'success' => true,
            'message' => 'All queue successfully retrieved!',
            'data' => $queue
        ], 200);
    }

    public function getDoneQueue()
    {
        $queue = DoneQueue::whereDate('created_at', date('Y-m-d'))->with('transaction.detailTransaction', 'pencuci', 'pengering')->orderBy('done_at')->get();

        return response()->json([
            'success' => true,
            'message' => 'All done queue successfully retrieved!',
            'data' => $queue
        ], 200);
    }

    public function getFailedQueue()
    {
        $queue = FailedQueue::whereDate('created_at', date('Y-m-d'))->orderBy('failed_at')->get();

        return response()->json([
            'success' => true,
            'message' => 'All done queue successfully retrieved!',
            'data' => $queue
        ], 200);
    }

    public function getQueueByLayanan($layanan)
    {
        $queue = TempQueue::with('mesin', 'transaction.transactionToken')->where('layanan', $layanan)->get();

        return response()->json([
            'success' => true,
            'message' => 'All queue with layanan ' . $layanan . ' successfully retrieved!',
            'data' => $queue
        ], 200);
    }

    public function getQueueByUserLoggedIn()
    {

        $user = Auth::user();
        $result = [];

        $transaction = Transaction::where('user_id', $user->id)->whereDate('created_at', Carbon::today())->orderByDesc('created_at')->get();

        foreach ($transaction as $data) {
            $queue = TempQueue::with('mesin', 'transaction.detailTransaction.product', 'transaction.transactionToken')->where('id_transaction', $data->id)->first();
            if ($queue == null) {
                $queue = QueuedQueue::with('todayQueue.mesin', 'transaction.detailTransaction.product', 'transaction.transactionToken')->where('transaction_id', $data->id)->first();

                if ($queue == null) {
                    $data = [];
                } else {
                    $data = [
                        'status' => 'QUEUED',
                        'id_transaction' => $queue->transaction_id,
                        'no_antrian' => $queue->nomor_antrian,
                        'transaction' => $queue->transaction,
                    ];
                }
            } else {
                $data = [
                    'status' => 'ONWORK',
                    'id_transaction' => $queue->id_transaction,
                    'no_antrian' => $queue->nomor_antrian,
                    'transaction' => $queue->transaction,
                ];
            }
            if ($data != []) {
                array_push($result, $data);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'All queue by user logged in successfully retrieved!',
            'data' => $result
        ], 200);
    }

    public function test()
    {
        $queue = QueuedQueue::with('todayQueue.mesin', 'transaction.transactionToken')
            // ->whereHas('transaction.transactionToken', fn ($query) => $query->where('is_used', 1))
            ->get();

        $queue = $queue->where('todayQueue.layanan', 'CUCI');
        $queue = $queue->where('transaction.transactionToken.is_used', 1)->concat($queue->where('transaction.transactionToken', null))->first();

        // if ($queue->layanan == 'CUCI') {
        //     $queuedQueue = QueuedQueue::with('todayQueue', 'transaction.transactionToken')
        //         ->whereHas('todayQueue', fn ($query) => $query->where('layanan', 'CUCI'))
        //         ->whereHas('transaction.transactionToken', fn ($query) => $query->where('is_used', 1))
        //         ->orderBy('nomor_antrian', 'asc')
        //         ->first();
        //     Log::info('Kering Queued:', [$queuedQueue]);
        // } else if ($queue->layanan == 'KERING') {
        //     $queuedQueue = QueuedQueue::with('todayQueue.transactionToken')->whereHas('todayQueue', fn ($query) => $query->where('layanan', 'KERING'))->orderBy('nomor_antrian', 'asc')->first();
        //     Log::info('Kering Queued:', [$queuedQueue]);
        // }

        return response()->json([
            'success' => true,
            'message' => 'Test success!',
            'data' => $queue
        ], 200);
    }
}
