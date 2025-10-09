     {{-- Button Edit --}}
     <a href="{{ route('admin.kategori-sparepart.edit', $row->id) }}" class="btn btn-sm btn-warning"
         data-bs-toggle="tooltip" title="Edit">
         <i class="fas fa-edit"></i>
     </a>

     {{-- Button Delete --}}
     <button type="button" class="btn btn-sm btn-danger btn-delete"
         data-url="{{ route('admin.kategori-sparepart.destroy', $row->id) }}" data-bs-toggle="tooltip" title="Hapus">
         <i class="fas fa-trash"></i>
     </button>
