<?php

namespace App\Http\Controllers;

use App\Events\NotifyFrontend;
use App\Events\NotifyOnWorkQueue;
use App\Events\NotifyQueuedQueue;
use App\Models\DetailTransaction;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use ElephantIO\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PDO;

class TransactionController extends Controller
{
    public function index()
    {
        $transaction = Transaction::with('user', 'karyawan', 'detailTransaction.product')->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'message' => 'All transactions successfully retrieved!',
            'data' => $transaction
        ], 200);
    }

    public function show($id)
    {
        $transaction = Transaction::find($id);

        if ($transaction == null) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found!',
                'data' => $transaction
            ], 404);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Transaction successfully retrieved!',
                'data' => $transaction
            ], 200);
        }
    }

    public function create(Request $request)
    {
        $karyawan = Auth::user();

        if ($karyawan->role != 'KARYAWAN' && $karyawan->role != 'ADMIN') {
            $transaction_from = 'ONLINE';
        } else {
            $transaction_from = 'CASHIER';
        }

        $validateData = Validator::make(
            $request->all(),
            [
                'user_id' => 'required',
                'paying_method' => 'required|string|in:CASH,CASHLESS',
                'total' => 'required|numeric',
                'paid_sum' => 'required|numeric',
                'item' => 'required|json'
            ],
            [
                'user_id.required' => 'Pilih konsumen!',
                'paying_method.required' => 'Metode pembayaran wajib diisi!',
                'paid_sum.required' => 'Jumlah bayar wajib diisi!',
                'item.required' => 'Pilih min. 1 item!'
            ]
        );

        if ($validateData->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validateData->errors()
            ], 400);
        }

        $user = User::find($request->user_id);

        if ($user == null) {
            return response()->json([
                'success' => false,
                'message' => 'User not found!'
            ], 404);
        } else {
            $change = $request->paying_method == 'CASHLESS' ? 0 : ($request->paid_sum - $request->total);

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'karyawan_id' => $karyawan->id,
                'paying_method' => $request->paying_method,
                'total' => $request->total,
                'paid_sum' => $request->paid_sum,
                'change' => $change,
                'transaction_from' => $transaction_from
            ]);

            $items = json_decode($request->item);

            $detailTransaction = [];
            $services = [];

            foreach ($items as $data) {
                $product = Product::find($data->id);

                if ($product->product_name == "Cuci" || $product->product_name == "Kering") {
                    array_push($services, $product->product_name);
                }

                $total_price = $data->qty * $product->price;

                $detailTransactionInserted = DetailTransaction::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $data->qty,
                    'total_price' => $total_price
                ]);

                array_push($detailTransaction, $detailTransactionInserted);
            }

            $queueController = new QueueController();
            $createdQue = $queueController->createTempQueue($transaction->id, $services);

            $storeToken = [];

            if ($transaction->transaction_from == 'ONLINE') {
                $transactionTokenController = new TransactionTokenController();
                $storeToken = $transactionTokenController->store($transaction->id);
            }

            if (!$createdQue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Queue failed!',
                    'data' => [
                        'transaction_data' => $transaction,
                        'detail_transaction' => $detailTransaction
                    ]
                ], 400);
            } else if ($createdQue['status'] == 'ONWORK') {
                broadcast(new NotifyOnWorkQueue('Machine ' . $createdQue['mesin']['kode_mesin'] . ' updated!', 'machine-' . $createdQue['mesin']['kode_mesin']))->toOthers();
            } else if ($createdQue['queue_id'] != null) {
                broadcast(new NotifyQueuedQueue('New Queued!', 'queued'))->toOthers();
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaction success!',
                'data' => [
                    'transaction_data' => $transaction,
                    'detail_transaction' => $detailTransaction,
                    'queue' => $createdQue,
                    'token' => $storeToken
                ]
            ], 200);
        }
    }

    public function getTransactionByUserLoggedIn()
    {
        $user = Auth::user();
        $transaction = Transaction::with('detailTransaction.product')->where('user_id', $user->id)->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'message' => 'Transaction data successfully retrieved!',
            'data' => $transaction
        ], 200);
    }
}
