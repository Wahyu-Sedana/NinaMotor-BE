<!-- Sidebar -->
<div id="sidebar"
    class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
    <div class="flex items-center justify-between h-16 px-6 bg-primary-600">
        <h1 class="text-xl font-bold">Nina Motor</h1>
        <button id="closeSidebar" class="lg:hidden hover:text-gray-200">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <nav class="mt-6">
        <div class="px-6 py-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Main</p>
        </div>
        <a href="{{ route('admin.dashboard') }}"
            class="flex items-center px-6 py-3 text-gray-700 bg-primary-50 border-r-4 border-primary-600">
            <i class="fas fa-tachometer-alt mr-3 text-primary-600"></i>
            <span class="font-medium">Dashboard</span>
        </a>
        <a href="{{ route('admin.sparepart.index') }}"
            class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
            <i class="fas fa-box mr-3"></i>
            <span>Sparepart</span>
        </a>
        <a href="#"
            class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
            <i class="fas fa-users mr-3"></i>
            <span>Users</span>
        </a>
        <a href="#"
            class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
            <i class="fas fa-shopping-cart mr-3"></i>
            <span>Orders</span>
        </a>
        <a href="#"
            class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
            <i class="fas fa-chart-bar mr-3"></i>
            <span>Analytics</span>
        </a>
    </nav>
</div>

<!-- Overlay untuk mobile -->
<div id="sidebarOverlay" class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden hidden"></div>
