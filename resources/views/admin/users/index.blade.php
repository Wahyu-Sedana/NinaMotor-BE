@extends('admin.layouts.app')

@section('title', 'User - Admin Panel')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <strong>Data User</strong>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                            <i class="icon cil-plus"></i> Tambah User
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Search and Filter -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Cari user..." id="searchInput">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="icon cil-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col">
                                <select class="form-select" id="roleFilter">
                                    <option value="">Semua Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="user">User</option>
                                    <option value="moderator">Moderator</option>
                                </select>
                            </div>
                            <div class="col">
                                <select class="form-select" id="statusFilter">
                                    <option value="">Semua Status</option>
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Non-aktif</option>
                                    <option value="banned">Banned</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th width="60">Avatar</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Bergabung</th>
                                <th width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users ?? [] as $index => $user)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="avatar avatar-sm">
                                        <img class="avatar-img" src="{{ $user->avatar ?? 'https://via.placeholder.com/40x40' }}" alt="{{ $user->name ?? 'User' }}">
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $user->name ?? 'Sample User ' . ($index + 1) }}</strong>
                                    @if($user->is_verified ?? ($index % 2))
                                        <i class="icon cil-check-circle text-success ms-1" title="Terverifikasi"></i>
                                    @endif
                                </td>
                                <td>{{ $user->email ?? 'user' . ($index + 1) . '@example.com' }}</td>
                                <td>
                                    <span class="badge {{ 
                                        ($user->role ?? ['admin', 'user', 'moderator'][$index % 3]) == 'admin' ? 'bg-danger' : 
                                        (($user->role ?? ['admin', 'user', 'moderator'][$index % 3]) == 'moderator' ? 'bg-warning' : 'bg-info') 
                                    }}">
                                        {{ ucfirst($user->role ?? ['admin', 'user', 'moderator'][$index % 3]) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ 
                                        ($user->status ?? ['aktif', 'nonaktif', 'banned'][$index % 3]) == 'aktif' ? 'bg-success' : 
                                        (($user->status ?? ['aktif', 'nonaktif', 'banned'][$index % 3]) == 'banned' ? 'bg-danger' : 'bg-secondary') 
                                    }}">
                                        {{ ucfirst($user->status ?? ['aktif', 'nonaktif', 'banned'][$index % 3]) }}
                                    </span>
                                </td>
                                <td>
                                    <small>{{ $user->created_at ? $user->created_at->format('d M Y') : date('d M Y', strtotime('-' . ($index * 5) . ' days')) }}</small>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-coreui-toggle="dropdown">
                                            Aksi
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item text-warning" href="#" onclick="return confirm('Yakin ingin menonaktifkan user ini?')">
                                                    {{ ($user->status ?? ['aktif', 'nonaktif', 'banned'][$index % 3]) == 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </a>
                                            </li>
                                            <li>
                                                <form action="{{ route('admin.users.destroy', $user->id ?? $index + 1) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Yakin ingin menghapus user ini?')">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <!-- Sample data jika $users kosong -->
                            @for($i = 0; $i < 8; $i++)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <div class="avatar avatar-sm">
                                        <img class="avatar-img" src="https://via.placeholder.com/40x40" alt="User">
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ ['John Doe', 'Jane Smith', 'Bob Johnson', 'Alice Brown', 'Mike Wilson', 'Sarah Davis', 'Tom Anderson', 'Lisa Taylor'][$i] }}</strong>
                                    @if($i % 2)
                                        <i class="icon cil-check-circle text-success ms-1" title="Terverifikasi"></i>
                                    @endif
                                </td>
                                <td>{{ strtolower(str_replace(' ', '.', ['John Doe', 'Jane Smith', 'Bob Johnson', 'Alice Brown', 'Mike Wilson', 'Sarah Davis', 'Tom Anderson', 'Lisa Taylor'][$i])) }}@example.com</td>
                                <td>
                                    <span class="badge {{ 
                                        ['admin', 'user', 'moderator'][$i % 3] == 'admin' ? 'bg-danger' : 
                                        (['admin', 'user', 'moderator'][$i % 3] == 'moderator' ? 'bg-warning' : 'bg-info') 
                                    }}">
                                        {{ ucfirst(['admin', 'user', 'moderator'][$i % 3]) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ 
                                        ['aktif', 'nonaktif', 'banned'][$i % 3] == 'aktif' ? 'bg-success' : 
                                        (['aktif', 'nonaktif', 'banned'][$i % 3] == 'banned' ? 'bg-danger' : 'bg-secondary') 
                                    }}">
                                        {{ ucfirst(['aktif', 'nonaktif', 'banned'][$i % 3]) }}
                                    </span>
                                </td>
                                <td>
                                    <small>{{ date('d M Y', strtotime('-' . ($i * 5) . ' days')) }}</small>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-coreui-toggle="dropdown">
                                            Aksi
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#">Detail</a></li>
                                            <li><a class="dropdown-item" href="#">Edit</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-warning" href="#" onclick="return confirm('Yakin ingin menonaktifkan user ini?')">
                                                {{ ['aktif', 'nonaktif', 'banned'][$i % 3] == 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </a></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="return confirm('Yakin ingin menghapus user ini?')">Hapus</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @endfor
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Menampilkan 1-8 dari 8 user
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm">
                            <li class="page-item disabled">
                                <span class="page-link">Previous</span>
                            </li>
                            <li class="page-item active">
                                <span class="page-link">1</span>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link">Next</span>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const roleValue = roleFilter.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const userName = row.cells[2].textContent.toLowerCase();
            const userEmail = row.cells[3].textContent.toLowerCase();
            const role = row.cells[4].textContent.toLowerCase();
            const status = row.cells[5].textContent.toLowerCase();
            
            const matchesSearch = userName.includes(searchTerm) || userEmail.includes(searchTerm);
            const matchesRole = roleValue === '' || role.includes(roleValue);
            const matchesStatus = statusValue === '' || status.includes(statusValue);
            
            if (matchesSearch && matchesRole && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    searchInput.addEventListener('input', filterTable);
    roleFilter.addEventListener('change', filterTable);
    statusFilter.addEventListener('change', filterTable);
});
</script>
@endpush" href="{{ route('admin.users.show', $user->id ?? $index + 1) }}">Detail</a></li>
                                            <li><a class="dropdown-item" href="{{ route('admin.users.edit', $user->id ?? $index + 1) }}">Edit</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item