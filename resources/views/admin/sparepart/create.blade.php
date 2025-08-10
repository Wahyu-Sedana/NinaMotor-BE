@extends('admin.layouts.app')

@section('content')
    <div class="container container mx-auto px-6 py-8">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2 text-gray-800">Tambah Sparepart</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.sparepart.index') }}">Sparepart</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Tambah</li>
                    </ol>
                </nav>
            </div>

            <a href="{{ route('admin.sparepart.index') }}" class="btn btn-secondary">
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
                <h6 class="m-0 font-weight-bold text-primary">Form Tambah Sparepart</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.sparepart.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        {{-- Kode Sparepart --}}
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="kode_sparepart" class="form-label">Kode Sparepart <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('kode_sparepart') is-invalid @enderror"
                                    id="kode_sparepart" name="kode_sparepart" value="{{ old('kode_sparepart') }}"
                                    placeholder="Masukkan kode sparepart" required>
                                @error('kode_sparepart')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Nama Sparepart --}}
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nama_sparepart" class="form-label">Nama Sparepart <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nama_sparepart') is-invalid @enderror"
                                    id="nama_sparepart" name="nama_sparepart" value="{{ old('nama_sparepart') }}"
                                    placeholder="Masukkan nama sparepart" required>
                                @error('nama_sparepart')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Kategori --}}
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="kategori_id" class="form-label">Kategori <span
                                        class="text-danger">*</span></label>
                                <select class="form-select @error('kategori_id') is-invalid @enderror" id="kategori_id"
                                    name="kategori_id" required>
                                    <option value="">Pilih Kategori</option>
                                    @foreach ($kategoris as $kategori)
                                        <option value="{{ $kategori->id }}"
                                            {{ old('kategori_id') == $kategori->id ? 'selected' : '' }}>
                                            {{ $kategori->nama_kategori }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('kategori_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Merk --}}
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="merk" class="form-label">Merk</label>
                                <input type="text" class="form-control @error('merk') is-invalid @enderror"
                                    id="merk" name="merk" value="{{ old('merk') }}"
                                    placeholder="Masukkan merk sparepart">
                                @error('merk')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Harga Beli --}}
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="harga_beli" class="form-label">Harga Beli <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('harga_beli') is-invalid @enderror"
                                        id="harga_beli" name="harga_beli" value="{{ old('harga_beli') }}" placeholder="0"
                                        min="0" step="0.01" required>
                                </div>
                                @error('harga_beli')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Harga Jual --}}
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="harga_jual" class="form-label">Harga Jual <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('harga_jual') is-invalid @enderror"
                                        id="harga_jual" name="harga_jual" value="{{ old('harga_jual') }}"
                                        placeholder="0" min="0" step="0.01" required>
                                </div>
                                @error('harga_jual')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Stok --}}
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="stok" class="form-label">Stok <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('stok') is-invalid @enderror"
                                    id="stok" name="stok" value="{{ old('stok') }}" placeholder="0"
                                    min="0" required>
                                @error('stok')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Deskripsi --}}
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <textarea class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" name="deskripsi"
                                    rows="3" placeholder="Masukkan deskripsi sparepart">{{ old('deskripsi') }}</textarea>
                                @error('deskripsi')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Gambar Produk --}}
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gambar_produk" class="form-label">Gambar Produk</label>
                                <input type="file" class="form-control @error('gambar_produk') is-invalid @enderror"
                                    id="gambar_produk" name="gambar_produk" accept="image/jpeg,image/jpg,image/png">
                                <div class="form-text">Format: JPG, JPEG, PNG. Maksimal 2MB.</div>
                                @error('gambar_produk')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Preview Gambar --}}
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Preview Gambar</label>
                                <div class="border rounded p-3 text-center" style="min-height: 150px;">
                                    <img id="image-preview" src="" alt="Preview" class="img-fluid"
                                        style="max-height: 130px; display: none;">
                                    <div id="no-image" class="text-muted">
                                        <i class="fas fa-image fa-3x mb-2"></i>
                                        <p>Tidak ada gambar dipilih</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('admin.sparepart.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Preview gambar saat file dipilih
            $('#gambar_produk').on('change', function() {
                const file = this.files[0];
                const preview = $('#image-preview');
                const noImage = $('#no-image');

                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.attr('src', e.target.result).show();
                        noImage.hide();
                    }
                    reader.readAsDataURL(file);
                } else {
                    preview.hide();
                    noImage.show();
                }
            });

            // Auto calculate suggested selling price (optional)
            $('#harga_beli').on('input', function() {
                const hargaBeli = parseFloat($(this).val()) || 0;
                const suggestedPrice = Math.round(hargaBeli * 1.3); // 30% markup

                if (hargaBeli > 0 && $('#harga_jual').val() === '') {
                    $('#harga_jual').val(suggestedPrice);
                }
            });

            // Format number input dengan separator
            $('#harga_beli, #harga_jual').on('blur', function() {
                const value = parseFloat($(this).val());
                if (!isNaN(value)) {
                    $(this).val(value.toFixed(0));
                }
            });
        });
    </script>
@endpush
