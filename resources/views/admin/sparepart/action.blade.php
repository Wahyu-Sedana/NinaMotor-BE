<a href="{{ route('admin.sparepart.edit', $row->kode_sparepart) }}" class="btn btn-sm btn-warning"> <i
        class="fas fa-edit"></i></a>
<form action="{{ route('admin.sparepart.destroy', $row->kode_sparepart) }}" method="POST" style="display:inline;">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-danger btn-delete">
        <i class="fas fa-trash"></i>
    </button>
</form>
