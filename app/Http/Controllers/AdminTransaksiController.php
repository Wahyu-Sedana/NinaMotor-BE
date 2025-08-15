<?php

namespace App\Http\Controllers;

use App\DataTables\AdminTransactionDataTable;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminTransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(AdminTransactionDataTable $dataTable)
    {
        return $dataTable->render('admin.transaksi.index', [
            'title' => 'Transaksi'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaksi $transaksi)
    {
        $transaksi->load('user');

        $itemsData = [];
        if ($transaksi->items_data) {
            $itemsData = json_decode($transaksi->items_data, true) ?? [];
        }

        return view('admin.transaksi.show', [
            'title' => 'Detail Transaksi',
            'transaksi' => $transaksi,
            'itemsData' => $itemsData
        ]);
    }

    /**
     * Update payment status
     */
    public function updateStatus(Request $request, Transaksi $transaksi)
    {
        $request->validate([
            'status_pembayaran' => 'required|in:pending,berhasil,gagal,expired,cancelled'
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $transaksi->status_pembayaran;

            $transaksi->update([
                'status_pembayaran' => $request->status_pembayaran
            ]);

            DB::commit();

            Log::info('Transaction status updated', [
                'id' => $transaksi->id,
                'old_status' => $oldStatus,
                'new_status' => $transaksi->status_pembayaran,
                'user' => auth()->user()->name ?? 'Unknown'
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status pembayaran berhasil diperbarui',
                    'data' => $transaksi
                ]);
            }

            return redirect()->route('admin.transaksi.index')
                ->with('success', 'Status pembayaran berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error updating transaction status', [
                'id' => $transaksi->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat memperbarui status'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat memperbarui status.')
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaksi $transaksi)
    {
        try {
            $allowedStatuses = ['gagal', 'expired', 'cancelled'];

            if (!in_array($transaksi->status_pembayaran, $allowedStatuses)) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Hanya transaksi dengan status failed, expired, atau cancelled yang dapat dihapus.'
                    ], 409);
                }

                return redirect()->back()
                    ->with('error', 'Hanya transaksi dengan status failed, expired, atau cancelled yang dapat dihapus.');
            }

            DB::beginTransaction();

            $transaksiData = $transaksi->toArray();
            $transaksi->delete();

            DB::commit();

            Log::info('Transaction deleted', [
                'deleted_data' => $transaksiData,
                'user' => auth()->user()->name ?? 'Unknown'
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi berhasil dihapus'
                ]);
            }

            return redirect()->route('admin.transaksi.index')
                ->with('success', 'Transaksi berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error deleting transaction', [
                'id' => $transaksi->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menghapus data'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }
}
