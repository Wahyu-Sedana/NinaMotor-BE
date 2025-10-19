@extends('admin.layouts.app')

@section('title', $title)

@section('content')
    <div class="container-fluid px-3 px-md-4 py-4">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2 text-gray-800">{{ $title }}</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a id="export-excel" href="#" class="btn btn-success btn-sm text-white">
                    <i class="fas fa-file-excel"></i> <span class="d-none d-sm-inline">Export Excel</span>
                </a>
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
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h6 class="m-0 font-weight-bold text-primary">Data Transaksi</h6>
                </div>

                {{-- Filter Section - Responsive Grid --}}
                <div class="row g-2">
                    {{-- Filter Tahun --}}
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="filter-tahun" class="form-label small mb-1">Tahun</label>
                        <select id="filter-tahun" class="form-select form-select-sm"></select>
                    </div>

                    {{-- Filter Bulan --}}
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="filter-bulan" class="form-label small mb-1">Bulan</label>
                        <select id="filter-bulan" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    {{-- Filter Status --}}
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="filter-status" class="form-label small mb-1">Status</label>
                        <select id="filter-status" class="form-select form-select-sm">
                            <option value="">Semua Status</option>
                            <option value="pending">Pending</option>
                            <option value="berhasil">Berhasil</option>
                            <option value="gagal">Gagal</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    {{-- Filter Search --}}
                    <div class="col-12 col-lg-5">
                        <label for="filter-search" class="form-label small mb-1">Cari</label>
                        <input type="text" id="filter-search" class="form-control form-control-sm"
                            placeholder="Nama User / Metode Pembayaran">
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    {!! $dataTable->table(
                        ['class' => 'table table-bordered table-striped table-hover w-100', 'id' => 'transaksi-table'],
                        true,
                    ) !!}
                </div>
            </div>
        </div>

        {{-- Modal Update Status --}}
        <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="updateStatusModalLabel">
                            <i class="fas fa-edit text-primary me-2"></i>Update Status Pembayaran
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="updateStatusForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="status_pembayaran" class="form-label">Status Pembayaran <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="status_pembayaran" name="status_pembayaran" required>
                                    <option value="">Pilih Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="berhasil">Berhasil</option>
                                    <option value="gagal">Gagal</option>
                                    <option value="expired">Expired</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <input type="hidden" id="transaksi_id" name="transaksi_id">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Batal
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Detail Items --}}
        <div class="modal fade" id="itemsModal" tabindex="-1" aria-labelledby="itemsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="itemsModalLabel">
                            <i class="fas fa-shopping-bag text-primary me-2"></i>Detail Item Transaksi
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="itemsList"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}

    <script>
        $(document).ready(function() {
            // Initialize tooltips
            $('body').tooltip({
                selector: '[data-bs-toggle="tooltip"]'
            });

            // Get DataTable instance
            const table = window.LaravelDataTables['transaksi-table'];
            const currentYear = new Date().getFullYear();

            // Populate tahun filter
            for (let y = currentYear; y >= 2020; y--) {
                $('#filter-tahun').append(`<option value="${y}">${y}</option>`);
            }
            $('#filter-tahun').val(currentYear);

            // ===================================
            // FILTER FUNCTIONALITY
            // ===================================

            // Send filter data to server
            table.on('preXhr.dt', function(e, settings, data) {
                data.tahun = $('#filter-tahun').val();
                data.bulan = $('#filter-bulan').val();
                data.status = $('#filter-status').val();
                data.search_custom = $('#filter-search').val();
            });

            // Reload table on filter change
            $('#filter-tahun, #filter-bulan, #filter-status').on('change', function() {
                table.ajax.reload();
            });

            // Search with delay 500ms
            let searchTimeout;
            $('#filter-search').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    table.ajax.reload();
                }, 500);
            });

            // ===================================
            // EXPORT EXCEL
            // ===================================

            $('#export-excel').on('click', function(e) {
                e.preventDefault();

                let tahun = $('#filter-tahun').val();
                let bulan = $('#filter-bulan').val();
                let status = $('#filter-status').val();
                let search = $('#filter-search').val();

                let url = "{{ route('admin.transaksi.export.excel') }}";
                let params = [];

                if (tahun) params.push('tahun=' + tahun);
                if (bulan) params.push('bulan=' + bulan);
                if (status) params.push('status=' + status);
                if (search) params.push('search=' + encodeURIComponent(search));

                if (params.length > 0) {
                    url += '?' + params.join('&');
                }

                window.location.href = url;
            });

            // ===================================
            // UPDATE STATUS MODAL
            // ===================================

            $(document).on('click', '.btn-update-status', function(e) {
                e.preventDefault();
                const transaksiId = $(this).data('id');
                const currentStatus = $(this).data('status');

                $('#transaksi_id').val(transaksiId);
                $('#status_pembayaran').val(currentStatus);
                $('#updateStatusModal').modal('show');
            });

            $('#updateStatusForm').on('submit', function(e) {
                e.preventDefault();

                const transaksiId = $('#transaksi_id').val();
                const status = $('#status_pembayaran').val();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();

                if (!transaksiId || !status) {
                    Swal.fire('Error!', 'Data transaksi atau status tidak valid', 'error');
                    return;
                }

                submitBtn.prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin me-1"></i>Memperbarui...');

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    url: `/admin/transaksi/${transaksiId}/update-status`,
                    type: 'PUT',
                    data: {
                        status_pembayaran: status,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#updateStatusModal').modal('hide');

                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message || 'Status berhasil diperbarui',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });

                            table.ajax.reload(null, false);
                        } else {
                            Swal.fire('Gagal!', response.message || 'Gagal memperbarui status',
                                'error');
                        }
                    },
                    error: function(xhr) {
                        let message = 'Terjadi kesalahan saat memperbarui status';

                        if (xhr.responseJSON?.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.status === 404) {
                            message = 'Route tidak ditemukan';
                        } else if (xhr.status === 422) {
                            message = 'Data tidak valid';
                        } else if (xhr.status === 500) {
                            message = 'Kesalahan server';
                        }

                        Swal.fire('Error!', message, 'error');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // ===================================
            // SHOW ITEMS MODAL
            // ===================================

            $(document).on('click', '.show-items', function() {
                let items = $(this).data('items');
                let html = '';

                if (!items || items === '' || items === 'null' || items === null) {
                    html =
                        '<div class="alert alert-info mb-0">Tidak ada data items untuk transaksi ini.</div>';
                    $('#itemsList').html(html);
                    $('#itemsModal').modal('show');
                    return;
                }

                try {
                    let parsed;

                    if (typeof items === 'object') {
                        parsed = items;
                    } else if (typeof items === 'string') {
                        parsed = JSON.parse(items);
                    }

                    if (!parsed || !Array.isArray(parsed) || parsed.length === 0) {
                        html =
                            '<div class="alert alert-info mb-0">Tidak ada items dalam transaksi ini.</div>';
                    } else {
                        html = `
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th style="width: 50px;">No</th>
                                            <th>Nama Item</th>
                                            <th style="width: 120px;">Harga</th>
                                            <th style="width: 80px;">Qty</th>
                                            <th style="width: 120px;">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        let total = 0;

                        parsed.forEach((item, index) => {
                            const nama = item.nama || item.name || item.item_name || item
                                .product_name || '-';
                            const harga = parseFloat(item.harga || item.price || item.amount || 0);
                            const quantity = parseInt(item.quantity || item.qty || item.jumlah ||
                                1);
                            const subtotal = harga * quantity;
                            total += subtotal;

                            html += `
                                <tr>
                                    <td class="text-center">${index + 1}</td>
                                    <td>${nama}</td>
                                    <td class="text-end">Rp ${harga.toLocaleString('id-ID')}</td>
                                    <td class="text-center">${quantity}</td>
                                    <td class="text-end fw-bold">Rp ${subtotal.toLocaleString('id-ID')}</td>
                                </tr>
                            `;
                        });

                        html += `
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="4" class="text-end">Total:</th>
                                            <th class="text-end text-primary">Rp ${total.toLocaleString('id-ID')}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        `;
                    }
                } catch (e) {
                    console.error('Error parsing items:', e);
                    html =
                        '<div class="alert alert-danger mb-0">Error: Data items tidak valid atau rusak.</div>';
                }

                $('#itemsList').html(html);
                $('#itemsModal').modal('show');
            });

            // ===================================
            // DELETE TRANSACTION
            // ===================================

            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                const url = $(this).data('url');
                const transaksiId = $(this).closest('tr').find('td:nth-child(2)').text();

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    html: `Transaksi <strong>"${transaksiId}"</strong> akan dihapus dan tidak dapat dikembalikan!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash me-1"></i>Ya, Hapus!',
                    cancelButtonText: '<i class="fas fa-times me-1"></i>Batal',
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

                                if (xhr.responseJSON?.message) {
                                    message = xhr.responseJSON.message;
                                } else if (xhr.status === 404) {
                                    message = 'Data tidak ditemukan';
                                } else if (xhr.status === 403) {
                                    message = 'Anda tidak memiliki akses';
                                } else if (xhr.status === 500) {
                                    message = 'Kesalahan server';
                                }

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

            // Refresh tooltips after table draw
            table.on('draw.dt', function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            });
        });
    </script>
@endpush
