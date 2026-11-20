<nav class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-2 px-6 py-4">

        {{-- LOGO --}}
        <a href="{{ route('guest.home') }}" class="flex shrink-0 items-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-900 font-bold text-white">
                n
            </span>
            <span class="text-xl font-semibold tracking-tight text-slate-900">
                nesa<span class="text-blue-900">Kost</span>
            </span>
        </a>

        {{-- NAVBAR DESKTOP --}}
        <div class="hidden items-center gap-7 lg:flex">

            @if (auth()->check() && auth()->user()->role === 'tenant')

                {{-- MENU TENANT --}}
                @php
                    $tenantMenus = [
                        [
                            'label' => 'Dashboard',
                            'route' => 'tenant.dashboard',
                            'url' => '/tenant/dashboard',
                        ],
                        [
                            'label' => 'Cari Kamar',
                            'route' => 'guest.home',
                            'url' => '/guest/home',
                        ],
                        [
                            'label' => 'Kamar Saya',
                            'route' => 'tenant.kamar-saya',
                            'url' => '/tenant/kamar-saya',
                        ],
                        [
                            'label' => 'Informasi Sewa',
                            'route' => 'tenant.informasi-sewa',
                            'url' => '/tenant/informasi-sewa',
                        ],
                        [
                            'label' => 'Riwayat Pembayaran',
                            'route' => 'tenant.riwayat-pembayaran',
                            'url' => '/tenant/riwayat-pembayaran',
                        ],
                        [
                            'label' => 'Profil',
                            'route' => 'tenant.profil',
                            'url' => '/tenant/profil',
                        ],
                    ];
                @endphp

                @foreach ($tenantMenus as $menu)
                    @php
                        $isActive = request()->routeIs($menu['route']) || request()->is(ltrim($menu['url'], '/') . '*');
                    @endphp

                    <a href="{{ \Illuminate\Support\Facades\Route::has($menu['route']) ? route($menu['route']) : url($menu['url']) }}"
                        class="text-sm font-medium transition
                       {{ $isActive ? 'text-blue-900' : 'text-slate-600 hover:text-blue-900' }}">
                        {{ $menu['label'] }}
                    </a>
                @endforeach
            @else
                {{-- MENU GUEST --}}
                <a href="{{ route('customer.home') }}#cari"
                    class="text-sm font-medium text-slate-600 transition hover:text-blue-900">
                    Cari Kost
                </a>

                <a href="{{ route('customer.home') }}#pemilik"
                    class="text-sm font-medium text-slate-600 transition hover:text-blue-900">
                    Untuk Pemilik Kost
                </a>

            @endif
        </div>

        {{-- BAGIAN KANAN DESKTOP --}}
        <div class="hidden items-center gap-3 lg:flex">

            @guest
                <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 transition hover:text-blue-900">
                    Masuk
                </a>

                <a href="{{ route('register.role') }}"
                    class="rounded-lg bg-blue-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-800">
                    Daftar
                </a>
            @else
                @if (auth()->user()->role === 'tenant')
                    @php
                        $tenantUnreadCount = auth()->user()->unreadNotifications()->count();
                        $tenantLatestNotifications = auth()->user()->notifications()->latest()->take(5)->get();
                    @endphp
                    <div class="relative" id="tenant-notification-wrapper">
                        <button type="button" id="tenant-notification-button"
                            class="relative flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50"
                            aria-label="Buka notifikasi" aria-expanded="false" aria-controls="tenant-notification-dropdown">
                            <i data-feather="bell" class="h-5 w-5"></i>
                            @if ($tenantUnreadCount > 0)
                                <span
                                    class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">{{ $tenantUnreadCount > 99 ? '99+' : $tenantUnreadCount }}</span>
                            @endif
                        </button>
                        <div id="tenant-notification-dropdown"
                            class="absolute right-0 z-50 mt-3 hidden w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3"><span
                                    class="text-sm font-semibold text-slate-900">Notifikasi</span><a
                                    href="{{ route('tenant.notifications.index') }}"
                                    class="text-xs font-medium text-blue-900 hover:underline">Lihat semua</a></div>
                            <div class="max-h-96 overflow-y-auto">
                                @forelse($tenantLatestNotifications as $item)
                                    @php
                                        $itemData = $item->data ?? [];
                                    @endphp
                                    <a href="{{ route('tenant.notifications.open', $item->id) }}"
                                        class="block border-b border-slate-100 px-4 py-3 last:border-0 hover:bg-slate-50 {{ $item->read_at ? '' : 'bg-blue-50/60' }}">
                                        <div class="flex items-start gap-2">
                                            @unless ($item->read_at)
                                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-700"></span>
                                            @endunless
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold text-slate-800">
                                                    {{ $itemData['title'] ?? 'Notifikasi' }}</p>
                                                <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">
                                                    {{ $itemData['message'] ?? 'Anda memiliki notifikasi baru.' }}</p>
                                                <p class="mt-1 text-[10px] text-slate-400">
                                                    {{ $item->created_at?->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <p class="px-4 py-8 text-center text-xs text-slate-500">Belum ada notifikasi.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600">
                            <i data-feather="log-out" class="h-4 w-4"></i>
                            Keluar
                        </button>
                    </form>
                @else
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-slate-600 hover:text-red-600">
                            Keluar
                        </button>
                    </form>
                @endif

            @endguest
        </div>

        {{-- NOTIFIKASI MOBILE TENANT --}}
        @if (auth()->check() && auth()->user()->role === 'tenant')
            @php
                $mobileTenantUnreadCount = auth()->user()->unreadNotifications()->count();
                $mobileTenantLatestNotifications = auth()->user()->notifications()->latest()->take(5)->get();
            @endphp

            <div class="relative ml-auto mr-2 shrink-0 lg:hidden" id="tenant-mobile-notification-wrapper">
                <button type="button" id="tenant-mobile-notification-button"
                    class="relative flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50"
                    aria-label="Buka notifikasi" aria-expanded="false"
                    aria-controls="tenant-mobile-notification-dropdown">
                    <i data-feather="bell" class="h-5 w-5"></i>

                    @if ($mobileTenantUnreadCount > 0)
                        <span
                            class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">
                            {{ $mobileTenantUnreadCount > 99 ? '99+' : $mobileTenantUnreadCount }}
                        </span>
                    @endif
                </button>

                <div id="tenant-mobile-notification-dropdown"
                    class="fixed right-3 z-[60] mt-2 hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
                    style="width: min(320px, calc(100vw - 24px)); max-width: calc(100vw - 24px); box-sizing: border-box;">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                        <span class="text-sm font-semibold text-slate-900">Notifikasi</span>
                        <a href="{{ route('tenant.notifications.index') }}"
                            class="shrink-0 text-xs font-medium text-blue-900 hover:underline">
                            Lihat Semua
                        </a>
                    </div>

                    <div class="max-h-96 overflow-y-auto">
                        @forelse ($mobileTenantLatestNotifications as $item)
                            @php
                                $mobileItemData = $item->data ?? [];
                            @endphp

                            <a href="{{ route('tenant.notifications.open', $item->id) }}"
                                class="flex min-w-0 items-start gap-2 border-b border-slate-100 px-4 py-3 last:border-0 hover:bg-slate-50 {{ $item->read_at ? '' : 'bg-blue-50/60' }}">
                                @if (is_null($item->read_at))
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-700"></span>
                                @endif

                                <span class="min-w-0 flex-1 break-words">
                                    <span class="block break-words text-xs font-semibold text-slate-800">
                                        {{ $mobileItemData['title'] ?? 'Notifikasi' }}
                                    </span>
                                    <span class="mt-1 block break-words text-xs leading-5 text-slate-500">
                                        {{ $mobileItemData['message'] ?? 'Anda memiliki notifikasi baru.' }}
                                    </span>
                                    <span class="mt-1 block text-[10px] text-slate-400">
                                        {{ $item->created_at ? $item->created_at->diffForHumans() : '' }}
                                    </span>
                                </span>
                            </a>
                        @empty
                            <p class="px-4 py-8 text-center text-xs text-slate-500">
                                Belum ada notifikasi.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        <button type="button" id="customer-menu-button"
            class="rounded-lg p-2 text-slate-700 hover:bg-slate-100 lg:hidden" aria-label="Buka menu"
            aria-expanded="false" aria-controls="customer-mobile-menu">
            <i data-feather="menu" class="h-6 w-6"></i>
        </button>

    </div>

    <div id="customer-mobile-menu" class="hidden border-t border-slate-100 bg-white px-6 py-4 lg:hidden">

        <div class="flex flex-col gap-4">

            @if (auth()->check() && auth()->user()->role === 'tenant')

                @foreach ($tenantMenus as $menu)
                    @php
                        $isActive =
                            request()->routeIs($menu['route']) || request()->is(ltrim($menu['url'], '/') . '/*');
                    @endphp

                    <a href="{{ \Illuminate\Support\Facades\Route::has($menu['route']) ? route($menu['route']) : url($menu['url']) }}"
                        class="text-sm font-medium
                       {{ $isActive ? 'text-blue-900' : 'text-slate-600' }}">
                        {{ $menu['label'] }}
                    </a>
                @endforeach

                <div class="border-t border-slate-100 pt-4">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-red-600">
                            Keluar
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('customer.home') }}#cari" class="text-sm font-medium text-slate-600">
                    Cari Kost
                </a>

                <a href="{{ route('customer.home') }}#pemilik" class="text-sm font-medium text-slate-600">
                    Untuk Pemilik Kost
                </a>

                @guest
                    <div class="flex flex-col gap-3 border-t border-slate-100 pt-4">
                        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600">
                            Masuk
                        </a>

                        <a href="{{ route('register.role') }}"
                            class="rounded-lg bg-blue-900 px-4 py-2.5 text-center text-sm font-medium text-white">
                            Daftar
                        </a>
                    </div>
                @else
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-red-600">
                            Keluar
                        </button>
                    </form>
                @endguest

            @endif

        </div>
    </div>
