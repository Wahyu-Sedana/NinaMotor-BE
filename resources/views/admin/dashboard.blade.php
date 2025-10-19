@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-3 px-md-4 py-4">
        {{-- Header --}}
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

        {{-- Welcome Card --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-2">Selamat datang di Admin Panel!</h2>
                <p class="text-muted mb-0">Kelola aplikasi Anda dengan mudah menggunakan panel admin ini.</p>
            </div>
        </div>

        {{-- Statistics Cards - RESPONSIVE --}}
        <div class="row g-3 g-md-4 mb-4">
            {{-- Total Produk --}}
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="flex-grow-1">
                                <p class="text-muted small mb-1">Total Produk</p>
                                <h3 class="mb-0 fw-bold">{{ $totalProducts }}</h3>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="fas fa-box fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-primary bg-opacity-10 border-0 py-2">
                        <small class="text-primary">
                            <i class="fas fa-chart-line me-1"></i>
                            Produk tersedia
                        </small>
                    </div>
                </div>
            </div>

            {{-- Total User --}}
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="flex-grow-1">
                                <p class="text-muted small mb-1">Total User</p>
                                <h3 class="mb-0 fw-bold">{{ $totalUsers }}</h3>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-success bg-opacity-10 border-0 py-2">
                        <small class="text-success">
                            <i class="fas fa-user-plus me-1"></i>
                            Pengguna terdaftar
                        </small>
                    </div>
                </div>
            </div>

            {{-- Total Order --}}
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="flex-grow-1">
                                <p class="text-muted small mb-1">Total Order</p>
                                <h3 class="mb-0 fw-bold">{{ $totalOrder }}</h3>
                            </div>
                            <div class="text-warning opacity-75">
                                <i class="fas fa-shopping-cart fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-warning bg-opacity-10 border-0 py-2">
                        <small class="text-warning">
                            <i class="fas fa-clipboard-list me-1"></i>
                            Pesanan masuk
                        </small>
                    </div>
                </div>
            </div>

            {{-- Total Transaksi --}}
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 p-md-4">
                        <div class="flex-grow-1">
                            <p class="text-muted small mb-1">Total Transaksi</p>
                            <h3 class="mb-0 fw-bold fs-6 fs-md-4">
                                Rp {{ number_format($totalTransaksi, 0, ',', '.') }}
                            </h3>
                        </div>
                        <div class="mt-2 text-danger opacity-75">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                    <div class="card-footer bg-danger bg-opacity-10 border-0 py-2">
                        <small class="text-danger">
                            <i class="fas fa-money-bill-wave me-1"></i>
                            Total pendapatan
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- NOTIFIKASI SERVIS TERBARU --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="fas fa-bell text-warning me-2"></i>
                        Notifikasi Terbaru
                        <span id="unreadBadge" class="badge bg-danger ms-2" style="display: none;">0</span>
                    </h5>
                    <button onclick="markAllAsRead()" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-check-double"></i>
                        <span class="d-none d-sm-inline ms-1">Tandai Semua Dibaca</span>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="notificationList">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                        <p>Memuat notifikasi...</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Data Section --}}
        <div class="row g-3 g-md-4">
            {{-- Recent Transaksi --}}
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-shopping-cart text-warning me-2"></i>
                                Transaksi Terbaru
                            </h5>
                            <a href="{{ route('admin.transaksi.index') }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-arrow-right me-1"></i>Lihat Semua
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">ID Order</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th class="pe-3">Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentTransaksi ?? [] as $transaksi)
                                        <tr>
                                            <td class="ps-3">
                                                <span class="badge bg-secondary">{{ $transaksi->id }}</span>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 150px;">
                                                    {{ $transaksi->user->nama ?? '-' }}
                                                </div>
                                            </td>
                                            <td>
                                                <strong>Rp {{ number_format($transaksi->total, 0, ',', '.') }}</strong>
                                            </td>
                                            <td>
                                                @if ($transaksi->status_pembayaran == 'berhasil')
                                                    <span class="badge bg-success">Berhasil</span>
                                                @elseif($transaksi->status_pembayaran == 'pending')
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                @elseif($transaksi->status_pembayaran == 'gagal')
                                                    <span class="badge bg-danger">Gagal</span>
                                                @else
                                                    <span
                                                        class="badge bg-secondary">{{ ucfirst($transaksi->status_pembayaran) }}</span>
                                                @endif
                                            </td>
                                            <td class="pe-3">
                                                <small class="text-muted">
                                                    {{ $transaksi->created_at->diffForHumans() }}
                                                </small>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                                Belum ada transaksi
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Servis Motor --}}
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-wrench text-primary me-2"></i>
                                Servis Motor Terbaru
                            </h5>
                            <a href="{{ route('admin.servis.index') }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-arrow-right me-1"></i>Lihat Semua
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Customer</th>
                                        <th>No Kendaraan</th>
                                        <th>Jenis Motor</th>
                                        <th>Status</th>
                                        <th class="pe-3">Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentServis ?? [] as $servis)
                                        <tr>
                                            <td class="ps-3">
                                                <div class="text-truncate" style="max-width: 120px;">
                                                    {{ $servis->user->nama ?? '-' }}
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $servis->no_kendaraan }}</span>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 100px;">
                                                    {{ $servis->jenis_motor }}
                                                </div>
                                            </td>
                                            <td>
                                                @if ($servis->status == 'pending')
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                @elseif($servis->status == 'in_service')
                                                    <span class="badge bg-primary">Proses</span>
                                                @elseif($servis->status == 'done')
                                                    <span class="badge bg-success">Selesai</span>
                                                @elseif($servis->status == 'priced')
                                                    <span class="badge bg-info">Pembayaran</span>
                                                @else
                                                    <span class="badge bg-danger">Ditolak</span>
                                                @endif
                                            </td>
                                            <td class="pe-3">
                                                <small class="text-muted">
                                                    {{ $servis->created_at->diffForHumans() }}
                                                </small>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                                Belum ada servis motor
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Audio untuk notifikasi --}}
    <audio id="notificationSound" preload="auto">
        <source src="{{ asset('sounds/notif.mp3') }}" type="audio/mpeg">
    </audio>

    {{-- Firebase SDK --}}
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js"></script>
@endsection

