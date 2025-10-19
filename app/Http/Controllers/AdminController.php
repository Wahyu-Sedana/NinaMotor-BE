<?php

namespace App\Http\Controllers;

use App\Models\ServisMotor;
use App\Models\Sparepart;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $ordersPerMonth = Transaksi::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as total')
        )
            ->whereYear('created_at', now()->year)
            ->where('status_pembayaran', 'berhasil')
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month')
            ->get();

        $dataPerMonth = Transaksi::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as total_orders'),
            DB::raw('SUM(total) as total_revenue')
        )
            ->whereYear('created_at', now()->year)
            ->where('status_pembayaran', 'berhasil')
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month')
            ->get();


        $months = [];
        $totals = [];
        $revenues = [];
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        for ($i = 1; $i <= 12; $i++) {
            $months[] = $monthNames[$i - 1];
            $totals[] = $ordersPerMonth->firstWhere('month', $i)->total ?? 0;
            $revenues[] = $dataPerMonth->firstWhere('month', $i)->total_revenue ?? 0;
        }

        $recentTransaksi = Transaksi::with('user')
            ->whereDate('created_at', Carbon::today())
            ->latest()
            ->take(5)
            ->get();

        $recentServis = ServisMotor::with('user')
            ->whereDate('created_at', Carbon::today())
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalProducts',
            'totalUsers',
            'totalOrder',
            'totalTransaksi',
            'todayOrders',
            'totalRevenue',
            'months',
            'totals',
            'revenues',
            'recentTransaksi',
            'recentServis'
        ));
    }

    public function editProfile()
    {
        $title = 'Edit Profile Admin';
        return view('admin.auth.profile', compact('title'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'nama' => 'required|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $user->nama = $request->nama;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return redirect()->back()->with('success', 'Profile berhasil diperbarui');
    }
}
