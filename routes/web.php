<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminDataCustomerController;
use App\Http\Controllers\AdminKategoriSparepartController;
use App\Http\Controllers\AdminServisMotorController;
use App\Http\Controllers\AdminSparepartController;
use App\Http\Controllers\AdminTransaksiController;
use App\Http\Controllers\AuthenticationController;
use App\Notifications\NewServisNotification;
use App\Notifications\NewTransactionNotification;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('admin.login');
});


// Route::get('/', [AdminController::class, 'index'])->name('admin.auth.login');
Route::prefix('admin')->name('admin.')->middleware('guest')->group(function () {
    Route::get('/login', [AuthenticationController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthenticationController::class, 'login']);
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Logout
    Route::post('/logout', [AuthenticationController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    // Sparepart
    Route::resource('sparepart', AdminSparepartController::class);

    // Kategori Sparepart
    Route::resource('kategori-sparepart', AdminKategoriSparepartController::class);

    // Customer
    Route::resource('customer', AdminDataCustomerController::class);

    // Transaksi
    Route::resource('/transaksi', AdminTransaksiController::class);
    Route::put('/transaksi/{transaksi}/update-status', [AdminTransaksiController::class, 'updateStatus'])->name('transaksi.update-status');

    // Servis Motor
    Route::get('servis', [AdminServisMotorController::class, 'index'])->name('servis.index');
    Route::get('servis/create', [AdminServisMotorController::class, 'create'])->name('servis.create');
    Route::post('servis', [AdminServisMotorController::class, 'store'])->name('servis.store');
    Route::get('servis/{servisMotor}', [AdminServisMotorController::class, 'show'])->name('servis.show');
    Route::get('servis/{servisMotor}/edit', [AdminServisMotorController::class, 'edit'])->name('servis.edit');
    Route::put('servis/{servisMotor}', [AdminServisMotorController::class, 'update'])->name('servis.update');
    Route::patch('servis/{servisMotor}', [AdminServisMotorController::class, 'update'])->name('servis.patch');
    Route::delete('servis/{servisMotor}', [AdminServisMotorController::class, 'destroy'])->name('servis.destroy');
    Route::patch('servis/{servisMotor}/status', [AdminServisMotorController::class, 'updateStatus'])->name('servis.updateStatus');

    // Profile
    Route::get('/profile', [AdminController::class, 'editProfile'])->name('auth.profile');
    Route::put('/profile', [AdminController::class, 'updateProfile'])->name('auth.update');

    // notif
    Route::get('/notifications/servis', function () {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json([], 403);
        }

        $notifications = auth()->user()->unreadNotifications
            ->where('type', NewServisNotification::class)
            ->values();

        return response()->json($notifications);
    });
    Route::post('/notifications/servis/mark-as-read', function () {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json([], 403);
        }

        auth()->user()->unreadNotifications
            ->where('type', NewServisNotification::class)
            ->markAsRead();

        return response()->json(['message' => 'Notifikasi Servis ditandai sudah dibaca.']);
    });

    Route::get('/notifications/transaksi', function () {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json([], 403);
        }

        $notifications = auth()->user()->unreadNotifications
            ->where('type', NewTransactionNotification::class)
            ->values();

        return response()->json($notifications);
    });
    Route::post('/notifications/transaksi/mark-as-read', function () {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json([], 403);
        }

        auth()->user()->unreadNotifications
            ->where('type', NewTransactionNotification::class)
            ->markAsRead();

        return response()->json(['message' => 'Notifikasi Transaksi ditandai sudah dibaca.']);
    });
    Route::get('/notifications/all', function () {
        $servis = auth()->user()->unreadNotifications->where('type', NewServisNotification::class)->get();
        $transaksi = auth()->user()->unreadNotifications->where('type', NewTransactionNotification::class)->get();

        $all = $servis->merge($transaksi)->sortByDesc('created_at')->values();

        return response()->json($all->values()->all());
    });
});
