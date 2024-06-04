<?php

namespace App\Http\Controllers;

use App\Models\ThresholdTime;
use App\Models\Transaction;
use App\Models\TransactionToken;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionTokenController extends Controller
{
    public function store($transaction_id){
        $transaction = Transaction::find($transaction_id);
        $threshold_time = ThresholdTime::first()->threshold_time;

        if($transaction == null){
            return [
                'success' => false,
                'message' => 'Transaction not found',
                'data' => null
            ];
        }else{
            if($transaction->transaction_from == 'CASHIER'){
                return [
                    'success' => false,
                    'message' => 'Transaction from cashier cannot be tokenized',
                    'data' => $transaction
                ];
            }else if($transaction->transaction_from == 'ONLINE'){
                $token = $this->generateToken();
                $transactionToken = TransactionToken::create([
                    'transaction_id' => $transaction_id,
                    'token' => $token,
                    'is_used' => false,
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+1day'))
                ]);

                return [
                    'success' => true,
                    'message' => 'Token has successfully created',
                    'data' => $transactionToken
                ];
            }else{
                return [
                    'success' => false,
                    'message' => 'Transaction from unknown source is invalid',
                    'data' => $transaction
                ];
            }
        }
    }

    public function show($id){
        $transactionToken = TransactionToken::with('transaction')->find($id);

        if($transactionToken == null){
            return [
                'success' => false,
                'message' => 'Token not found',
                'data' => null
            ];
        }else{
            return [
                'success' => true,
                'message' => 'Token found',
                'data' => $transactionToken
            ];
        }
    }

    public function generateToken(){
        $token = bin2hex(random_bytes(3));

        while(TransactionToken::where('token', $token)->where('created_at', Carbon::today())->first()){
            $token = bin2hex(random_bytes(3));
        }

        $token = strtoupper($token);
        return $token;
    }

    public function useToken(Request $request){
        $token = $request->token;
        $transactionToken = TransactionToken::where('token', $token)->with('transaction.detailTransaction.product')->first();
        if($transactionToken){  
            if($transactionToken->is_used){
                return response()->json([
                    'success' => false,
                    'message' => 'Token sudah ditukar!',
                    'data' => $transactionToken
                ], 400);
            }

            if($transactionToken->expires_at < Carbon::now()){
                return response()->json([
                    'success' => false,
                    'message' => 'Token sudah kadaluarsa!',
                    'data' => $transactionToken
                ], 400);
            }

            $transaction = $transactionToken->transaction;
            $transactionToken->update([
                'is_used' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token successfully used!',
                'data' => [
                    'transaction' => $transaction,
                    'transactionToken' => $transactionToken
                ]
            ], 200);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Token tidak ditemukan!',
                'data' => null
            ], 400);
        }
    }
}
