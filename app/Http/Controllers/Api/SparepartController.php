<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sparepart;
use Illuminate\Http\Request;

class SparepartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $spareparts = Sparepart::with('kategori')->get();

        return response()->json([
            'success' => true,
            'data' => $spareparts
        ]);
    }

    /**
     * Menampilkan detail satu sparepart berdasarkan ID.
     */
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
}
