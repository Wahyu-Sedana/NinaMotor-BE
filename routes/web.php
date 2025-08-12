<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminDataCustomerController;
use App\Http\Controllers\AdminKategoriSparepartController;
use App\Http\Controllers\AdminServisMotorController;
use App\Http\Controllers\AdminSparepartController;
use App\Http\Controllers\AdminTransaksiController;
use App\Http\Controllers\AuthenticationController;
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
    Route::get('/transaksi/{transaksi}/items', [AdminTransaksiController::class, 'items'])
        ->name('admin.transaksi.items');

    // Servis Motor
    Route::resource('servis', AdminServisMotorController::class);
});
