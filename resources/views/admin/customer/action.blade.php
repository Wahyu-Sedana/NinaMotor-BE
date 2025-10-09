     <a href="{{ route('admin.customer.edit', $row->id) }}" class="btn btn-sm btn-warning" data-bs-toggle="tooltip"
         title="Edit">
         <i class="fas fa-edit"></i>
     </a>

     {{-- Button Delete --}}
     <button type="button" class="btn btn-sm btn-danger btn-delete"
         data-url="{{ route('admin.customer.destroy', $row->id) }}" data-bs-toggle="tooltip" title="Hapus">
         <i class="fas fa-trash"></i>
     </button>