</nav>

{{-- JAVASCRIPT MOBILE MENU --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuButton = document.getElementById('customer-menu-button');
        const mobileMenu = document.getElementById('customer-mobile-menu');
        const notificationButton = document.getElementById('tenant-notification-button');
        const notificationDropdown = document.getElementById('tenant-notification-dropdown');

        const mobileNotificationButton = document.getElementById('tenant-mobile-notification-button');
        const mobileNotificationDropdown = document.getElementById('tenant-mobile-notification-dropdown');

        if (mobileNotificationButton && mobileNotificationDropdown) {
            mobileNotificationButton.addEventListener('click', function(event) {
                event.stopPropagation();

                const willOpen = mobileNotificationDropdown.classList.contains('hidden');
                mobileNotificationDropdown.classList.toggle('hidden');
                mobileNotificationButton.setAttribute('aria-expanded', String(willOpen));
            });

            document.addEventListener('click', function(event) {
                if (!event.target.closest('#tenant-mobile-notification-wrapper')) {
                    mobileNotificationDropdown.classList.add('hidden');
                    mobileNotificationButton.setAttribute('aria-expanded', 'false');
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    mobileNotificationDropdown.classList.add('hidden');
                    mobileNotificationButton.setAttribute('aria-expanded', 'false');
                }
            });
        }

        if (notificationButton && notificationDropdown) {
            notificationButton.addEventListener('click', function(event) {
                event.stopPropagation();
                const isHidden = notificationDropdown.classList.toggle('hidden');
                notificationButton.setAttribute('aria-expanded', String(!isHidden));
            });

            document.addEventListener('click', function(event) {
                if (!event.target.closest('#tenant-notification-wrapper')) {
                    notificationDropdown.classList.add('hidden');
                    notificationButton.setAttribute('aria-expanded', 'false');
                }
            });
        }

        if (!menuButton || !mobileMenu) return;

        menuButton.addEventListener('click', function() {
            const isHidden = mobileMenu.classList.toggle('hidden');

            menuButton.setAttribute('aria-expanded', !isHidden);

            menuButton.innerHTML = isHidden ?
                '<i data-feather="menu" class="h-6 w-6"></i>' :
                '<i data-feather="x" class="h-6 w-6"></i>';

            if (window.feather) {
                window.feather.replace();
            }
        });
    });
</script>
