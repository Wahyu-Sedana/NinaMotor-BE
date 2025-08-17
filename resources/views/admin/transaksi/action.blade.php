<a href="javascript:void(0);" class="btn btn-sm btn-warning btn-update-status" data-id="{{ $row->id }}"
    data-status="{{ $row->status_pembayaran }}" data-bs-toggle="tooltip" title="Update Status">
    <i class="fas fa-edit"></i>
</a>

<button class="btn btn-info btn-sm show-items" data-items="{{ $row->items_data }}" data-bs-toggle="tooltip"
    title="Lihat Items">
    <i class="fas fa-eye"></i>
</button>

{{-- <form action="{{ route('admin.transaksi.destroy', $row->id) }}" method="POST" style="display:inline;">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-danger btn-delete"
        data-url="{{ route('admin.transaksi.destroy', $row->id) }}" data-bs-toggle="tooltip" title="Hapus">
        <i class="fas fa-trash"></i>
    </button>
</form> --}}
