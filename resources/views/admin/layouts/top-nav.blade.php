<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="flex items-center justify-between px-6 py-4">
        <div class="flex items-center">
            <button id="openSidebar" class="lg:hidden mr-4 text-gray-600 hover:text-gray-900">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>

        <div class="flex items-center space-x-4">
            <button class="relative p-2 text-gray-400 hover:text-gray-600">
                <i class="fas fa-bell text-xl"></i>
                <span
                    class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">3</span>
            </button>
            <div class="relative">
                <button id="profileDropdown" class="flex items-center space-x-3 text-gray-600 hover:text-gray-900">
                    <span class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-200">
                        <i class="fas fa-user text-gray-600 text-sm"></i>
                    </span>

                    <span class="hidden md:block">Admin User</span>
                    <i class="fas fa-chevron-down text-sm"></i>
                </button>
                <div id="profileMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden">
                    <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <hr class="my-2" />
                    <button type="button" onclick="sweetAlertLogout()"
                        class="block px-4 py-2 text-gray-700 hover:bg-gray-100 transition-colors w-full text-left">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </button>
                </div>
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
