<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartsModel;
use App\Models\Sparepart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sparepart_id' => 'required|exists:tb_sparepart,id',
            'quantity' => 'required|integer|min:1',
            'session_token' => 'nullable|string',
            'user_id' => 'nullable|uuid|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $sparepart = Sparepart::find($request->sparepart_id);

        if ($sparepart->stok < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stok tidak mencukupi',
            ], 400);
        }


        $sparepart->stok -= $request->quantity;
        $sparepart->save();

        $cart = CartsModel::where('user_id', $request->user_id)
            ->orWhere('session_token', $request->session_token)
            ->first();

        if (!$cart) {
            $cart = CartsModel::create([
                'user_id' => $request->user_id,
                'session_token' => $request->session_token ?? Str::uuid(),
                'items' => [],
            ]);
        }

        $items = $cart->items;

        $found = false;
        foreach ($items as &$item) {
            if ($item['sparepart_id'] === $request->sparepart_id) {
                $item['quantity'] += $request->quantity;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $items[] = [
                'sparepart_id' => $request->sparepart_id,
                'quantity' => $request->quantity,
            ];
        }

        $cart->items = $items;
        $cart->save();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menambahkan ke keranjang',
            'data' => $cart
        ]);
    }
}
