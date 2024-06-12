<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MaxTimeTransactionController;
use App\Http\Controllers\MesinController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ThresholdTimeController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionTokenController;
use App\Http\Controllers\UserController;
use App\Models\ThresholdTime;
use App\Models\TransactionToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/notAuthenticated', [AuthController::class, 'notAuthenticated'])->name('notAuthenticated');
Route::post('/register', [UserController::class, 'register'])->name('user.register');
Route::post('/login/{page}', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/profile', [UserController::class, 'getUserLoggedIn'])->name('user.getLogIn');
    Route::get('/logout', [AuthController::class, 'logout'])->name('user.logout');

    // * User
    Route::get('/user', [UserController::class, 'getLoggedUser'])->name('user.getLoggedUser');
    Route::get('/user/{id}', [UserController::class, 'show'])->name('user.show');
    Route::get('/user/{role}/role', [UserController::class, 'getUserByRole'])->name('user.index');
    Route::post('/user', [UserController::class, 'create'])->name('user.create');
    Route::put('/user/update/{id}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/user/{id}', [UserController::class, 'destroy'])->name('user.destroy');

    // * Product
    Route::get('/product', [ProductController::class, 'index'])->name('product.index');
    Route::get('/product/{id}', [ProductController::class, 'show'])->name('product.show');
    Route::post('/product', [ProductController::class, 'create'])->name('product.create');
    Route::put('/product/{id}', [ProductController::class, 'update'])->name('product.update');
    Route::delete('/product/{id}', [ProductController::class, 'destroy'])->name('product.destroy');
    Route::get('/product/{id}/{status}', [ProductController::class, 'setStatus'])->name('product.setStatus');
    Route::get('/getActiveProduct', [ProductController::class, 'getActiveProduct'])->name('product.getActiveProduct');

    // * Transaction
    Route::middleware('maxTime')->group(function () {
        Route::post('/transaction', [TransactionController::class, 'create'])->name('transaction.create');
    });
    Route::get('/transaction', [TransactionController::class, 'index'])->name('transaction.index');
    Route::get('/transaction/{$id}', [TransactionController::class, 'show'])->name('transaction.show');
    Route::get('/getTransactionByUserLoggedIn', [TransactionController::class, 'getTransactionByUserLoggedIn'])->name('transaction.getTransactionByUserLoggedIn');
    // Route::post('/product/{id}', [ProductController::class, 'update'])->name('product.update');
    // Route::delete('/product/{id}', [ProductController::class, 'destroy'])->name('product.destroy');

    // * Mesin
    Route::get('/mesin', [MesinController::class, 'index'])->name('mesin.index');
    Route::get('/mesin/{id}', [MesinController::class, 'show'])->name('mesin.show');
    Route::post('/mesin', [MesinController::class, 'create'])->name('mesin.create');
    Route::put('/mesin/{id}', [MesinController::class, 'update'])->name('mesin.update');
    Route::delete('/mesin/{id}', [MesinController::class, 'destroy'])->name('mesin.destroy');
    Route::get('/mesin/{id}/{status}', [MesinController::class, 'setStatus'])->name('mesin.setStatus');
    Route::get('/getMachineByJenis/{jenis}', [MesinController::class, 'getActiveMachineByJenis'])->name('mesin.getMachineByJenis');

    // * Queue
    Route::get('/queue', [QueueController::class, 'getAll'])->name('queue.index');
    Route::get('/isServicesComplete/{id}', [QueueController::class, 'isServicesComplete'])->name('queue.check-services');
    Route::get('/getOnWorkQueueByMachine/{kode_mesin}', [QueueController::class, 'getOnWorkQueueByMachine'])->name('queue.getQueueByMachine');
    Route::get('/getAllQueueByStatus/{status}', [QueueController::class, 'getQueueByStatus'])->name('queue.getAllQueueByStatus');
    Route::get('/openCloseQueue/{action}', [QueueController::class, 'openQueue'])->name('queue.openQueue');
    Route::get('/getDoneQueue', [QueueController::class, 'getDoneQueue'])->name('queue.getDoneQueue');
    Route::get('/getFailedQueue', [QueueController::class, 'getFailedQueue'])->name('queue.getFailedQueue');
    Route::get('/getAllOpenCloseQueueLog', [QueueController::class, 'getAllOpenCloseQueueLog'])->name('queue.getAllOpenCloseQueueLog');
    Route::get('/isTodayLogOpened', [QueueController::class, 'isTodayQueueOpened'])->name('queue.getQueueByTransaction');
    Route::get('/nextActionQueue/{id}/{action}', [QueueController::class, 'nextActionQueue'])->name('queue.nextActionQueue');
    Route::get('/nextOrDoneQueue/{id}', [QueueController::class, 'notifyNextOrDoneQueue'])->name('queue.notifyNextOrDoneQueue');
    Route::get('/getQueueByUserLoggedIn', [QueueController::class, 'getQueueByUserLoggedIn'])->name('queue.getQueueByUserLoggedIn');

    // * Token
    Route::post('/token', [TransactionTokenController::class, 'useToken'])->name('token.useToken');

    // * Threshold Time
    Route::get('thresholdTime', [ThresholdTimeController::class, 'index'])->name('threshold_time.index');
    Route::put('thresholdTime', [ThresholdTimeController::class, 'update'])->name('threshold_time.update');

    // * Max Transaction Time
    Route::get('maxTimeTransaction', [MaxTimeTransactionController::class, 'index'])->name('max_time_transaction.index');
    Route::put('maxTimeTransaction', [MaxTimeTransactionController::class, 'update'])->name('max_time_transaction.update');

    // * Test
    Route::get('/test', [QueueController::class, 'test'])->name('queue.test');
});

Route::get('/getQueuedQueue', [QueueController::class, 'getQueuedQueue'])->name('queue.getQueuedQueue');
Route::get('/getQueueByLayanan/{layanan}', [QueueController::class, 'getQueueByLayanan'])->name('queue.getQueueByLayanan');
