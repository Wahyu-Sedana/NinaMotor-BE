@extends('admin.layouts.app')

@section('content')
    <div class="container mx-auto px-6 py-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2 text-gray-800">Dashboard</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-2">Selamat datang di Admin Panel!</h2>
            <p class="text-gray-600">Kelola aplikasi Anda dengan mudah menggunakan panel admin ini.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Total Produk --}}
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6 rounded-lg text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm">Total Produk</p>
                        <p class="text-3xl font-bold">{{ $totalProducts }}</p>
                    </div>
                    <div class="bg-blue-400 bg-opacity-30 p-3 rounded-full">
                        <i class="fas fa-box text-2xl"></i>
                    </div>
                </div>
            </div>

            {{-- Total User --}}
            <div class="bg-gradient-to-r from-green-500 to-green-600 p-6 rounded-lg text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm">Total User</p>
                        <p class="text-3xl font-bold">{{ $totalUsers }}</p>
                    </div>
                    <div class="bg-green-400 bg-opacity-30 p-3 rounded-full">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                </div>
            </div>

            {{-- Total Order --}}
            <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 p-6 rounded-lg text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 text-sm">Total Order</p>
                        <p class="text-3xl font-bold">{{ $totalOrder }}</p>
                    </div>
                    <div class="bg-yellow-400 bg-opacity-30 p-3 rounded-full">
                        <i class="fas fa-shopping-cart text-2xl"></i>
                    </div>
                </div>
            </div>

            {{-- Total Transaksi --}}
            <div class="bg-gradient-to-r from-red-500 to-red-600 p-6 rounded-lg text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-100 text-sm">Total Transaksi</p>
                        <p class="text-3xl font-bold">Rp {{ number_format($totalTransaksi, 0, ',', '.') }}</p>
                    </div>
                    <div class="bg-red-400 bg-opacity-30 p-3 rounded-full">
                        <i class="fas fa-dollar-sign text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cart Total Order dan Traffic Sources (tetap statis) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Cart Total Order Bulanan</h3>
                <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
                    <canvas id="cartTotalOrderChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Cart Total Transaksi Bulanan</h3>
                <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
                    <canvas id="cartTotalTransaksiChart"></canvas>
                </div>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script>
            // Sidebar functionality
            const openSidebar = document.getElementById('openSidebar');
            const closeSidebar = document.getElementById('closeSidebar');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            openSidebar?.addEventListener('click', () => {
                sidebar.classList.remove('-translate-x-full');
                sidebarOverlay.classList.remove('hidden');
            });

            closeSidebar?.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
            });

            sidebarOverlay?.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
            });

            // Profile dropdown
            const profileDropdown = document.getElementById('profileDropdown');
            const profileMenu = document.getElementById('profileMenu');

            profileDropdown?.addEventListener('click', (e) => {
                e.stopPropagation();
                profileMenu.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!profileDropdown?.contains(e.target)) {
                    profileMenu?.classList.add('hidden');
                }
            });

            // Close mobile sidebar on window resize
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) {
                    sidebar.classList.remove('-translate-x-full');
                    sidebarOverlay.classList.add('hidden');
                }
            });
            const ctx = document.getElementById('cartTotalOrderChart');
            const ctx1 = document.getElementById('cartTotalTransaksiChart');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($months),
                    datasets: [{
                        label: 'Total Order per Bulan',
                        data: @json($totals),
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: @json($months),
                    datasets: [{
                        label: 'Total Transaksi per Bulan',
                        data: @json($totals),
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            })
        </script>
    @endpush
