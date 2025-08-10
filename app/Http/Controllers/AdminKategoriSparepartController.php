<?php

namespace App\Http\Controllers;

use App\DataTables\AdminKategoriSparepartDataTable;
use App\Models\KategoriSparepart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminKategoriSparepartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(AdminKategoriSparepartDataTable $dataTable)
    {
        return $dataTable->render('admin.kategori-sparepart.index', [
            'title' => 'Kategori Sparepart'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.kategori-sparepart.create', [
            'title' => 'Tambah Kategori Sparepart'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_kategori' => 'required|string|max:100|unique:tb_kategori_sparepart,nama_kategori',
            'deskripsi' => 'nullable|string|max:500'
        ], [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.max' => 'Nama kategori maksimal 100 karakter.',
            'nama_kategori.unique' => 'Nama kategori sudah ada, gunakan nama lain.',
            'deskripsi.max' => 'Deskripsi maksimal 500 karakter.'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $kategori = KategoriSparepart::create([
                'nama_kategori' => $request->nama_kategori,
                'deskripsi' => $request->deskripsi
            ]);

            DB::commit();

            Log::info('Kategori sparepart created', [
                'id' => $kategori->id,
                'nama_kategori' => $kategori->nama_kategori,
                'user' => auth()->user()->name ?? 'Unknown'
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kategori berhasil ditambahkan',
                    'data' => $kategori
                ]);
            }

            return redirect()->route('admin.kategori-sparepart.index')
                ->with('success', 'Kategori sparepart berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error creating kategori sparepart', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menyimpan data'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menyimpan data.')
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(KategoriSparepart $kategoriSparepart)
    {
        $kategoriSparepart->load(['spareparts' => function ($query) {
            $query->select('id', 'kode_sparepart', 'nama_sparepart', 'merk', 'stok', 'kategori_id');
        }]);

        return view('admin.kategori-sparepart.show', [
            'title' => 'Detail Kategori Sparepart',
            'kategori' => $kategoriSparepart
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KategoriSparepart $kategoriSparepart)
    {
        return view('admin.kategori-sparepart.edit', [
            'title' => 'Edit Kategori Sparepart',
            'kategori' => $kategoriSparepart
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KategoriSparepart $kategoriSparepart)
    {
        $validator = Validator::make($request->all(), [
            'nama_kategori' => 'required|string|max:100|unique:tb_kategori_sparepart,nama_kategori,' . $kategoriSparepart->id,
            'deskripsi' => 'nullable|string|max:500'
        ], [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.max' => 'Nama kategori maksimal 100 karakter.',
            'nama_kategori.unique' => 'Nama kategori sudah ada, gunakan nama lain.',
            'deskripsi.max' => 'Deskripsi maksimal 500 karakter.'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $oldData = $kategoriSparepart->toArray();

            $kategoriSparepart->update([
                'nama_kategori' => $request->nama_kategori,
                'deskripsi' => $request->deskripsi
            ]);

            DB::commit();

            Log::info('Kategori sparepart updated', [
                'id' => $kategoriSparepart->id,
                'old_data' => $oldData,
                'new_data' => $kategoriSparepart->fresh()->toArray(),
                'user' => auth()->user()->name ?? 'Unknown'
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kategori berhasil diperbarui',
                    'data' => $kategoriSparepart
                ]);
            }

            return redirect()->route('admin.kategori-sparepart.index')
                ->with('success', 'Kategori sparepart berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error updating kategori sparepart', [
                'id' => $kategoriSparepart->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat memperbarui data'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat memperbarui data.')
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KategoriSparepart $kategoriSparepart)
    {
        try {
            // Cek apakah kategori masih digunakan
            $jumlahSparepart = $kategoriSparepart->spareparts()->count();

            if ($jumlahSparepart > 0) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Kategori tidak dapat dihapus karena masih digunakan oleh {$jumlahSparepart} sparepart."
                    ], 409);
                }

                return redirect()->back()
                    ->with('error', "Kategori tidak dapat dihapus karena masih digunakan oleh {$jumlahSparepart} sparepart.");
            }

            DB::beginTransaction();

            $kategoriData = $kategoriSparepart->toArray();
            $kategoriSparepart->delete();

            DB::commit();

            Log::info('Kategori sparepart deleted', [
                'deleted_data' => $kategoriData,
                'user' => auth()->user()->name ?? 'Unknown'
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kategori berhasil dihapus'
                ]);
            }

            return redirect()->route('admin.kategori-sparepart.index')
                ->with('success', 'Kategori sparepart berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error deleting kategori sparepart', [
                'id' => $kategoriSparepart->id,
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
