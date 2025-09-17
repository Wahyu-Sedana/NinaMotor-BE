<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sparepart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SparepartController extends Controller
{
    public function index()
    {
        $spareparts = Sparepart::with('kategori')->get();

        return response()->json([
            'success' => true,
            'data' => $spareparts
        ]);
    }

    public function show($id)
    {
        $sparepart = Sparepart::with('kategori')->find($id);

        if (!$sparepart) {
            return response()->json([
                'success' => false,
                'message' => 'Sparepart tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sparepart
        ]);
    }

    public function showDataByKategori(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string'
        ]);

        $namaKategori = $request->input('nama_kategori');

        $spareparts = Sparepart::join('tb_kategori_sparepart as k', 'tb_sparepart.kategori_id', '=', 'k.id')
            ->where('k.nama_kategori', 'like', '%' . $namaKategori . '%')
            ->select('tb_sparepart.*')
            ->with('kategori')
            ->get();

        if ($spareparts->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'Tidak ada sparepart ditemukan untuk kategori: ' . $namaKategori,
                'data' => []
            ], 200);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Data sparepart berhasil ditemukan',
            'kategori' => $namaKategori,
            'total' => $spareparts->count(),
            'data' => $spareparts
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_sparepart' => 'required|string|unique:tb_sparepart,kode_sparepart',
            'kategori_id' => 'required|exists:tb_kategori_sparepart,id',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'stok' => 'required|integer|min:0',
            'harga_beli' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0',
            'merk' => 'nullable|string|max:100',
            'gambar_produk' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('gambar_produk')) {
            $path = $request->file('gambar_produk')->store('gambar_produk', 'public');
            $validated['gambar_produk'] = $path;
        }

        $sparepart = Sparepart::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sparepart berhasil ditambahkan',
            'data' => $sparepart
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $sparepart = Sparepart::find($id);

        if (!$sparepart) {
            return response()->json([
                'success' => false,
                'message' => 'Sparepart tidak ditemukan'
            ], 404);
        }

        $validated = $request->validate([
            'kategori_id' => 'sometimes|required|exists:tb_kategori_sparepart,id',
            'nama' => 'sometimes|required|string|max:255',
            'deskripsi' => 'nullable|string',
            'stok' => 'sometimes|required|integer|min:0',
            'harga_beli' => 'sometimes|required|numeric|min:0',
            'harga_jual' => 'sometimes|required|numeric|min:0',
            'merk' => 'nullable|string|max:100',
            'gambar_produk' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('gambar_produk')) {

            if ($sparepart->gambar_produk && Storage::disk('public')->exists($sparepart->gambar_produk)) {
                Storage::disk('public')->delete($sparepart->gambar_produk);
            }

            $path = $request->file('gambar_produk')->store('gambar_produk', 'public');
            $validated['gambar_produk'] = $path;
        }

        $sparepart->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sparepart berhasil diperbarui',
            'data' => $sparepart
        ], 200);
    }

    public function destroy($id)
    {
        $sparepart = Sparepart::find($id);

        if (!$sparepart) {
            return response()->json([
                'success' => false,
                'message' => 'Sparepart tidak ditemukan'
            ], 404);
        }

        $sparepart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sparepart berhasil dihapus'
        ]);
    }
}
