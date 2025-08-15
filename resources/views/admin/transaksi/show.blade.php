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
                        <li class="breadcrumb-item"><a href="{{ route('admin.transaksi.index') }}">Transaksi</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Detail</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.transaksi.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <button class="btn btn-warning btn-update-status" data-id="{{ $transaksi->id }}"
                    data-status="{{ $transaksi->status_pembayaran }}">
                    <i class="fas fa-edit"></i> Update Status
                </button>
            </div>
        </div>

        <!-- Alert Messages -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Transaction Details Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Informasi Transaksi</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Basic Information -->
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>ID Transaksi:</strong></td>
                                <td>{{ $transaksi->id }}</td>
                            </tr>
                            <tr>
                                <td><strong>Order ID:</strong></td>
                                <td>{{ $transaksi->order_id ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Kode Transaksi:</strong></td>
                                <td>{{ $transaksi->kode_transaksi ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Nama Customer:</strong></td>
                                <td>{{ $transaksi->user->name ?? ($transaksi->nama_customer ?? '-') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td>{{ $transaksi->user->email ?? ($transaksi->email_customer ?? '-') }}</td>
                            </tr>
                            <tr>
                                <td><strong>No. Telepon:</strong></td>
                                <td>{{ $transaksi->no_telepon ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>

                    <!-- Payment Information -->
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Status Pembayaran:</strong></td>
                                <td>
                                    @switch($transaksi->status_pembayaran)
                                        @case('paid')
                                            <span class="badge bg-success">Paid</span>
                                        @break

                                        @case('pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @break

                                        @case('failed')
                                            <span class="badge bg-danger">Failed</span>
                                        @break

                                        @case('expired')
                                            <span class="badge bg-secondary">Expired</span>
                                        @break

                                        @case('cancelled')
                                            <span class="badge bg-dark">Cancelled</span>
                                        @break

                                        @default
                                            <span
                                                class="badge bg-light text-dark">{{ ucfirst($transaksi->status_pembayaran) }}</span>
                                    @endswitch
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Total Amount:</strong></td>
                                <td>
                                    <span class="h5 text-success">
                                        Rp
                                        {{ number_format($transaksi->gross_amount ?? ($transaksi->total_amount ?? 0), 0, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Payment Type:</strong></td>
                                <td>{{ $transaksi->payment_type ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Bank:</strong></td>
                                <td>{{ $transaksi->va_bank ?? ($transaksi->bank ?? '-') }}</td>
                            </tr>
                            <tr>
                                <td><strong>VA Number:</strong></td>
                                <td>{{ $transaksi->va_number ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Transaction Time:</strong></td>
                                <td>{{ $transaksi->transaction_time ? \Carbon\Carbon::parse($transaksi->transaction_time)->format('d/m/Y H:i:s') : '-' }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <hr>
                        <div class="d-flex justify-content-between">
                            <div>
                                <small class="text-muted">
                                    <strong>Dibuat:</strong> {{ $transaksi->created_at->format('d/m/Y H:i:s') }}
                                </small>
                            </div>
                            <div>
                                <small class="text-muted">
                                    <strong>Diupdate:</strong> {{ $transaksi->updated_at->format('d/m/Y H:i:s') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Details Card -->
        @if (!empty($itemsData))
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Detail Items</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Item</th>
                                    <th>Harga</th>
                                    <th>Qty</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $total = 0; @endphp
                                @foreach ($itemsData as $index => $item)
                                    @php
                                        $subtotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                                        $total += $subtotal;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item['name'] ?? ($item['item_name'] ?? '-') }}</td>
                                        <td>Rp {{ number_format($item['price'] ?? 0, 0, ',', '.') }}</td>
                                        <td>{{ $item['quantity'] ?? 1 }}</td>
                                        <td>Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end">Total:</th>
                                    <th>Rp {{ number_format($total, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Raw Data Card (for debugging) -->
        @if (config('app.debug'))
            <div class="card shadow mt-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Raw Data (Debug)</h6>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded">{{ json_encode($transaksi->toArray(), JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        @endif
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Status Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="updateStatusForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status_pembayaran" class="form-label">Status Pembayaran</label>
                            <select class="form-select" id="status_pembayaran" name="status_pembayaran" required>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                                <option value="expired">Expired</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Pastikan status yang dipilih sesuai dengan kondisi pembayaran yang sebenarnya.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Update Status Button Click
            $('.btn-update-status').on('click', function() {
                const id = $(this).data('id');
                const currentStatus = $(this).data('status');

                // Set form action
                $('#updateStatusForm').attr('action', `/admin/transaksi/${id}/status`);

                // Set current status as selected
                $('#status_pembayaran').val(currentStatus);

                // Show modal
                $('#updateStatusModal').modal('show');
            });

            // Form submission with loading state
            $('#updateStatusForm').on('submit', function(e) {
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.text();

                // Show loading state
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

                // Re-enable button after 3 seconds (fallback)
                setTimeout(function() {
                    submitBtn.prop('disabled', false).text(originalText);
                }, 3000);
            });

            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
@endpush

@push('styles')
    <style>
        .table-borderless td {
            border: none !important;
            padding: 0.5rem 0;
        }

        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15);
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        pre {
            max-height: 400px;
            overflow-y: auto;
            font-size: 0.875rem;
        }

        .badge {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
        }

        .alert {
            border: none;
            border-radius: 0.35rem;
        }
    </style>
@endpush
