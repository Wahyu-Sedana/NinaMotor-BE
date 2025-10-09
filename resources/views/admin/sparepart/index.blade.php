@extends('admin.layouts.app')

@section('content')
    <div class="container mx-auto px-6 py-8">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2 text-gray-800">Data Sparepart</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Sparepart</li>
                    </ol>
                </nav>
            </div>

            <a href="{{ route('admin.sparepart.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Tambah Sparepart
            </a>
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

        {{-- TABEL --}}
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Data Sparepart</h6>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    {!! $dataTable->table(['class' => 'table table-bordered table-striped nowrap w-100'], true) !!}
                </div>
            </div>
        </div>
    </div>
@endsection

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i> Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Apakah Anda yakin ingin menghapus <strong id="itemName"></strong>?</p>
                <p class="text-muted small mt-1 mb-0">Data yang dihapus tidak dapat dikembalikan.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Batal
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-1"></i> Hapus
                </button>
            </div>
        </div>
    </div>
</div>


@push('scripts')
    {!! $dataTable->scripts() !!}

    <script>
        $(document).ready(function() {
            $('body').tooltip({
                selector: '[data-bs-toggle="tooltip"]'
            });

            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                const url = $(this).data('url');
                const sparepartName = $(this).closest('tr').find('td:nth-child(2)').text();

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    html: `Sparepart <strong>"${sparepartName}"</strong> akan dihapus dan tidak dapat dikembalikan!`,
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
                                    $('#sparepart-table').DataTable().ajax
                                        .reload(null, false);
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

            $('#sparepart-table').on('draw.dt', function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            });
        });
    </script>
@endpush
