@extends('admin.layouts.app')

@section('content')
    <div class="container mx-auto px-6 py-8">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2 text-gray-800">Edit Pengguna</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.customer.index') }}">Pengguna</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                    </ol>
                </nav>
            </div>

            <a href="{{ route('admin.customer.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Kembali
            </a>
        </div>

        {{-- ALERT MESSAGES --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Terdapat kesalahan:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- FORM --}}
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Form Edit Pengguna</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.customer.update', $customer->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- Nama --}}
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama"
                            name="nama" value="{{ old('nama', $customer->nama) }}" placeholder="Masukkan nama pengguna"
                            required>
                        @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                            name="email" value="{{ old('email', $customer->email) }}" placeholder="Masukkan alamat email"
                            required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Password (Opsional untuk update) --}}
                    <div class="mb-3">
                        <label for="password" class="form-label">Password Baru (isi jika ingin mengubah)</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                            name="password" placeholder="Biarkan kosong jika tidak ingin mengubah password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Konfirmasi Password Baru --}}
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                            placeholder="Konfirmasi password baru">
                    </div>

                    {{-- Alamat --}}
                    <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat</label>
                        <textarea class="form-control @error('alamat') is-invalid @enderror" id="alamat" name="alamat" rows="3"
                            placeholder="Masukkan alamat pengguna">{{ old('alamat', $customer->alamat) }}</textarea>
                        @error('alamat')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div class="mb-3">
                        <label for="no_telp" class="form-label">No Telp</label>
                        <input type="text" class="form-control @error('no_telp') is-invalid @enderror" id="no_telp"
                            name="no_telp" placeholder="Masukkan No Telp (misal: 081234567890)" required>
                        @error('no_telp')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>

                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('admin.customer.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Perbarui
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
