<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminDataCustomerController;
use App\Http\Controllers\AdminKategoriSparepartController;
use App\Http\Controllers\AdminServisMotorController;
use App\Http\Controllers\AdminSparepartController;
use App\Http\Controllers\AdminTransaksiController;
use App\Http\Controllers\Api\UsersController;
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

Route::get('/verify-email/{token}', [UsersController::class, 'verifyEmail'])->name('verify.email');

Route::post('/admin/subscribe-topic', [AdminServisMotorController::class, 'subscribeToTopic'])->name('admin.subscribe-topic');
Route::get('/admin/servis/recent', [AdminServisMotorController::class, 'getRecentServis'])->name('admin.servis.recent');
Route::get('/admin/servis/unread-count', [AdminServisMotorController::class, 'getUnreadCount'])->name('admin.servis.unread-count');

Route::get('/admin/notifications/unread', [AdminServisMotorController::class, 'getUnreadNotifications'])->name('admin.notifications.unread');
Route::get('/admin/notifications/count', [AdminServisMotorController::class, 'getUnreadCount'])->name('admin.notifications.count');
Route::post('/admin/notifications/{id}/read', [AdminServisMotorController::class, 'markAsRead'])->name('admin.notifications.read');
Route::post('/admin/notifications/read-all', [AdminServisMotorController::class, 'markAllAsRead'])->name('admin.notifications.read-all');


Route::post('/admin/subscribe-topic-transaksi', [AdminTransaksiController::class, 'subscribeToTopic'])->name('admin.subscribe-topic-transaksi');
Route::get('/admin/servis/recent-transaksi', [AdminTransaksiController::class, 'getRecentServis'])->name('admin.transaksi.recent');
Route::get('/admin/servis/unread-count-transaksi', [AdminTransaksiController::class, 'getUnreadCount'])->name('admin.transaksi.unread-count');

Route::get('/admin/notifications/unread-transaksi', [AdminTransaksiController::class, 'getUnreadNotifications'])->name('admin.notifications.unread-transaksi');
Route::get('/admin/notifications/count-transaksi', [AdminTransaksiController::class, 'getUnreadCount'])->name('admin.notifications.count-transaksi');
Route::post('/admin/notifications/{id}/read-transaksi', [AdminTransaksiController::class, 'markAsRead'])->name('admin.notifications.read-transaksi');
Route::post('/admin/notifications/read-all-transaksi', [AdminTransaksiController::class, 'markAllAsRead'])->name('admin.notifications.read-all-transaksi');

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
    Route::get('/transaksi/export/excel', [AdminTransaksiController::class, 'exportExcel'])->name('transaksi.export.excel');

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
    Route::get('/servis/export/excel', [AdminServisMotorController::class, 'exportExcel'])->name('servis.export.excel');

    // Profile
    Route::get('/profile', [AdminController::class, 'editProfile'])->name('auth.profile');
    Route::put('/profile', [AdminController::class, 'updateProfile'])->name('auth.update');
});
