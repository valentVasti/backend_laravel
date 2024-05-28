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
use Illuminate\Support\Facades\Log;

class QueueController extends Controller
{
    public function getAll()
    {
        $queuedQueue = TempQueue::with('mesin')->get();

        return response()->json([
            'success' => true,
            'message' => 'All queue successfully retrieved!',
            'data' => $queuedQueue
        ], 200);
    }

    public function openQueue($action)
    {
        // TODO buat di frontend sekalian open close queue, sama tampilin temp_queue di queueWindow
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
        $queueLog = OpenCloseQueueLog::all();

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

            $queue = QueuedQueue::with('todayQueue.mesin')->find($queue->id);
        }

        return $queue;
    }

    public function nextActionQueue($id, $action)
    {
        Log::info('Get In into nextActionQueue function' . $id . ' ' . $action);
        $queue = TempQueue::with('mesin')->find($id);
        $done_at = date('Y-m-d H:i:s');
        $queuedQueue = QueuedQueue::where('queue_id', $id)->first();
        $doneQueue = DoneQueue::where('id_transaction', $queue->id_transaction)->first();
        Log::info('Queue: ' . $queue);
        Log::info('Queued Queue: ' . $queuedQueue);
        Log::info('Done Queue: ' . $doneQueue);

        if ($action == 'done') {
            Log::info('Get In into done queue');
            if ($doneQueue != null) {
                // update done queue, brrti dia done dari next
                Log::info('Updated Done from Done');
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
                Log::info('Created Done from Done');
                Log::info('Done Queue: ' . $doneQueue);
                Log::info('Mesin Column: ' . $queue->mesin->kode_mesin);
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
            Log::info('Get In into next queue');
            $emptyQueue = $this->getAvailableMachine('KERING')['queue'];

            $doneQueue = DoneQueue::create([
                'id_transaction' => $queue->id_transaction,
                'pencuci' => $queue->mesin->id,
                'nomor_antrian' => $queue->nomor_antrian,
                'done_at' => null
            ]);

            Log::info('Created Done from Next');
            Log::info('Done Queue: ' . $doneQueue);
            Log::info('Mesin Column: ' . $queue->mesin->kode_mesin);

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
                // tidak ada antrian di queuedqueue, lsg kosongin
                $queue->update([
                    'id_transaction' => null,
                    'nomor_antrian' => 0,
                    'status' => 'IDLE'
                ]);
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
            // antrian onwork penuh, tapi belum ada antrian lain di queued
            $queueToday = QueuedQueue::orderBy('updated_at', 'asc')->get();
            $emptyQueue = TempQueue::whereNotIn('id', $queueToday->pluck('queue_id'))->where('layanan', $service)->orderBy('updated_at', 'asc')->first();

            if ($emptyQueue == null) {
                // antrian onwork penuh, dan antrian queued juga penuh
                // harus cari mesin yang paling cepat selesai
                $queuedQueue = QueuedQueue::with('todayQueue')->whereHas('todayQueue', function ($query) use ($service) {
                    $query->where('layanan', $service);
                })->orderBy('updated_at', 'desc')->first();

                // $todayQueByServices = TempQueue::where('layanan', $service)->get();
                $emptyQueue = TempQueue::where('id', $queuedQueue->queue_id + 1)->where('layanan', $service)->first();

                if ($emptyQueue == null) {
                    $emptyQueue = TempQueue::where('layanan', $service)->orderBy('updated_at', 'asc')->first();
                }
            }
        }

        $queuedMaxNomorAntrian = QueuedQueue::max('nomor_antrian');
        $onWorkMaxNomorAntrian = TempQueue::max('nomor_antrian');
        $doneQueueMaxNomorAntrian = DoneQueue::where('created_at', date('Y-m-d H:i:s'))->max('nomor_antrian');
        $failedQueueMaxNomorAntrian = FailedQueue::where('created_at', date('Y-m-d H:i:s'))->max('nomor_antrian');

        if ($queuedMaxNomorAntrian > $onWorkMaxNomorAntrian && $queuedMaxNomorAntrian > $doneQueueMaxNomorAntrian && $queuedMaxNomorAntrian > $failedQueueMaxNomorAntrian) {
            $nomor_antrian = $queuedMaxNomorAntrian;
        } else if ($onWorkMaxNomorAntrian > $queuedMaxNomorAntrian && $onWorkMaxNomorAntrian > $doneQueueMaxNomorAntrian && $onWorkMaxNomorAntrian > $failedQueueMaxNomorAntrian) {
            $nomor_antrian = $onWorkMaxNomorAntrian;
        } else if ($doneQueueMaxNomorAntrian > $queuedMaxNomorAntrian && $doneQueueMaxNomorAntrian > $onWorkMaxNomorAntrian && $doneQueueMaxNomorAntrian > $failedQueueMaxNomorAntrian) {
            $nomor_antrian = $doneQueueMaxNomorAntrian;
        } else if ($failedQueueMaxNomorAntrian > $queuedMaxNomorAntrian && $failedQueueMaxNomorAntrian > $onWorkMaxNomorAntrian && $failedQueueMaxNomorAntrian > $doneQueueMaxNomorAntrian) {
            $nomor_antrian = $failedQueueMaxNomorAntrian;
        } else {
            $nomor_antrian = 0;
        }

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
                    'color' => 'default'
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
                'color' => 'primary'
            ] : [
                'action' => 'Selesai',
                'color' => 'success'
            ];

        if ($queue->layanan == 'KERING') {
            $result = [
                'action' => 'Selesai',
                'color' => 'success'
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
        $queue = QueuedQueue::with('todayQueue.mesin')->get();

        return response()->json([
            'success' => true,
            'message' => 'All queue successfully retrieved!',
            'data' => $queue
        ], 200);
    }

    public function getDoneQueue()
    {
        $queue = DoneQueue::with('transaction.detailTransaction', 'pencuci', 'pengering')->orderBy('done_at')->get();

        return response()->json([
            'success' => true,
            'message' => 'All done queue successfully retrieved!',
            'data' => $queue
        ], 200);
    }

    public function getQueueByLayanan($layanan)
    {
        $queue = TempQueue::with('mesin')->where('layanan', $layanan)->get();

        return response()->json([
            'success' => true,
            'message' => 'All queue with layanan ' . $layanan . ' successfully retrieved!',
            'data' => $queue
        ], 200);
    }

    public function notifyNextOrDoneQueue($id)
    {
        broadcast(new NotifyNextOrDoneQueue('NotifyFrontend', 'queue-' . $id))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Event successfully broadcasted!',
            'data' => [
                'channel' => 'queue-' . $id
            ]
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
}
