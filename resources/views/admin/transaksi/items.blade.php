@extends('admin.layouts.app')

@section('content')
    <div class="container mx-auto px-6 py-8">
        <h1 class="text-2xl font-bold mb-4">Items Transaksi #{{ $transaksi->id }}</h1>

        @if (count($itemsData) > 0)
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nama Item</th>
                        <th>Kuantitas</th>
                        <th>Harga</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($itemsData as $item)
                        <tr>
                            <td>{{ $item['nama'] ?? '-' }}</td>
                            <td>{{ $item['qty'] ?? 0 }}</td>
                            <td>Rp {{ number_format($item['harga'] ?? 0, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format(($item['qty'] ?? 0) * ($item['harga'] ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Tidak ada data item untuk transaksi ini.</p>
        @endif

        <a href="{{ route('admin.transaksi.index') }}" class="btn btn-secondary mt-4">Kembali</a>
    </div>
@endsection
