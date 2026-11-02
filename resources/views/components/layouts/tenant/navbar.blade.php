
<nav class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">

        {{-- LOGO --}}
        <a href="{{ route('guest.home') }}"
           class="flex shrink-0 items-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-900 font-bold text-white">
                n
            </span>
            <span class="text-xl font-semibold tracking-tight text-slate-900">
                nesa<span class="text-blue-900">Kost</span>
            </span>
        </a>

        {{-- NAVBAR DESKTOP --}}
        <div class="hidden items-center gap-7 lg:flex">

            @if(auth()->check() && auth()->user()->role === 'tenant')

                {{-- MENU TENANT --}}
                @php
                    $tenantMenus = [
                        [
                            'label' => 'Dashboard',
                            'route' => 'tenant.dashboard',
                            'url' => '/tenant/dashboard',
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

                @foreach($tenantMenus as $menu)
                    @php
                        $isActive = request()->routeIs($menu['route'])
                            || request()->is(ltrim($menu['url'], '/') . '*');
                    @endphp

                    <a href="{{ \Illuminate\Support\Facades\Route::has($menu['route'])
                        ? route($menu['route'])
                        : url($menu['url']) }}"
                       class="text-sm font-medium transition
                       {{ $isActive
                            ? 'text-blue-900'
                            : 'text-slate-600 hover:text-blue-900' }}">
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
                <a href="{{ route('login') }}"
                   class="text-sm font-medium text-slate-600 transition hover:text-blue-900">
                    Masuk
                </a>

                <a href="{{ route('register.role') }}"
                   class="rounded-lg bg-blue-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-800">
                    Daftar
                </a>
            @else

                @if(auth()->user()->role === 'tenant')
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
                        <button type="submit"
                                class="text-sm font-medium text-slate-600 hover:text-red-600">
                            Keluar
                        </button>
                    </form>
                @endif

            @endguest
        </div>

        {{-- TOMBOL MOBILE --}}
        <button type="button"
                id="customer-menu-button"
                class="rounded-lg p-2 text-slate-700 hover:bg-slate-100 lg:hidden"
                aria-label="Buka menu"
                aria-expanded="false"
                aria-controls="customer-mobile-menu">
            <i data-feather="menu" class="h-6 w-6"></i>
        </button>

    </div>

    {{-- NAVBAR MOBILE --}}
    <div id="customer-mobile-menu"
         class="hidden border-t border-slate-100 bg-white px-6 py-4 lg:hidden">

        <div class="flex flex-col gap-4">

            @if(auth()->check() && auth()->user()->role === 'tenant')

                @foreach($tenantMenus as $menu)
                    @php
                        $isActive = request()->routeIs($menu['route'])
                            || request()->is(ltrim($menu['url'], '/') . '*');
                    @endphp

                    <a href="{{ \Illuminate\Support\Facades\Route::has($menu['route'])
                        ? route($menu['route'])
                        : url($menu['url']) }}"
                       class="text-sm font-medium
                       {{ $isActive ? 'text-blue-900' : 'text-slate-600' }}">
                        {{ $menu['label'] }}
                    </a>
                @endforeach

                <div class="border-t border-slate-100 pt-4">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="text-sm font-medium text-red-600">
                            Keluar
                        </button>
                    </form>
                </div>

            @else

                <a href="{{ route('customer.home') }}#cari"
                   class="text-sm font-medium text-slate-600">
                    Cari Kost
                </a>

                <a href="{{ route('customer.home') }}#pemilik"
                   class="text-sm font-medium text-slate-600">
                    Untuk Pemilik Kost
                </a>

                @guest
                    <div class="flex flex-col gap-3 border-t border-slate-100 pt-4">
                        <a href="{{ route('login') }}"
                           class="text-sm font-medium text-slate-600">
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
                        <button type="submit"
                                class="text-sm font-medium text-red-600">
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
    document.addEventListener('DOMContentLoaded', function () {
        const menuButton = document.getElementById('customer-menu-button');
        const mobileMenu = document.getElementById('customer-mobile-menu');

        if (!menuButton || !mobileMenu) return;

        menuButton.addEventListener('click', function () {
            const isHidden = mobileMenu.classList.toggle('hidden');

            menuButton.setAttribute('aria-expanded', !isHidden);

            menuButton.innerHTML = isHidden
                ? '<i data-feather="menu" class="h-6 w-6"></i>'
                : '<i data-feather="x" class="h-6 w-6"></i>';

            if (window.feather) {
                window.feather.replace();
            }
        });
    });
</script>
