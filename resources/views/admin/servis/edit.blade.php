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

        {{-- INFO TRANSAKSI (jika ada) --}}
        @if ($servisMotor->transaksi)
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Informasi Transaksi</h6>
                <div class="row">
                    <div class="col-md-6">
                        <strong>ID Transaksi:</strong> {{ $servisMotor->transaksi->id }}<br>
                        <strong>Total:</strong> Rp {{ number_format($servisMotor->transaksi->total, 0, ',', '.') }}<br>
                        <strong>Status Pembayaran:</strong>
                        @switch($servisMotor->transaksi->status_pembayaran)
                            @case('pending')
                                <span class="badge bg-warning text-dark">Menunggu Pembayaran</span>
                            @break

                            @case('paid')
                                <span class="badge bg-success">Sudah Dibayar</span>
                            @break

                            @case('cancelled')
                                <span class="badge bg-danger">Dibatalkan</span>
                            @break

                            @case('failed')
                                <span class="badge bg-danger">Gagal</span>
                            @break

                            @default
                                <span class="badge bg-secondary">{{ ucfirst($servisMotor->transaksi->status_pembayaran) }}</span>
                        @endswitch
                    </div>
                    <div class="col-md-6">
                        <strong>Tanggal Transaksi:</strong>
                        {{ $servisMotor->transaksi->tanggal_transaksi }}<br>
                        <strong>Metode Pembayaran:</strong>
                        {{ $servisMotor->transaksi->metode_pembayaran ?? 'Belum dipilih' }}
                    </div>
                </div>
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

                    {{-- No Kendaraan & Jenis Motor --}}
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

                    {{-- Status & Harga Servis --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status"
                                required>
                                <option value="">Pilih Status</option>
                                <option value="pending"
                                    {{ old('status', $servisMotor->status) == 'pending' ? 'selected' : '' }}>
                                    Pending - Menunggu Konfirmasi
                                </option>
                                <option value="rejected"
                                    {{ old('status', $servisMotor->status) == 'rejected' ? 'selected' : '' }}>
                                    Rejected - Ditolak
                                </option>
                                <option value="in_service"
                                    {{ old('status', $servisMotor->status) == 'in_service' ? 'selected' : '' }}>
                                    In Service - Sedang Dikerjakan
                                </option>
                                <option value="priced"
                                    {{ old('status', $servisMotor->status) == 'priced' ? 'selected' : '' }}>
                                    Priced - Sudah Diberi Harga
                                </option>
                                <option value="done"
                                    {{ old('status', $servisMotor->status) == 'done' ? 'selected' : '' }}>
                                    Done - Servis Selesai
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            {{-- Status Badge Preview --}}
                            <div class="mt-2" id="status-preview">
                                @if ($servisMotor->status == 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($servisMotor->status == 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @elseif($servisMotor->status == 'in_service')
                                    <span class="badge bg-primary">In Service</span>
                                @elseif($servisMotor->status == 'priced')
                                    <span class="badge bg-info">Priced</span>
                                @elseif($servisMotor->status == 'done')
                                    <span class="badge bg-success">Done</span>
                                @else
                                    <span class="badge bg-secondary">Tidak Diketahui</span>
                                @endif
                                <small class="text-muted ms-2">Status saat ini</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="harga_servis" class="form-label">Harga Servis</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('harga_servis') is-invalid @enderror"
                                    id="harga_servis" name="harga_servis"
                                    value="{{ old('harga_servis', $servisMotor->harga_servis) }}" min="0"
                                    step="1000" placeholder="0">
                            </div>
                            @error('harga_servis')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Masukkan harga servis untuk status "Priced" atau "Done"
                                </small>
                            </div>

                            {{-- Harga Preview --}}
                            <div class="mt-2" id="harga-preview">
                                @if ($servisMotor->harga_servis)
                                    <span class="badge bg-success">Rp
                                        {{ number_format($servisMotor->harga_servis, 0, ',', '.') }}</span>
                                    <small class="text-muted ms-2">Harga saat ini</small>
                                @else
                                    <span class="badge bg-secondary">Belum ada harga</span>
                                @endif
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

                    {{-- Info Tambahan --}}
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="alert alert-light border-left-info" role="alert">
                                <h6 class="mb-2"><i class="fas fa-lightbulb me-2"></i>Informasi:</h6>
                                <ul class="mb-0 small">
                                    <li><strong>Pending:</strong> Servis baru masuk, menunggu konfirmasi teknisi</li>
                                    <li><strong>Rejected:</strong> Servis ditolak karena alasan tertentu</li>
                                    <li><strong>In Service:</strong> Motor sedang dalam proses servis</li>
                                    <li><strong>Priced:</strong> Servis selesai diperiksa, sudah ada estimasi biaya.
                                        Transaksi akan dibuat otomatis.</li>
                                    <li><strong>Done:</strong> Servis selesai dan motor siap diambil</li>
                                </ul>
                            </div>
                        </div>
                    </div>

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.getElementById('status');
            const hargaServisInput = document.getElementById('harga_servis');
            const statusPreview = document.getElementById('status-preview');
            const hargaPreview = document.getElementById('harga-preview');

            function updateStatusPreview() {
                const statusValue = statusSelect.value;
                let badgeHtml = '';

                switch (statusValue) {
                    case 'pending':
                        badgeHtml = '<span class="badge bg-warning text-dark">Pending</span>';
                        break;
                    case 'rejected':
                        badgeHtml = '<span class="badge bg-danger">Rejected</span>';
                        break;
                    case 'in_service':
                        badgeHtml = '<span class="badge bg-primary">In Service</span>';
                        break;
                    case 'priced':
                        badgeHtml = '<span class="badge bg-info">Priced</span>';
                        break;
                    case 'done':
                        badgeHtml = '<span class="badge bg-success">Done</span>';
                        break;
                    default:
                        badgeHtml = '<span class="badge bg-secondary">Pilih Status</span>';
                }

                statusPreview.innerHTML = badgeHtml + ' <small class="text-muted ms-2">Preview status</small>';
            }


            function updateHargaPreview() {
                const harga = hargaServisInput.value;
                let previewHtml = '';

                if (harga && harga > 0) {
                    const formattedHarga = new Intl.NumberFormat('id-ID').format(harga);
                    previewHtml =
                        `<span class="badge bg-success">Rp ${formattedHarga}</span> <small class="text-muted ms-2">Preview harga</small>`;
                } else {
                    previewHtml = '<span class="badge bg-secondary">Belum ada harga</span>';
                }

                hargaPreview.innerHTML = previewHtml;
            }

            function validateHargaByStatus() {
                const statusValue = statusSelect.value;
                const hargaValue = hargaServisInput.value;

                // Reset validasi
                hargaServisInput.classList.remove('is-invalid');

                // Hapus pesan error sebelumnya
                const existingError = hargaServisInput.parentNode.parentNode.querySelector(
                    '.invalid-feedback.custom-error');
                if (existingError) {
                    existingError.remove();
                }

                if ((statusValue === 'priced' || statusValue === 'done') && (!hargaValue || hargaValue <= 0)) {
                    hargaServisInput.classList.add('is-invalid');

                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback custom-error';
                    errorDiv.textContent =
                        `Harga servis wajib diisi untuk status "${statusValue === 'priced' ? 'Priced' : 'Done'}"`;
                    hargaServisInput.parentNode.parentNode.appendChild(errorDiv);

                    return false;
                }

                return true;
            }

            statusSelect.addEventListener('change', function() {
                updateStatusPreview();
                validateHargaByStatus();
            });

            hargaServisInput.addEventListener('input', function() {
                updateHargaPreview();
                validateHargaByStatus();
            });

            document.querySelector('form').addEventListener('submit', function(e) {
                if (!validateHargaByStatus()) {
                    e.preventDefault();
                    alert('Mohon lengkapi harga servis sesuai dengan status yang dipilih.');
                    return false;
                }
            });

            updateStatusPreview();
            updateHargaPreview();
        });
    </script>

    <style>
        .border-left-info {
            border-left: 4px solid #36b9cc !important;
        }

        .alert-light {
            background-color: #f8f9fc;
            border-color: #e3e6f0;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-color: #ced4da;
        }
    </style>
@endsection
