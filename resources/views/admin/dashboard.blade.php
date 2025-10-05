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

        {{-- NOTIFIKASI SERVIS TERBARU --}}
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-bell text-yellow-500"></i> Notifikasi Terbaru
                    <span id="unreadBadge" class="badge bg-danger ms-2" style="display: none;">0</span>
                </h3>
                <button onclick="markAllAsRead()" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-check-double"></i> Tandai Semua Dibaca
                </button>
            </div>
            <div id="notificationList" class="space-y-3">
                <div class="text-center text-gray-500 py-4">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p>Memuat notifikasi...</p>
                </div>
            </div>
        </div>

        {{-- Cart Total Order dan Traffic Sources --}}
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
    </div>

    {{-- Audio untuk notifikasi --}}
    <audio id="notificationSound" preload="auto">
        <source src="{{ asset('sounds/notif.mp3') }}" type="audio/mpeg">
    </audio>

    {{-- Firebase SDK --}}
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js"></script>
@endsection

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

        // Subscribe to admin notifications topic
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
                .then(data => {
                    console.log('✅ Subscribed to admin_notifications topic:', data);
                })
                .catch(error => {
                    console.error('❌ Failed to subscribe:', error);
                });
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
                .then(data => {
                    console.log('✅ Subscribed to admin_notifications topic:', data);
                })
                .catch(error => {
                    console.error('❌ Failed to subscribe:', error);
                });
        }


        // Listen for foreground messages from Firebase
        messaging.onMessage((payload) => {
            console.log('📨 Firebase message received:', payload);

            const notification = payload.notification || {};
            const data = payload.data || {};

            // Play notification sound
            playNotificationSound();

            // Reload notifications from database (sudah di-insert dari backend)
            setTimeout(() => {
                loadUnreadNotifications();
                updateUnreadCount();
            }, 500);

            // Show browser notification
            if (Notification.permission === 'granted') {
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

        // Load unread notifications from database
        function loadUnreadNotifications() {
            fetch('{{ route('admin.notifications.unread') }}')
                .then(response => response.json())
                .then(result => {
                    console.log('📊 Notifications loaded:', result);

                    if (result.success && result.data && result.data.length > 0) {
                        displayNotifications(result.data);
                    } else {
                        document.getElementById('notificationList').innerHTML = `
                            <div class="text-center text-gray-500 py-4">
                                <i class="fas fa-bell-slash text-3xl mb-2"></i>
                                <p>Belum ada notifikasi baru</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('❌ Error loading notifications:', error);
                    document.getElementById('notificationList').innerHTML = `
                        <div class="text-center text-danger py-4">
                            <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                            <p>Gagal memuat notifikasi</p>
                        </div>
                    `;
                });
        }

        function loadUnreadNotificationsTransaksi() {
            fetch('{{ route('admin.notifications.unread-transaksi') }}')
                .then(response => response.json())
                .then(result => {
                    console.log('📊 Notifications loaded:', result);

                    if (result.success && result.data && result.data.length > 0) {
                        displayNotifications(result.data);
                    } else {
                        document.getElementById('notificationList').innerHTML = `
                            <div class="text-center text-gray-500 py-4">
                                <i class="fas fa-bell-slash text-3xl mb-2"></i>
                                <p>Belum ada notifikasi baru</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('❌ Error loading notifications:', error);
                    document.getElementById('notificationList').innerHTML = `
                        <div class="text-center text-danger py-4">
                            <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                            <p>Gagal memuat notifikasi</p>
                        </div>
                    `;
                });
        }

        // Display notifications list
        function displayNotifications(notifications) {
            const listElement = document.getElementById('notificationList');
            listElement.innerHTML = notifications.map(notif => createNotificationHTML(notif)).join('');
        }

        // Create notification HTML
        function createNotificationHTML(notif) {
            const data = notif.data || {};
            const statusBadge = data.status ? getStatusBadge(data.status) : '';

            return `
                <div class="border border-warning rounded-lg p-4 hover:bg-gray-50 transition bg-warning-subtle" 
                     data-notification-id="${notif.id}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h5 class="font-semibold text-gray-800 mb-2">
                                <i class="fas fa-bell text-warning"></i>
                                ${notif.title}
                                <span class="badge bg-danger ms-2">BARU</span>
                            </h5>
                            <p class="text-sm text-gray-700 mb-2">${notif.message}</p>
                            
                            ${data.no_kendaraan ? `
                                <p class="text-sm text-gray-600 mb-1">
                                    <strong>No Kendaraan:</strong> ${data.no_kendaraan}
                                </p>` : ''}
                            
                            ${data.jenis_motor ? `
                                <p class="text-sm text-gray-600 mb-1">
                                    <strong>Jenis Motor:</strong> ${data.jenis_motor}
                                </p>` : ''}
                            
                            ${data.keluhan ? `
                                <p class="text-sm text-gray-600 mb-1">
                                    <strong>Keluhan:</strong> ${data.keluhan}
                                </p>` : ''}
                            
                            ${data.user_name ? `
                                <p class="text-sm text-gray-600 mb-2">
                                    <strong>Customer:</strong> ${data.user_name}
                                </p>` : ''}
                            
                            <div class="d-flex align-items-center gap-2">
                                ${statusBadge}
                                <span class="text-xs text-gray-500">
                                    <i class="far fa-clock"></i> ${notif.ti4me_ago || notif.formatted_date}
                                </span>
                            </div>
                        </div>
                        <div class="ms-3 d-flex flex-column gap-2">
                        ${notif.action_url ? `
                                <a href="${notif.action_url}" 
                                    class="btn btn-sm btn-primary"
                                    onclick="markAsRead(${notif.id}, event)">
                                    <i class="fas fa-eye"></i> Lihat
                                </a>` : ''}
                            <button onclick="markAsRead(${notif.id}, event)" 
                                    class="btn btn-sm btn-outline-success">
                                <i class="fas fa-check"></i> Tandai Dibaca
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        // Get status badge
        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-warning text-dark">Pending</span>',
                'in_service': '<span class="badge bg-info">Sedang Dikerjakan</span>',
                'priced': '<span class="badge bg-primary">Sudah Diprice</span>',
                'done': '<span class="badge bg-success">Selesai</span>',
                'rejected': '<span class="badge bg-danger">Ditolak</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
        }

        // Mark single notification as read and delete
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
                        console.log('✅ Notification marked as read');

                        // Hapus dari DOM dengan animasi
                        const notifElement = document.querySelector(`[data-notification-id="${notifId}"]`);
                        if (notifElement) {
                            notifElement.style.transition = 'all 0.3s ease-out';
                            notifElement.style.transform = 'translateX(100%)';
                            notifElement.style.opacity = '0';

                            setTimeout(() => {
                                notifElement.remove();

                                // Cek apakah masih ada notifikasi
                                const remainingNotifs = document.querySelectorAll('[data-notification-id]');
                                if (remainingNotifs.length === 0) {
                                    document.getElementById('notificationList').innerHTML = `
                                    <div class="text-center text-gray-500 py-4">
                                        <i class="fas fa-check-circle text-3xl text-success mb-2"></i>
                                        <p>Semua notifikasi sudah dibaca</p>
                                    </div>
                                `;
                                }
                            }, 300);
                        }

                        // Update counter
                        updateUnreadCount();

                        // Jika ada action_url dan bukan dari button tandai dibaca, redirect
                        const notif = result.notification || {};
                        if (notif.action_url && event && event.target.closest('a')) {
                            setTimeout(() => {
                                window.location.href = notif.action_url;
                            }, 300);
                        }
                    }
                })
                .catch(error => {
                    console.error('❌ Error marking as read:', error);
                    alert('Gagal menandai notifikasi sebagai dibaca');
                });
        }

        function markAsReadTransaksi(notifId, event) {
            if (event) event.preventDefault();

            fetch(`{{ url('admin/notifications') }}/${notifId}/read-transaksi`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        console.log('✅ Notification marked as read');
                        const notifElement = document.querySelector(`[data-notification-id="${notifId}"]`);
                        if (notifElement) {
                            notifElement.style.transition = 'all 0.3s ease-out';
                            notifElement.style.transform = 'translateX(100%)';
                            notifElement.style.opacity = '0';

                            setTimeout(() => {
                                notifElement.remove();
                                const remainingNotifs = document.querySelectorAll('[data-notification-id]');
                                if (remainingNotifs.length === 0) {
                                    document.getElementById('notificationList').innerHTML = `
                                    <div class="text-center text-gray-500 py-4">
                                        <i class="fas fa-check-circle text-3xl text-success mb-2"></i>
                                        <p>Semua notifikasi sudah dibaca</p>
                                    </div>
                                `;
                                }
                            }, 300);
                        }

                        updateUnreadCount();
                        const notif = result.notification || {};
                        if (notif.action_url && event && event.target.closest('a')) {
                            setTimeout(() => {
                                window.location.href = notif.action_url;
                            }, 300);
                        }
                    }
                })
                .catch(error => {
                    console.error('❌ Error marking as read:', error);
                    alert('Gagal menandai notifikasi sebagai dibaca');
                });
        }

        function markAllAsRead() {
            if (!confirm('Tandai semua notifikasi sebagai sudah dibaca?')) {
                return;
            }

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
                        console.log('✅ All notifications marked as read');

                        // Clear list
                        document.getElementById('notificationList').innerHTML = `
                        <div class="text-center text-gray-500 py-4">
                            <i class="fas fa-check-circle text-3xl text-success mb-2"></i>
                            <p>Semua notifikasi telah dibaca</p>
                        </div>
                    `;

                        // Update counter
                        updateUnreadCount();
                    }
                })
                .catch(error => {
                    console.error('❌ Error marking all as read:', error);
                    alert('Gagal menandai semua notifikasi');
                });
        }

        function markAllAsReadTransaksi() {
            if (!confirm('Tandai semua notifikasi sebagai sudah dibaca?')) {
                return;
            }

            fetch('{{ route('admin.notifications.read-all-transaksi') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        console.log('✅ All notifications marked as read');

                        // Clear list
                        document.getElementById('notificationList').innerHTML = `
                        <div class="text-center text-gray-500 py-4">
                            <i class="fas fa-check-circle text-3xl text-success mb-2"></i>
                            <p>Semua notifikasi telah dibaca</p>
                        </div>
                    `;

                        // Update counter
                        updateUnreadCount();
                    }
                })
                .catch(error => {
                    console.error('❌ Error marking all as read:', error);
                    alert('Gagal menandai semua notifikasi');
                });
        }

        // Update unread count badge
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
                .catch(error => {
                    console.error('❌ Error updating count:', error);
                });
        }

        function updateUnreadCountTransaksi() {
            fetch('{{ route('admin.notifications.count-transaksi') }}')
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
                .catch(error => {
                    console.error('❌ Error updating count:', error);
                });
        }

        // Play notification sound
        function playNotificationSound() {
            const audio = document.getElementById('notificationSound');
            audio.play().catch(e => {
                console.log('🔇 Audio play prevented (user interaction required)');
            });
        }

        // ===================================
        // INITIALIZE ON PAGE LOAD
        // ===================================
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Dashboard initialized');

            // Load initial data
            loadUnreadNotifications();
            loadUnreadNotificationsTransaksi();
            updateUnreadCount();

            // // Auto refresh every 10 seconds
            // setInterval(loadUnreadNotifications, 10000);
            // setInterval(loadUnreadNotificationsTransaksi, 10000);
            // setInterval(updateUnreadCount, 10000);
            // setInterval(updateUnreadCountTransaksi, 10000);
        });

        // ===================================
        // CHARTS
        // ===================================
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
        });
    </script>
@endpush
