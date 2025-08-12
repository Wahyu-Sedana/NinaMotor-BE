<!-- Sidebar -->
<div id="sidebar"
    class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 **overflow-y-auto**">
    <div class="bg-primary-600 px-6 py-2 flex justify-center">
        <a href="{{ route('admin.dashboard') }}">
            <img src="{{ asset('images/logo.png') }}" alt="Nina Motor" class="max-h-28 object-contain">
        </a>
    </div>


    <nav>
        <div class="px-6 py-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Main</p>
        </div>
        <a href="{{ route('admin.dashboard') }}"
            class="flex items-center px-6 py-3 
          {{ request()->routeIs('admin.dashboard') ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <i class="fas fa-tachometer-alt mr-3 text-primary-600"></i>
            <span class="font-medium">Dashboard</span>
        </a>

        <div class="px-6 py-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Produk</p>
        </div>

        <a href="{{ route('admin.sparepart.index') }}"
            class="flex items-center px-6 py-3 transition-colors
        {{ request()->routeIs('admin.sparepart.index')
            ? 'bg-gray-100 text-gray-900 font-medium'
            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <i
                class="fas fa-box mr-3 {{ request()->routeIs('admin.sparepart.*') ? 'text-primary-700' : 'text-gray-600' }}"></i>
            <span>Sparepart</span>
        </a>

        <a href="{{ route('admin.kategori-sparepart.index') }}"
            class="flex items-center px-6 py-3 transition-colors
        {{ request()->routeIs('admin.kategori-sparepart.index')
            ? 'bg-gray-100 text-gray-900 font-medium'
            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <i
                class="fas fa-layer-group mr-3 {{ request()->routeIs('admin.kategori-sparepart.*') ? 'text-primary-700' : 'text-gray-600' }}"></i>
            <span>Kategori Sparepart</span>
        </a>

        <div class="px-6 py-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Users</p>
        </div>

        <a href="{{ route('admin.customer.index') }}"
            class="flex items-center px-6 py-3 transition-colors
        {{ request()->routeIs('admin.customer.index')
            ? 'bg-gray-100 text-gray-900 font-medium'
            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <i
                class="fas fa-users mr-3 {{ request()->routeIs('admin.customer.*') ? 'text-primary-700' : 'text-gray-600' }}"></i>
            <span>Customer</span>
        </a>

        <div class="px-6 py-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Transaksi & Servis Motor</p>
        </div>

        <a href="{{ route('admin.transaksi.index') }}"
            class="flex items-center px-6 py-3 transition-colors
        {{ request()->routeIs('admin.transaksi.index')
            ? 'bg-gray-100 text-gray-900 font-medium'
            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <i
                class="fas fa-shopping-cart mr-3 {{ request()->routeIs('admin.transaksi.*') ? 'text-primary-700' : 'text-gray-600' }}"></i>
            <span>Transaksi</span>
        </a>

        <a href="{{ route('admin.servis.index') }}"
            class="flex items-center px-6 py-3 transition-colors
        {{ request()->routeIs('admin.servis.index')
            ? 'bg-gray-100 text-gray-900 font-medium'
            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <i
                class="fas fa-motorcycle mr-3 {{ request()->routeIs('admin.servis.*') ? 'text-primary-700' : 'text-gray-600' }}"></i>
            <span>Servis Motor</span>
        </a>
    </nav>
</div>

<!-- Overlay untuk mobile -->
<div id="sidebarOverlay" class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden hidden"></div>
