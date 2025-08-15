<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="flex items-center justify-between px-6 py-4">
        <div class="flex items-center">
            <button id="openSidebar" class="lg:hidden mr-4 text-gray-600 hover:text-gray-900">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>

        <div class="flex items-center space-x-4">
            <button id="notifButton" class="relative p-2 text-gray-400 hover:text-gray-600">
                <i class="fas fa-bell text-xl"></i>
                <span id="notifCounter"
                    class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">
                    0
                </span>
            </button>

            <!-- Dropdown list notifikasi -->
            <div id="notifMenu" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg py-2 hidden z-50">
                <div id="notifList" class="max-h-60 overflow-y-auto">
                    <p class="text-gray-500 text-sm text-center py-4">No notifications</p>
                </div>
                <hr>
                <button onclick="markTopNavNotificationsRead()"
                    class="block w-full text-center py-2 text-sm text-blue-600 hover:bg-gray-50">
                    Mark all as read
                </button>
            </div>

            <div class="relative">
                <button id="profileDropdown" class="flex items-center space-x-3 text-gray-600 hover:text-gray-900">
                    <span class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-200">
                        <i class="fas fa-user text-gray-600 text-sm"></i>
                    </span>

                    <span class="hidden md:block">
                        {{ Auth::user()->nama ?? 'Guest' }}
                    </span>
                </button>
            </div>

        </div>
    </div>
</header>

<script>
    function sweetAlertLogout() {
        Swal.fire({
            title: 'Are you sure?',
            text: "You will be logged out of the admin panel",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Logging out...',
                    text: 'Please wait',
                    icon: 'info',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = "{{ route('admin.logout') }}";

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                form.appendChild(csrfInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
