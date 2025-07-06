<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KategoriSparepart;
use Illuminate\Http\Request;

class KategoriSparepartController extends Controller
{
    public function index()
    {
        $kategori = KategoriSparepart::all();

        return response()->json([
            'success' => true,
            'data' => $kategori
        ]);
    }
}
