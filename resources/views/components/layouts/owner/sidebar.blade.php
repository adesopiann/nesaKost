@php
    $menu = [
        ['label' => 'Dashboard', 'url' => '/owner/dashboard', 'match' => 'owner/dashboard', 'icon' => 'grid'],
        ['label' => 'Data Cabang Kos', 'url' => '/owner/branches', 'match' => 'owner/branches*', 'icon' => 'home'],
        ['label' => 'Data Kamar', 'url' => '/owner/rooms', 'match' => 'owner/rooms*', 'icon' => 'columns'],
        ['label' => 'Data Penyewa', 'url' => '/owner/penyewa', 'match' => 'owner/penyewa*', 'icon' => 'users'],
        ['label' => 'Data Sewa', 'url' => '/owner/sewa', 'match' => 'owner/sewa*', 'icon' => 'clipboard'],
        [
            'label' => 'Data Pembayaran',
            'url' => '/owner/pembayaran',
            'match' => 'owner/pembayaran*',
            'icon' => 'credit-card',
        ],
        ['label' => 'Data Fasilitas', 'url' => '/owner/fasilitas', 'match' => 'owner/fasilitas*', 'icon' => 'wifi'],
        [
            'label' => 'Data Asisten Rumah Tangga',
            'url' => '/owner/asisten-rumah-tangga',
            'match' => 'owner/asisten-rumah-tangga*',
            'icon' => 'briefcase',
        ],
    ];
@endphp

{{-- Overlay khusus tampilan mobile --}}
<div id="sidebar-overlay" class="fixed inset-0 z-40 hidden bg-slate-950/40 lg:hidden"></div>

<aside id="owner-sidebar"
    class="fixed inset-y-0 left-0 z-50 flex w-[280px] -translate-x-full flex-col border-r border-slate-100 bg-white transition-transform duration-300 lg:translate-x-0">
    <div class="flex h-[72px] shrink-0 items-center gap-3 border-b border-slate-100 px-6">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#1D4E80] text-white">
            <i data-feather="grid" class="h-5 w-5"></i>
        </div>
        <div class="min-w-0">
            <p class="text-[15px] font-bold leading-5 tracking-tight text-slate-800">nesaKost - Management</p>
            <p class="text-[11px] font-medium leading-4 text-slate-500">v1.0</p>
        </div>
        <button type="button" data-sidebar-close aria-label="Tutup sidebar"
            class="ml-auto rounded-md p-1 text-slate-500 hover:bg-slate-100 lg:hidden">
            <i data-feather="x" class="h-5 w-5"></i>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 py-5">
        <ul class="space-y-1.5">
            @foreach ($menu as $item)
                @php
                    $active = request()->is($item['match']);
                @endphp
                <li>
                    <a href="{{ url($item['url']) }}" @if ($active) aria-current="page" @endif
                        class="group flex min-h-[42px] items-center gap-3 rounded-lg px-3 py-2 text-[14px] font-medium transition-colors
                       {{ $active ? 'bg-[#315F96] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <i data-feather="{{ $item['icon'] }}"
                            class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-white' : 'text-slate-500 group-hover:text-slate-700' }}"></i>
                        <span class="leading-5">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="border-t border-slate-100 px-4 py-4">
        <a href="{{ url('/') }}"
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-800">
            <i data-feather="external-link" class="h-[18px] w-[18px]"></i>
            <span>Lihat Website</span>
        </a>
    </div>
</aside>
