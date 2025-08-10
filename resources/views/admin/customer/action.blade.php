<a href="{{ route('admin.customer.edit', $row->id) }}" class="btn btn-sm btn-warning" data-bs-toggle="tooltip"
    title="Edit">
    <i class="fas fa-edit"></i>
</a>

<form action="{{ route('admin.customer.destroy', $row->id) }}" method="POST" style="display:inline;">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-danger btn-delete" data-bs-toggle="tooltip" title="Hapus"
        onclick="return confirm('Yakin ingin hapus data ini?')">
        <i class="fas fa-trash"></i>
    </button>
</form>
