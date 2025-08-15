@extends('admin.layouts.app')

@section('content')
    <div class="container mx-auto px-6 py-8">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2 text-gray-800">Edit Servis Motor</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.servis.index') }}">Servis Motor</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                    </ol>
                </nav>
            </div>

            <a href="{{ route('admin.servis.index') }}" class="btn btn-secondary">
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
                <h6 class="m-0 font-weight-bold text-primary">Form Edit Servis Motor</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.servis.update', $servisMotor->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- Info Pelanggan (readonly) --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nama_user" class="form-label">Nama Pelanggan</label>
                            <input type="text" class="form-control" id="nama_user"
                                value="{{ $servisMotor->user ? $servisMotor->user->nama : '-' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="tanggal_input" class="form-label">Tanggal Input</label>
                            <input type="text" class="form-control" id="tanggal_input"
                                value="{{ date('d/m/Y H:i', strtotime($servisMotor->created_at)) }}" readonly>
                        </div>
                    </div>

                    {{-- No Kendaraan --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="no_kendaraan" class="form-label">No Kendaraan <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('no_kendaraan') is-invalid @enderror"
                                id="no_kendaraan" name="no_kendaraan"
                                value="{{ old('no_kendaraan', $servisMotor->no_kendaraan) }}"
                                placeholder="Masukkan nomor kendaraan" required>
                            @error('no_kendaraan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Jenis Motor --}}
                        <div class="col-md-6">
                            <label for="jenis_motor" class="form-label">Jenis Motor <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('jenis_motor') is-invalid @enderror"
                                id="jenis_motor" name="jenis_motor"
                                value="{{ old('jenis_motor', $servisMotor->jenis_motor) }}"
                                placeholder="Masukkan jenis motor" required>
                            @error('jenis_motor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Keluhan --}}
                    <div class="mb-3">
                        <label for="keluhan" class="form-label">Keluhan <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('keluhan') is-invalid @enderror" id="keluhan" name="keluhan" rows="4"
                            placeholder="Deskripsikan keluhan atau masalah pada motor" required>{{ old('keluhan', $servisMotor->keluhan) }}</textarea>
                        @error('keluhan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status"
                                required>
                                <option value="">Pilih Status</option>
                                <option value="pending"
                                    {{ old('status', $servisMotor->status) == 'pending' ? 'selected' : '' }}>
                                    <span class="badge bg-warning">Pending</span> - Menunggu Konfirmasi
                                </option>
                                <option value="in_service"
                                    {{ old('status', $servisMotor->status) == 'in_service' ? 'selected' : '' }}>
                                    <span class="badge bg-primary">Proses</span> - Sedang Dikerjakan
                                </option>
                                <option value="done"
                                    {{ old('status', $servisMotor->status) == 'done' ? 'selected' : '' }}>
                                    <span class="badge bg-success">Selesai</span> - Servis Selesai
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            {{-- Status Badge Preview --}}
                            <div class="mt-2" id="status-preview">
                                @if ($servisMotor->status == 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($servisMotor->status == 'in_service')
                                    <span class="badge bg-primary">Proses</span>
                                @elseif($servisMotor->status == 'done')
                                    <span class="badge bg-success">Selesai</span>
                                @else
                                    <span class="badge bg-secondary">Tidak Diketahui</span>
                                @endif
                                <small class="text-muted ms-2">Status saat ini</small>
                            </div>
                        </div>
                    </div>

                    {{-- Catatan Admin --}}
                    <div class="mb-3">
                        <label for="catatan_admin" class="form-label">Catatan Admin</label>
                        <textarea class="form-control @error('catatan_admin') is-invalid @enderror" id="catatan_admin" name="catatan_admin"
                            rows="4" placeholder="Tambahkan catatan atau keterangan tambahan dari admin">{{ old('catatan_admin', $servisMotor->catatan_admin) }}</textarea>
                        @error('catatan_admin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('admin.servis.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Perbarui Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- JavaScript untuk Status Preview --}}
    <script>
        document.getElementById('status').addEventListener('change', function() {
            const statusValue = this.value;
            const preview = document.getElementById('status-preview');
            let badgeHtml = '';

            switch (statusValue) {
                case 'pending':
                    badgeHtml = '<span class="badge bg-warning text-dark">Pending</span>';
                    break;
                case 'in_service':
                    badgeHtml = '<span class="badge bg-primary">Proses</span>';
                    break;
                case 'done':
                    badgeHtml = '<span class="badge bg-success">Selesai</span>';
                    break;
                default:
                    badgeHtml = '<span class="badge bg-secondary">Pilih Status</span>';
            }

            preview.innerHTML = badgeHtml + ' <small class="text-muted ms-2">Preview status</small>';
        });
    </script>
@endsection
