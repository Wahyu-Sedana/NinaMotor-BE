<?php

namespace App\Http\Controllers;

use App\DataTables\AdminDataCustomerDataTable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminDataCustomerController extends Controller
{
    /**
     * Tampilkan daftar customer menggunakan DataTables.
     */
    public function index(AdminDataCustomerDataTable $dataTable)
    {
        return $dataTable->render('admin.customer.index', [
            'title' => 'Data Customer',
        ]);
    }

    /**
     * Tampilkan form untuk menambahkan customer baru.
     */
    public function create()
    {
        return view('admin.customer.create', [
            'title' => 'Tambah Customer',
        ]);
    }

    /**
     * Simpan data customer baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:tb_users,email',
            'password' => 'required|string|min:8|confirmed',
            'no_telp'  => 'nullable|string|max:13',
            'alamat'   => 'nullable|string|max:255',
            'profile'  => 'nullable|string|url|max:255',
        ]);

        DB::beginTransaction();

        try {
            $customer = User::create([
                'nama'     => $request->nama,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'no_telp'  => $request->no_telp,
                'alamat'   => $request->alamat,
                'profile'  => $request->profile,
                'role'     => 'customer',
            ]);

            DB::commit();

            Log::info('Customer ditambahkan', [
                'id' => $customer->id,
                'nama' => $customer->nama,
                'user' => auth()->user()->name ?? 'Unknown'
            ]);

            return redirect()->route('admin.customer.index')
                ->with('success', 'Customer berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Gagal menambahkan customer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withInput()->withErrors(['error' => 'Gagal menambahkan customer: ' . $e->getMessage()]);
        }
    }

    /**
     * Tampilkan detail customer tertentu.
     */
    public function show($id)
    {
        $customer = User::findOrFail($id);

        return view('admin.customer.show', [
            'title' => 'Detail Customer',
            'customer' => $customer,
        ]);
    }

    /**
     * Tampilkan form untuk mengedit data customer.
     */
    public function edit($id)
    {
        $customer = User::findOrFail($id);

        return view('admin.customer.edit', [
            'title' => 'Edit Customer',
            'customer' => $customer,
        ]);
    }

    /**
     * Perbarui data customer di database.
     */
    public function update(Request $request, $id)
    {
        $customer = User::findOrFail($id);

        $request->validate([
            'nama'     => 'required|string|max:255',
            'email'    => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('tb_users')->ignore($customer->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'no_telp'  => 'nullable|string|max:13',
            'alamat'   => 'nullable|string|max:255',
            'profile'  => 'nullable|string|url|max:255',
        ]);

        DB::beginTransaction();

        try {
            $customer->update([
                'nama'     => $request->nama,
                'email'    => $request->email,
                'no_telp'  => $request->no_telp,
                'alamat'   => $request->alamat,
                'profile'  => $request->profile,
            ]);

            if ($request->filled('password')) {
                $customer->update(['password' => Hash::make($request->password)]);
            }

            DB::commit();

            Log::info('Customer diperbarui', [
                'id' => $customer->id,
                'nama' => $customer->nama,
                'user' => auth()->user()->name ?? 'Unknown'
            ]);

            return redirect()->route('admin.customer.index')->with('success', 'Customer berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Gagal memperbarui customer', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'Gagal memperbarui customer: ' . $e->getMessage()]);
        }
    }

    /**
     * Hapus data customer dari database.
     */
    public function destroy($id)
    {
        try {
            $customer = User::find($id);

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer tidak ditemukan'
                ], 404);
            }

            DB::beginTransaction();

            $customerData = [
                'id' => $customer->id,
                'nama' => $customer->nama
            ];

            $customer->delete();
            DB::commit();

            Log::info('Customer dihapus', [
                'deleted_data' => $customerData,
                'user' => auth()->user()->name ?? 'Unknown'
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Customer berhasil dihapus'
                ]);
            }

            return redirect()->route('admin.customer.index')
                ->with('success', 'Customer berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error deleting customer', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menghapus data'
                ], 500);
            }

            return redirect()->route('admin.customer.index')
                ->with('error', 'Gagal menghapus customer: ' . $e->getMessage());
        }
    }
}
