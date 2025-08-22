<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>{{ $title ?? 'Admin Dashboard' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            background-color: #343a40;
            min-height: 100vh;
            width: 250px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        header {
            position: relative;
            z-index: 50;
        }

        #notifMenu {
            position: fixed;
            top: 60px;
            right: 20px;
            z-index: 9999;
        }


        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                overflow-y: auto;
            }

            .sidebar.show {
                transform: translateX(0);
            }
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        @include('admin.layouts.sidebar')

        <div class="flex-1 flex flex-col overflow-hidden lg:ml-0">
            @include('admin.layouts.top-nav')

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

    <script>
        $(document).ready(function() {
            let shownNotifIdsServis = new Set();
            let shownNotifIdsTransaksi = new Set();

            function updateTopNavCounter(totalCount) {
                const notifCounter = $('#notifCounter');
                if (totalCount > 0) {
                    notifCounter.text(totalCount).removeClass('hidden');
                } else {
                    notifCounter.addClass('hidden');
                }
            }

            function fetchServisNotifications() {
                return $.ajax({
                    url: '/admin/notifications/servis',
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(notifications) {
                        const sidebarCounter = $('#servisNotificationCounter');
                        if (notifications.length > 0) {
                            sidebarCounter.text(notifications.length).removeClass('hidden');
                        } else {
                            sidebarCounter.addClass('hidden');
                        }

                        notifications.forEach(function(notification) {
                            if (!shownNotifIdsServis.has(notification.id)) {
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'info',
                                    title: notification.data.message,
                                    showConfirmButton: false,
                                    timer: 5000,
                                    timerProgressBar: true
                                });
                                shownNotifIdsServis.add(notification.id);
                            }
                        });
                    }
                });
            }

            function fetchTransaksiNotifications() {
                return $.ajax({
                    url: '/admin/notifications/transaksi',
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(notifications) {
                        const sidebarCounter = $('#transaksiNotificationCounter');
                        if (notifications.length > 0) {
                            sidebarCounter.text(notifications.length).removeClass('hidden');
                        } else {
                            sidebarCounter.addClass('hidden');
                        }

                        notifications.forEach(function(notification) {
                            if (!shownNotifIdsTransaksi.has(notification.id)) {
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'success',
                                    title: notification.data.message,
                                    showConfirmButton: false,
                                    timer: 5000,
                                    timerProgressBar: true
                                });
                                shownNotifIdsTransaksi.add(notification.id);
                            }
                        });
                    }
                });
            }

            function fetchAllNotifications() {
                $.when(fetchServisNotifications(), fetchTransaksiNotifications()).done(function(servisData,
                    transaksiData) {
                    const servisCount = servisData[0]?.length || 0;
                    const transaksiCount = transaksiData[0]?.length || 0;
                    updateTopNavCounter(servisCount + transaksiCount);
                });
            }

            window.markServisNotificationsRead = function() {
                $('#servisNotificationCounter').addClass('hidden');
                $.post('/admin/notifications/servis/mark-as-read', {
                    _token: $('meta[name="csrf-token"]').attr('content')
                });
                shownNotifIdsServis.clear();
                fetchAllNotifications();
            };

            window.markTransaksiNotificationsRead = function() {
                $('#transaksiNotificationCounter').addClass('hidden');
                $.post('/admin/notifications/transaksi/mark-as-read', {
                    _token: $('meta[name="csrf-token"]').attr('content')
                });
                shownNotifIdsTransaksi.clear();
                fetchAllNotifications();
            };

            // window.markTopNavNotificationsRead = function() {
            //     $('#notifCounter').addClass('hidden');
            //     $.post('/admin/notifications/servis/mark-as-read', {
            //         _token: $('meta[name="csrf-token"]').attr('content')
            //     });
            //     $.post('/admin/notifications/transaksi/mark-as-read', {
            //         _token: $('meta[name="csrf-token"]').attr('content')
            //     });
            //     shownNotifIdsServis.clear();
            //     shownNotifIdsTransaksi.clear();
            //     $('#servisNotificationCounter').addClass('hidden');
            //     $('#transaksiNotificationCounter').addClass('hidden');
            // };

            fetchAllNotifications();
            setInterval(fetchAllNotifications, 3000);
        });
    </script>

    @stack('scripts')
</body>

</html>
