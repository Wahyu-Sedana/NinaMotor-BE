@extends('admin.layouts.app')

@section('content')
    <div class="container mx-auto px-6 py-8">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2 text-gray-800">Data Servis Motor</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Servis Motor</li>
                    </ol>
                </nav>
            </div>
        </div>

        {{-- ALERT MESSAGES --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- FILTER & EXPORT --}}
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="m-0 font-weight-bold text-primary">Data Servis Motor</h6>
                <div class="d-flex gap-2">
                    {{-- Filter Tahun --}}
                    <div class="d-flex align-items-center gap-2">
                        <label for="filter-tahun" class="mb-0 small">Tahun:</label>
                        <select id="filter-tahun" class="form-select form-select-sm" style="width: 100px;">
                            <option value="">Semua</option>
                            @for ($y = now()->year; $y >= 2020; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    {{-- Filter Bulan --}}
                    <div class="d-flex align-items-center gap-2">
                        <label for="filter-bulan" class="mb-0 small">Bulan:</label>
                        <select id="filter-bulan" class="form-select form-select-sm" style="width: 120px;">
                            <option value="">Semua</option>
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    {{-- Filter Status --}}
                    <div class="d-flex align-items-center gap-2">
                        <label for="filter-status" class="mb-0 small">Status:</label>
                        <select id="filter-status" class="form-select form-select-sm" style="width: 130px;">
                            <option value="">Semua Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_service">Proses</option>
                            <option value="rejected">Ditolak</option>
                            <option value="done">Selesai</option>
                            <option value="priced">Sudah Dibayar</option>
                        </select>
                    </div>

                    {{-- Filter Search --}}
                    <div class="d-flex align-items-center gap-2">
                        <label for="filter-search" class="mb-0 small">Cari:</label>
                        <input type="text" id="filter-search" class="form-control form-control-sm"
                            placeholder="Nama User / Nomor Kendaraan" style="width: 200px;">
                    </div>

                    {{-- Tombol Export --}}
                    <a id="export-excel" href="#" class="btn btn-success btn-sm text-white">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    {!! $dataTable->table(
                        ['class' => 'table table-bordered table-striped nowrap w-100', 'id' => 'servismotor-table'],
                        true,
                    ) !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}

    <script>
        $(document).ready(function() {
            const table = $('#servismotor-table').DataTable();

            // Kirim filter ke ajax
            $('#servismotor-table').on('preXhr.dt', function(e, settings, data) {
                data.tahun = $('#filter-tahun').val();
                data.bulan = $('#filter-bulan').val();
                data.status = $('#filter-status').val();
                data.search_custom = $('#filter-search').val();
            });

            // Reload tabel saat filter tahun/bulan berubah
            $('#filter-tahun, #filter-bulan, #filter-status').on('change', function() {
                table.ajax.reload();
            });

            // Search dengan delay 500ms
            let searchTimeout;
            $('#filter-search').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    table.ajax.reload();
                }, 500);
            });

            // Export Excel
            $('#export-excel').on('click', function(e) {
                e.preventDefault();
                let tahun = $('#filter-tahun').val();
                let bulan = $('#filter-bulan').val();
                let search = $('#filter-search').val();
                let status = $('#filter-status').val();
                let url = "{{ route('admin.servis.export.excel') }}?tahun=" + tahun + "&bulan=" + bulan +
                    "&status" + status + "&search=" + search;
                window.location.href = url;
            });

            // Delete handler
            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                const url = $(this).data('url');
                const serviceName = $(this).closest('tr').find('td:nth-child(3)').text();

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    html: `Data servis motor <strong>"${serviceName}"</strong> akan dihapus dan tidak dapat dikembalikan!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
                    cancelButtonText: '<i class="fas fa-times"></i> Batal',
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Menghapus...',
                            text: 'Sedang memproses penghapusan data',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });

                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Berhasil!',
                                        text: response.message,
                                        icon: 'success',
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                    table.ajax.reload(null, false);
                                } else {
                                    Swal.fire({
                                        title: 'Gagal!',
                                        text: response.message ||
                                            'Terjadi kesalahan',
                                        icon: 'error'
                                    });
                                }
                            },
                            error: function(xhr) {
                                let message = 'Terjadi kesalahan saat menghapus data';
                                if (xhr.responseJSON?.message) message = xhr
                                    .responseJSON.message;
                                else if (xhr.status === 404) message =
                                    'Data tidak ditemukan';
                                else if (xhr.status === 403) message =
                                    'Anda tidak memiliki akses';
                                else if (xhr.status === 500) message =
                                    'Kesalahan server';

                                Swal.fire({
                                    title: 'Error!',
                                    text: message,
                                    icon: 'error'
                                });
                            }
                        });
                    }
                });
            });

            // Tooltip refresh setiap draw
            $('#servismotor-table').on('draw.dt', function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            });
        });
    </script>
@endpush
