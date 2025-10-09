     <a href="{{ route('admin.sparepart.edit', $row->kode_sparepart) }}" class="btn btn-sm btn-warning"
         data-bs-toggle="tooltip" title="Edit">
         <i class="fas fa-edit"></i>
     </a>

     {{-- Button Delete --}}
     <button type="button" class="btn btn-sm btn-danger btn-delete"
         data-url="{{ route('admin.sparepart.destroy', $row->kode_sparepart) }}" data-bs-toggle="tooltip" title="Hapus">
         <i class="fas fa-trash"></i>
     </button>
