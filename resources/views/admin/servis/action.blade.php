<a href="{{ route('admin.servis.edit', $row->id) }}" class="btn btn-sm btn-warning">
    <i class="fas fa-edit"></i>
</a>
<button type="button" class="btn btn-sm btn-danger btn-delete" data-url="{{ route('admin.servis.destroy', $row->id) }}"
    data-bs-toggle="tooltip" title="Hapus">
    <i class="fas fa-trash"></i>
</button>
