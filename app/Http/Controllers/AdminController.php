<?php

namespace App\Http\Controllers;

use App\Models\Sparepart;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{

    public function index()
    {
        $totalUsers = User::where('role', 'customer')->count();
        $totalOrder = Transaksi::where('status_pembayaran', 'berhasil')->count();
        $totalTransaksi = Transaksi::where('status_pembayaran', 'berhasil')->sum('total');
        $totalProducts = Sparepart::count();
        $todayOrders = Transaksi::whereDate('created_at', now()->format('Y-m-d'))
            ->where('status_pembayaran', 'berhasil')
            ->count();
        $totalRevenue = Transaksi::whereDate('created_at', now()->format('Y-m-d'))
            ->where('status_pembayaran', 'berhasil')
            ->sum('total');

        return view('admin.dashboard', compact(
            'totalProducts',
            'totalUsers',
            'totalOrder',
            'totalTransaksi',
            'todayOrders',
            'totalRevenue'
        ));
    }
}
