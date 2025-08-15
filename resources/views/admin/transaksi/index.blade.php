@extends('admin.layouts.app')

@section('title', $title)

@section('content')
    <div class="container mx-auto px-6 py-8">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">{{ $title }}</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
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

        {{-- DataTable --}}
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Data Transaksi</h6>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    {!! $dataTable->table(
                        ['class' => 'table table-bordered table-striped nowrap w-100', 'id' => 'transaksi-table'],
                        true,
                    ) !!}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Update Status --}}
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Status Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="updateStatusForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status_pembayaran" class="form-label">Status Pembayaran</label>
                            <select class="form-select" id="status_pembayaran" name="status_pembayaran" required>
                                <option value="">Pilih Status</option>
                                <option value="pending">Pending</option>
                                <option value="berhasil">Paid</option>
                                <option value="gagal">Failed</option>
                                <option value="expired">Expired</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <input type="hidden" id="transaksi_id" name="transaksi_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="itemsModal" tabindex="-1" aria-labelledby="itemsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="itemsModalLabel">Detail Item Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul id="itemsList" class="mb-0"></ul>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}

    <script>
        $(document).ready(function() {
            $('body').tooltip({
                selector: '[data-bs-toggle="tooltip"]'
            });


            $(document).on('click', '.btn-update-status', function(e) {
                e.preventDefault();
                const transaksiId = $(this).data('id');
                const currentStatus = $(this).data('status');

                console.log('Transaksi ID:', transaksiId, 'Current Status:', currentStatus); // Debug

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
                    '<i class="fas fa-spinner fa-spin"></i> Memperbarui...');

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
                        console.log('Response:', response);

                        if (response.success) {
                            $('#updateStatusModal').modal('hide');

                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message || 'Status berhasil diperbarui',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });

                            // Reload DataTable dan statistik
                            $('#transaksi-table').DataTable().ajax.reload(null, false);
                            loadStatistics();
                        } else {
                            Swal.fire('Gagal!', response.message || 'Gagal memperbarui status',
                                'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', xhr.responseText); // Debug

                        let msg = 'Terjadi kesalahan saat memperbarui status';

                        if (xhr.status === 404) {
                            msg = 'Route tidak ditemukan. Periksa konfigurasi route.';
                        } else if (xhr.status === 422) {
                            msg = 'Data yang dikirim tidak valid';
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            try {
                                const errorResponse = JSON.parse(xhr.responseText);
                                msg = errorResponse.message || msg;
                            } catch (e) {

                            }
                        }

                        Swal.fire('Error!', msg, 'error');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            $(document).on('click', '.show-items', function() {
                let items = $(this).data('items');
                let html = '';

                console.log('Items data:', items);

                if (!items || items === '' || items === 'null' || items === null) {
                    html = '<li class="list-group-item">Tidak ada data items untuk transaksi ini.</li>';
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

                    console.log('Parsed items:', parsed);

                    if (!parsed || !Array.isArray(parsed) || parsed.length === 0) {
                        html = '<li class="list-group-item">Tidak ada items dalam transaksi ini.</li>';
                    } else {
                        html = `
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Nama Item</th>
                                <th>Harga</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
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
                        <td>${index + 1}</td>
                        <td>${nama}</td>
                        <td>Rp ${harga.toLocaleString('id-ID')}</td>
                        <td>${quantity}</td>
                        <td>Rp ${subtotal.toLocaleString('id-ID')}</td>
                    </tr>
                `;
                        });

                        html += `
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Total:</th>
                                <th>Rp ${total.toLocaleString('id-ID')}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
                    }
                } catch (e) {
                    console.error('Error parsing items:', e);
                    console.error('Raw items data:', items);
                    html =
                        '<li class="list-group-item text-danger">Error: Data items tidak valid atau rusak.</li>';
                }

                $('#itemsList').html(html);
                $('#itemsModal').modal('show');
            });

            // Delete Transaction
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
                    confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
                    cancelButtonText: '<i class="fas fa-times"></i> Batal',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Berhasil!',
                                        text: response.message,
                                        icon: 'success',
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                    $('#transaksi-table').DataTable().ajax.reload(null,
                                        false);
                                } else {
                                    Swal.fire('Gagal!', response.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                let msg = 'Terjadi kesalahan saat menghapus data';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    msg = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', msg, 'error');
                            }
                        });
                    }
                });
            });

            $('#transaksi-table').on('draw.dt', function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            });
        });
    </script>
@endpush
