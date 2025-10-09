<?php

namespace App\Http\Controllers;

use App\DataTables\AdminSparepartDataTable;
use App\Models\KategoriSparepart;
use App\Models\Sparepart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminSparepartController extends Controller
{
    /**
     * Display a listing of the spareparts using DataTables.
     */
    public function index(AdminSparepartDataTable $dataTable)
    {
        return $dataTable->render('admin.sparepart.index', [
            'title' => 'Data Sparepart',
        ]);
    }

    /**
     * Show the form for creating a new sparepart.
     */
    public function create()
    {
        $kategoris = KategoriSparepart::orderBy('nama_kategori')->get();
        return view('admin.sparepart.create', compact('kategoris'));
    }

    /**
     * Store a newly created sparepart in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'kode_sparepart' => 'required|string|unique:tb_sparepart,kode_sparepart',
            'kategori_id'    => 'required|exists:tb_kategori_sparepart,id',
            'nama_sparepart' => 'required|string|max:255',
            'deskripsi'      => 'nullable|string',
            'stok'           => 'required|integer|min:0',
            'harga'          => 'required|numeric|min:0',
            'merk'           => 'nullable|string|max:255',
            'gambar_produk'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('gambar_produk')) {
            $data['gambar_produk'] = $request->file('gambar_produk')->store('spareparts', 'public');
        }

        Sparepart::create($data);

        return redirect()->route('admin.sparepart.index')->with('success', 'Sparepart berhasil ditambahkan.');
    }

    /**
     * Display the specified sparepart.
     */
    public function show($kode_sparepart)
    {
        $sparepart = Sparepart::where('kode_sparepart', $kode_sparepart)->firstOrFail();

        return view('admin.sparepart.show', compact('sparepart'));
    }

    /**
     * Show the form for editing the specified sparepart.
     */
    public function edit($kode_sparepart)
    {
        $sparepart = Sparepart::where('kode_sparepart', $kode_sparepart)->firstOrFail();
        $kategoris = KategoriSparepart::orderBy('nama_kategori')->get();

        return view('admin.sparepart.edit', compact('sparepart', 'kategoris'));
    }

    /**
     * Update the specified sparepart in storage.
     */
    public function update(Request $request, $kode_sparepart)
    {
        $sparepart = Sparepart::where('kode_sparepart', $kode_sparepart)->firstOrFail();

        $request->validate([
            'kategori_id'    => 'required|exists:tb_kategori_sparepart,id',
            'nama_sparepart' => 'required|string|max:255',
            'deskripsi'      => 'nullable|string',
            'stok'           => 'required|integer|min:0',
            'harga'          => 'required|numeric|min:0',
            'merk'           => 'nullable|string|max:255',
            'gambar_produk'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('gambar_produk')) {
            if ($sparepart->gambar_produk && Storage::disk('public')->exists($sparepart->gambar_produk)) {
                Storage::disk('public')->delete($sparepart->gambar_produk);
            }

            $data['gambar_produk'] = $request->file('gambar_produk')->store('spareparts', 'public');
        }

        $sparepart->update($data);

        return redirect()->route('admin.sparepart.index')->with('success', 'Sparepart berhasil diperbarui.');
    }

    /**
     * Remove the specified sparepart from storage.
     */
    public function destroy($kode_sparepart)
    {
        try {
            $sparepart = Sparepart::where('kode_sparepart', $kode_sparepart)->first();

            if (!$sparepart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sparepart tidak ditemukan'
                ], 404);
            }

            DB::beginTransaction();

            if ($sparepart->gambar_produk && Storage::disk('public')->exists($sparepart->gambar_produk)) {
                Storage::disk('public')->delete($sparepart->gambar_produk);
            }

            $sparepartData = [
                'kode_sparepart' => $sparepart->kode_sparepart,
                'nama_sparepart' => $sparepart->nama_sparepart
            ];

            $sparepart->delete();

            DB::commit();

            Log::info('Sparepart deleted', [
                'deleted_data' => $sparepartData,
                'user' => auth()->user()->name ?? 'Unknown'
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sparepart berhasil dihapus'
                ]);
            }

            return redirect()->route('admin.sparepart.index')
                ->with('success', 'Sparepart berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error deleting sparepart', [
                'kode_sparepart' => $kode_sparepart ?? 'NULL',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menghapus data'
                ], 500);
            }

            return redirect()->route('admin.sparepart.index')
                ->with('error', 'Gagal menghapus sparepart: ' . $e->getMessage());
        }
    }
}