@push('styles')
    <style>
        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .h3 {
                font-size: 1.5rem;
            }

            .card-body h3 {
                font-size: 1.5rem !important;
            }

            .fa-2x {
                font-size: 1.5rem;
            }

            .table {
                font-size: 0.875rem;
            }

            .table th,
            .table td {
                padding: 0.5rem 0.25rem;
            }
        }

        /* Card hover effect */
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        /* Notification item styling */
        .notification-item {
            transition: all 0.3s ease;
            border-left: 3px solid #ffc107;
        }

        .notification-item:hover {
            background-color: #f8f9fa;
        }

        /* Table row hover */
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
    </style>
@endpush

@push('scripts')
    <script>
        // ===================================
        // FIREBASE CONFIGURATION
        // ===================================
        const firebaseConfig = {
            apiKey: "AIzaSyBeGc52irf9PIRUP62plxCqNmss1Bpe2ew",
            authDomain: "ninamotor-53934.firebaseapp.com",
            projectId: "ninamotor-53934",
            storageBucket: "ninamotor-53934.firebasestorage.app",
            messagingSenderId: "453165515440",
            appId: "1:453165515440:web:d5539cb05061c34feb7175",
            measurementId: "G-2H6951H8J5"
        };

        firebase.initializeApp(firebaseConfig);
        const messaging = firebase.messaging();

        // Request notification permission
        Notification.requestPermission().then((permission) => {
            if (permission === 'granted') {
                console.log('✅ Notification permission granted');

                messaging.getToken({
                    vapidKey: 'BFbgKpuLJZ9b1LdFTIqQR8sVppskr3YxtmAB8KxzQxoNI8NCfcBIT-bwz1jc7hZACHTL8B96EtE2JsVDxlIeZJM'
                }).then((currentToken) => {
                    if (currentToken) {
                        console.log('FCM Token:', currentToken);
                        subscribeToAdminTopic(currentToken);
                        subscribeToAdminTopicTransaksi(currentToken);
                    }
                }).catch((err) => {
                    console.error('Error getting token:', err);
                });
            }
        });

        // Subscribe functions
        function subscribeToAdminTopic(token) {
            fetch('{{ route('admin.subscribe-topic') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        token: token,
                        topic: 'admin_notifications'
                    })
                })
                .then(response => response.json())
                .then(data => console.log('✅ Subscribed to admin_notifications:', data))
                .catch(error => console.error('❌ Failed to subscribe:', error));
        }

        function subscribeToAdminTopicTransaksi(token) {
            fetch('{{ route('admin.subscribe-topic-transaksi') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        token: token,
                        topic: 'admin_notifications'
                    })
                })
                .then(response => response.json())
                .then(data => console.log('✅ Subscribed to admin_notifications transaksi:', data))
                .catch(error => console.error('❌ Failed to subscribe:', error));
        }

        // Listen for foreground messages
        messaging.onMessage((payload) => {
            console.log('📨 Firebase message received:', payload);
            playNotificationSound();

            setTimeout(() => {
                loadUnreadNotifications();
                loadUnreadNotificationsTransaksi();
                updateUnreadCount();
            }, 500);

            if (Notification.permission === 'granted') {
                const notification = payload.notification || {};
                const data = payload.data || {};

                new Notification(notification.title || 'Notifikasi Baru', {
                    body: notification.body || 'Ada notifikasi baru untuk Anda',
                    icon: '/images/logo.png',
                    tag: 'notif-' + (data.servis_id || Date.now()),
                    requireInteraction: true,
                    data: data
                });
            }
        });

        // ===================================
        // NOTIFICATION FUNCTIONS
        // ===================================

        function loadUnreadNotifications() {
            fetch('{{ route('admin.notifications.unread') }}')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data && result.data.length > 0) {
                        displayNotifications(result.data);
                    } else {
                        showEmptyState();
                    }
                })
                .catch(error => {
                    console.error('❌ Error loading notifications:', error);
                    showErrorState();
                });
        }

        function loadUnreadNotificationsTransaksi() {
            fetch('{{ route('admin.notifications.unread-transaksi') }}')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data && result.data.length > 0) {
                        displayNotifications(result.data);
                    }
                })
                .catch(error => console.error('❌ Error loading notifications transaksi:', error));
        }

        function displayNotifications(notifications) {
            const listElement = document.getElementById('notificationList');
            listElement.innerHTML = notifications.map(notif => createNotificationHTML(notif)).join('');
        }

        function createNotificationHTML(notif) {
            const data = notif.data || {};
            const statusBadge = data.status ? getStatusBadge(data.status) : '';

            return `
                <div class="notification-item border rounded-3 p-3 mb-3 bg-warning bg-opacity-10" 
                     data-notification-id="${notif.id}">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                        <div class="flex-grow-1">
                            <h6 class="fw-semibold mb-2">
                                <i class="fas fa-bell text-warning me-1"></i>
                                ${notif.title}
                                <span class="badge bg-danger ms-2">BARU</span>
                            </h6>
                            <p class="text-muted small mb-2">${notif.message}</p>
                            
                            ${data.no_kendaraan ? `
                                        <div class="small mb-1">
                                            <strong>No Kendaraan:</strong> ${data.no_kendaraan}
                                        </div>` : ''}
                            
                            ${data.jenis_motor ? `
                                        <div class="small mb-1">
                                            <strong>Jenis Motor:</strong> ${data.jenis_motor}
                                        </div>` : ''}
                            
                            ${data.user_name ? `
                                        <div class="small mb-2">
                                            <strong>Customer:</strong> ${data.user_name}
                                        </div>` : ''}
                            
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                ${statusBadge}
                                <span class="text-muted small">
                                    <i class="far fa-clock me-1"></i>${notif.time_ago || notif.formatted_date}
                                </span>
                            </div>
                        </div>
                        
                        <div class="d-flex flex-row flex-md-column gap-2 align-self-start">
                            ${notif.action_url ? `
                                        <a href="${notif.action_url}" 
                                           class="btn btn-sm btn-primary"
                                           onclick="markAsRead(${notif.id}, event)">
                                            <i class="fas fa-eye me-1"></i>Lihat
                                        </a>` : ''}
                            <button onclick="markAsRead(${notif.id}, event)" 
                                    class="btn btn-sm btn-outline-success">
                                <i class="fas fa-check me-1"></i>Tandai Dibaca
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-warning text-dark">Pending</span>',
                'in_service': '<span class="badge bg-info">Proses</span>',
                'priced': '<span class="badge bg-primary">Sudah Diprice</span>',
                'done': '<span class="badge bg-success">Selesai</span>',
                'rejected': '<span class="badge bg-danger">Ditolak</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
        }

        function showEmptyState() {
            document.getElementById('notificationList').innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="fas fa-bell-slash fa-3x mb-3 opacity-50"></i>
                    <p>Belum ada notifikasi baru</p>
                </div>
            `;
        }

        function showErrorState() {
            document.getElementById('notificationList').innerHTML = `
                <div class="text-center text-danger py-5">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <p>Gagal memuat notifikasi</p>
                </div>
            `;
        }

        function markAsRead(notifId, event) {
            if (event) event.preventDefault();

            fetch(`{{ url('admin/notifications') }}/${notifId}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const notifElement = document.querySelector(`[data-notification-id="${notifId}"]`);
                        if (notifElement) {
                            notifElement.style.transition = 'all 0.3s ease-out';
                            notifElement.style.transform = 'translateX(100%)';
                            notifElement.style.opacity = '0';

                            setTimeout(() => {
                                notifElement.remove();

                                const remainingNotifs = document.querySelectorAll('[data-notification-id]');
                                if (remainingNotifs.length === 0) {
                                    showEmptyState();
                                }
                            }, 300);
                        }

                        updateUnreadCount();

                        const notif = result.notification || {};
                        if (notif.action_url && event && event.target.closest('a')) {
                            setTimeout(() => window.location.href = notif.action_url, 300);
                        }
                    }
                })
                .catch(error => {
                    console.error('❌ Error:', error);
                    alert('Gagal menandai notifikasi');
                });
        }

        function markAllAsRead() {
            if (!confirm('Tandai semua notifikasi sebagai sudah dibaca?')) return;

            fetch('{{ route('admin.notifications.read-all') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showEmptyState();
                        updateUnreadCount();
                    }
                })
                .catch(error => {
                    console.error('❌ Error:', error);
                    alert('Gagal menandai semua notifikasi');
                });
        }

        function updateUnreadCount() {
            fetch('{{ route('admin.notifications.count') }}')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const badge = document.getElementById('unreadBadge');
                        if (result.count > 0) {
                            badge.textContent = result.count;
                            badge.style.display = 'inline-block';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('❌ Error updating count:', error));
        }

        function playNotificationSound() {
            const audio = document.getElementById('notificationSound');
            audio.play().catch(e => console.log('🔇 Audio play prevented'));
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Dashboard initialized');
            loadUnreadNotifications();
            loadUnreadNotificationsTransaksi();
            updateUnreadCount();
        });
    </script>
@endpush
