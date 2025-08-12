<?php

namespace App\Http\Controllers;

use App\DataTables\AdminDataCustomerDataTable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminDataCustomerController extends Controller
{
    public function index(AdminDataCustomerDataTable $dataTable)
    {
        return $dataTable->render('admin.customer.index', [
            'title' => 'Customer'
        ]);
    }

    public function create()
    {
        return view('admin.customer.create', [
            'title' => 'Tambah Customer'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:tb_users,email',
            'password' => 'required|string|min:8|confirmed',
            'no_telp' => 'nullable|string|max:13',
            'alamat' => 'nullable|string|max:255',
            'profile' => 'nullable|string|url|max:255',
        ]);

        try {
            User::create([
                'nama' => $request->nama,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'no_telp' => $request->role,
                'alamat' => $request->alamat,
                'profile' => $request->profile,
            ]);

            return redirect()->route('admin.customer.index')->with('success', 'Pengguna berhasil ditambahkan!');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Gagal menambahkan pengguna: ' . $e->getMessage()]);
        }
    }

    public function edit(User $customer)
    {
        return view('admin.customer.edit', [
            'title' => 'Edit Customer',
            'user' => $customer,
        ]);
    }

    public function update(Request $request, User $customer)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('tb_users')->ignore($customer->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'no_telp' => 'required|string|max:13',
            'alamat' => 'nullable|string|max:255',
            'profile' => 'nullable|string|url|max:255',
        ]);

        try {
            $customer->nama = $request->nama;
            $customer->email = $request->email;
            $customer->no_telp = $request->no_telp;
            $customer->alamat = $request->alamat;

            if ($request->filled('password')) {
                $customer->password = Hash::make($request->password);
            }

            $customer->save();

            return redirect()->route('admin.customer.index')->with('success', 'Pengguna berhasil diperbarui!');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Gagal memperbarui pengguna: ' . $e->getMessage()]);
        }
    }
}
