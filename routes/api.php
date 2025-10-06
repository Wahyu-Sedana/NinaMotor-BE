<?php

use App\Http\Controllers\AlamatController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\KategoriSparepartController;
use App\Http\Controllers\Api\ServisMotorController;
use App\Http\Controllers\Api\SparepartController;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\UsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

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

Route::post('/register', [UsersController::class, 'register']);
Route::post('/login', [UsersController::class, 'login']);

Route::post('/resend-verification', [UsersController::class, 'resendVerification']);

Route::post('/forgot-password', [UsersController::class, 'forgotPassword']);
Route::post('/reset-password', [UsersController::class, 'resetPasswordWithToken']);

Route::post('/midtrans/callback', [TransaksiController::class, 'midtransCallback']);
Route::get('/sparepart', [SparepartController::class, 'index']);
Route::post('/check-email', [UsersController::class, 'checkUserByEmail']);
Route::post('/reset-password', [UsersController::class, 'resetPassword']);
Route::post('/sparepart/kategori', [SparepartController::class, 'showDataByKategori']);
Route::get('/kategori', [KategoriSparepartController::class, 'index']);

Route::get('/sparepart/{id}', [SparepartController::class, 'show']);
// Route::post('transaksi/continue', [TransaksiController::class, 'continuePayment']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/sparepart', [SparepartController::class, 'store']);
    Route::put('/sparepart/{id}', [SparepartController::class, 'update']);
    Route::delete('/sparepart/{id}', [SparepartController::class, 'destroy']);


    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::post('/cart/remove', [CartController::class, 'remove']);

    Route::post('/bookmark', [BookmarkController::class, 'store']);
    Route::get('/bookmark', [BookmarkController::class, 'show']);
    Route::post('/bookmark/remove', [BookmarkController::class, 'destroy']);

    Route::get('/servis-motor', [ServisMotorController::class, 'index']);
    Route::post('/servis-motor', [ServisMotorController::class, 'store']);
    Route::get('/servis-motor/{id}', [ServisMotorController::class, 'show']);

    Route::post('/transaksi/create', [TransaksiController::class, 'createTransaction']);
    Route::get('/transaksi/list', [TransaksiController::class, 'getTransactionList']);

    Route::post('/profile', [UsersController::class, 'profile']);
    Route::post('/profile/update', [UsersController::class, 'updateProfile']);

    Route::post('/logout', [UsersController::class, 'logout']);

    Route::get('/alamat/user/{userId}', [AlamatController::class, 'getUserAddresses']);
    Route::get('/alamat/{id}', [AlamatController::class, 'show']);
    Route::post('/alamat', [AlamatController::class, 'store']);
    Route::put('/alamat/{id}', [AlamatController::class, 'update']);
    Route::delete('/alamat/{id}', [AlamatController::class, 'destroy']);
    Route::post('/alamat/{id}/set-default', [AlamatController::class, 'setDefault']);
});


Route::post('/test-notification', function (Request $request) {
    Log::info('Test Notification request received', $request->all());

    $request->validate([
        'fcm_token' => 'required|string',
        'title' => 'nullable|string',
        'body' => 'nullable|string',
    ]);

    $fcmToken = $request->fcm_token;
    $title = $request->title ?? 'Test Notification';
    $body = $request->body ?? 'Ini adalah pesan notifikasi test dari backend.';

    try {
        $factory = (new Factory)
            ->withServiceAccount(storage_path('app/ninamotor-53934-firebase-adminsdk-fbsvc-1008728fde.json'));
        $messaging = $factory->createMessaging();

        $message = CloudMessage::withTarget('token', $fcmToken)
            ->withNotification(Notification::create($title, $body))
            ->withData([
                'test_key' => 'test_value',
            ]);

        $messaging->send($message);

        Log::info('Notification sent successfully to token', ['token' => $fcmToken]);

        return response()->json([
            'success' => true,
            'message' => 'Notification sent successfully',
        ]);
    } catch (\Throwable $e) {
        Log::error('Failed to send test notification', [
            'error' => $e->getMessage(),
            'token' => $fcmToken,
            'title' => $title,
            'body' => $body,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to send notification',
            'error' => $e->getMessage(),
        ], 500);
    }
});
