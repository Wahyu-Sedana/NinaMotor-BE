<a href="{{ route('admin.kategori-sparepart.edit', $row->id) }}" class="btn btn-sm btn-warning"> <i
        class="fas fa-edit"></i></a>
<form action="{{ route('admin.kategori-sparepart.destroy', $row->nama_kategori) }}" method="POST" style="display:inline;">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-danger btn-delete">
        <i class="fas fa-trash"></i>
    </button>
</form>
