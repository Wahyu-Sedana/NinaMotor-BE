<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartsModel;
use App\Models\Sparepart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function store(Request $request)
    {
        Log::info('Incoming request to addToCart (token-based auth): ', $request->all());

        $validator = Validator::make($request->all(), [
            'sparepart_id' => 'required|string|exists:tb_sparepart,kode_sparepart',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            Log::warning('Validasi gagal: ', $validator->errors()->toArray());

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi',
            ], 401);
        }

        $sparepart = Sparepart::find($request->sparepart_id);
        Log::info("Sparepart ditemukan: {$request->sparepart_id}, stok saat ini: {$sparepart->stok}");

        // if ($sparepart->stok < $request->quantity) {
        //     Log::warning("Stok tidak mencukupi: diminta {$request->quantity}, tersedia {$sparepart->stok}");
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Stok tidak mencukupi',
        //     ], 200);
        // }

        // $sparepart->stok -= $request->quantity;
        // $sparepart->save();
        // Log::info("Stok berhasil dikurangi, sisa: {$sparepart->stok}");

        if ($sparepart->stok < $request->quantity) {
            Log::warning("Stok tidak mencukupi: diminta {$request->quantity}, tersedia {$sparepart->stok}");
            return response()->json([
                'success' => false,
                'message' => 'Stok tidak mencukupi untuk saat ini',
            ], 200);
        }

        $cart = CartsModel::where('user_id', $user->id)->first();

        if (!$cart) {
            Log::info("Cart tidak ditemukan, membuat baru...");
            $cart = CartsModel::create([
                'user_id' => $user->id,
                'session_token' => null,
                'items' => [],
            ]);
        } else {
            Log::info("Cart ditemukan: ID = {$cart->id}");
        }

        $items = $cart->items;
        $found = false;
        foreach ($items as &$item) {
            if ($item['sparepart_id'] === $request->sparepart_id) {
                $item['quantity'] += $request->quantity;
                $found = true;
                Log::info("Item sudah ada di cart, menambah quantity menjadi {$item['quantity']}");
                break;
            }
        }

        if (!$found) {
            $items[] = [
                'sparepart_id' => $request->sparepart_id,
                'quantity' => $request->quantity,
            ];
            Log::info("Item baru ditambahkan ke cart: sparepart_id = {$request->sparepart_id}, quantity = {$request->quantity}");
        }

        $cart->items = $items;
        $cart->save();
        Log::info("Cart berhasil disimpan: cart_id = {$cart->id}");

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menambahkan ke keranjang',
            'data' => $cart
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User belum login'
            ], 401);
        }

        $cart = CartsModel::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json([
                'success' => true,
                'message' => 'Cart kosong',
                'data' => null
            ]);
        }

        $items = [];
        $total = 0;
        foreach ($cart->items as $item) {
            $sparepart = Sparepart::find($item['sparepart_id']);

            if ($sparepart) {
                $subtotal = $sparepart->harga_jual * $item['quantity'];
                $total += $subtotal;

                $items[] = [
                    'sparepart_id' => $sparepart->kode_sparepart,
                    'nama_sparepart' => $sparepart->nama_sparepart,
                    'gambar' => $sparepart->gambar_produk,
                    'harga_jual' => $sparepart->harga_jual,
                    'quantity' => $item['quantity'],
                    'subtotal' => $sparepart->harga_jual * $item['quantity'],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Data cart berhasil diambil',
            'data' => [
                'id' => $cart->id,
                'user_id' => $cart->user_id,
                'items' => $items,
                'total' => $total
            ]
        ]);
    }

    public function remove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sparepart_id' => 'required|string|exists:tb_sparepart,kode_sparepart',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi',
            ], 401);
        }

        $cart = CartsModel::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart tidak ditemukan',
            ], 404);
        }

        $items = $cart->items;
        $updatedItems = array_filter($items, function ($item) use ($request) {
            return $item['sparepart_id'] !== $request->sparepart_id;
        });

        $cart->items = array_values($updatedItems);
        $cart->save();

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil dihapus dari cart',
            'data' => $cart
        ]);
    }
}
