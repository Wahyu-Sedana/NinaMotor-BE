<?php

namespace App\Http\Controllers;

use App\DataTables\AdminServisMotorDataTable;
use App\Models\ServisMotor;
use Illuminate\Http\Request;

class AdminServisMotorController extends Controller
{
    public function index(AdminServisMotorDataTable $dataTable)
    {
        return $dataTable->render('admin.servis.index', [
            'title' => 'Data Servis Motor',
        ]);
    }

    /**
     * Show the form for editing the specified sparepart.
     */
    public function edit(ServisMotor $servis)
    {
        return view('admin.servis.edit', compact('servis'));
    }

    /**
     * Update the specified service in storage.
     */
    public function update(Request $request, ServisMotor $servis)
    {
        $request->validate([
            'nama_sparepart' => 'required|string|max:255',
            'deskripsi'      => 'nullable|string',
            'stok'           => 'required|integer|min:0',
            'harga_beli'     => 'required|numeric|min:0',
            'harga_jual'     => 'required|numeric|min:0',
            'merk'           => 'nullable|string|max:255',
            'gambar_produk'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('gambar_produk')) {
            $data['gambar_produk'] = $request->file('gambar_produk')->store('spareparts', 'public');
        }

        $servis->update($data);

        return redirect()->route('admin.servis.index')->with('success', 'Sparepart berhasil diperbarui.');
    }

    /**
     * Remove the specified service from storage.
     */
    // public function destroy(string $kode_sparepart)
    // {
    //     try {
    //         $sparepart = Sparepart::findOrFail($kode_sparepart);

    //         if ($sparepart->gambar_produk && Storage::disk('public')->exists('gambar_produk/' . $sparepart->gambar_produk)) {
    //             Storage::disk('public')->delete('gambar_produk/' . $sparepart->gambar_produk);
    //         }

    //         $sparepart->delete();

    //         return redirect()->route('admin.sparepart.index')
    //             ->with('success', 'Sparepart berhasil dihapus!');
    //     } catch (\Exception $e) {
    //         return redirect()->route('admin.sparepart.index')
    //             ->with('error', 'Gagal menghapus sparepart: ' . $e->getMessage());
    //     }
    // }
}
