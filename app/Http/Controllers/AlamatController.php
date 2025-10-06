<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AlamatPengiriman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AlamatController extends Controller
{
    /**
     * Get all addresses by user
     */
    public function getUserAddresses($userId)
    {
        try {
            $addresses = AlamatPengiriman::where('user_id', $userId)
                ->orderBy('is_default', 'DESC')
                ->orderBy('created_at', 'DESC')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $addresses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat alamat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single address
     */
    public function show($id)
    {
        try {
            $address = AlamatPengiriman::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $address
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Create new address
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:tb_users,id',
            'label_alamat' => 'required|string|max:50',
            'nama_penerima' => 'required|string|max:255',
            'no_telp_penerima' => 'required|string|max:13',
            'alamat_lengkap' => 'required|string',
            'province_id' => 'required|integer',
            'province_name' => 'required|string|max:255',
            'city_id' => 'required|integer',
            'city_name' => 'required|string|max:255',
            'district_id' => 'required|integer',
            'district_name' => 'required|string|max:255',
            'kode_pos' => 'nullable|string|max:5',
            'is_default' => 'boolean'
        ]);

        Log::info('Request data:', $request->all());


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['id'] = Str::uuid();

            // Jika ini alamat pertama user, set sebagai default
            $existingCount = AlamatPengiriman::where('user_id', $data['user_id'])->count();
            if ($existingCount == 0) {
                $data['is_default'] = true;
            }

            $address = AlamatPengiriman::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Alamat berhasil ditambahkan',
                'data' => $address
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan alamat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update address
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'label_alamat' => 'string|max:50',
            'nama_penerima' => 'string|max:255',
            'no_telp_penerima' => 'string|max:13',
            'alamat_lengkap' => 'string',
            'province_id' => 'integer',
            'province_name' => 'string|max:255',
            'city_id' => 'integer',
            'city_name' => 'string|max:255',
            'district_id' => 'required|integer',
            'district_name' => 'required|string|max:255',
            'kode_pos' => 'nullable|string|max:5',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $address = AlamatPengiriman::findOrFail($id);
            $address->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Alamat berhasil diupdate',
                'data' => $address
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate alamat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete address
     */
    public function destroy($id)
    {
        try {
            $address = AlamatPengiriman::findOrFail($id);
            $userId = $address->user_id;
            $wasDefault = $address->is_default;

            $address->delete();

            // Jika yang dihapus adalah default, set alamat lain sebagai default
            if ($wasDefault) {
                $newDefault = AlamatPengiriman::where('user_id', $userId)->first();
                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Alamat berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus alamat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set address as default
     */
    public function setDefault($id)
    {
        try {
            $address = AlamatPengiriman::findOrFail($id);
            $address->update(['is_default' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Alamat berhasil diset sebagai default',
                'data' => $address
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate alamat: ' . $e->getMessage()
            ], 500);
        }
    }
}
