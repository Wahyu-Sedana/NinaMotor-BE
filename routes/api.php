<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\KategoriSparepartController;
use App\Http\Controllers\Api\SparepartController;
use App\Http\Controllers\Api\UsersController;
use Illuminate\Http\Request;
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

Route::post('/register', [UsersController::class, 'register']);
Route::post('/login', [UsersController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [UsersController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/kategori', [KategoriSparepartController::class, 'index']);

    Route::get('/sparepart', [SparepartController::class, 'index']);
    Route::get('/sparepart/{id}', [SparepartController::class, 'show']);
    Route::post('/sparepart', [SparepartController::class, 'store']);
    Route::put('/sparepart/{id}', [SparepartController::class, 'update']);
    Route::delete('/sparepart/{id}', [SparepartController::class, 'destroy']);

    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);

    Route::post('/profile', [UsersController::class, 'profile']);

    Route::post('/logout', [UsersController::class, 'logout']);
});
