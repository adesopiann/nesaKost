<header
    class="fixed right-0 top-0 z-30 flex h-[72px] items-center justify-between border-b border-slate-100 bg-white px-4 shadow-[0_2px_8px_rgba(15,23,42,0.04)] sm:px-6 lg:left-[260px] lg:px-8">
    <div class="flex min-w-0 flex-1 items-center gap-3">
        <button id="sidebar-open" type="button" aria-label="Buka menu"
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 lg:hidden">
            <i data-feather="menu" class="h-5 w-5"></i>
        </button>
        <span class="truncate text-sm font-semibold text-slate-700 md:hidden">nesaKost Management</span>
    </div>

    @php
        $ownerNotifications = auth()->user()->notifications()->latest()->limit(5)->get();
        $ownerUnreadCount = auth()->user()->unreadNotifications()->count();
    @endphp
    <div class="ml-3 flex shrink-0 items-center gap-3 sm:gap-5">
        <div class="relative" id="owner-notification-root">
            <button type="button" id="owner-notification-toggle" aria-label="Notifikasi" aria-expanded="false"
                class="relative flex h-10 w-9 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100">
                <i data-feather="bell" class="h-5 w-5"></i>
                @if($ownerUnreadCount > 0)
                    <span class="absolute -right-1 -top-1 min-w-4 rounded-full bg-red-600 px-1 text-center text-[10px] font-bold leading-4 text-white ring-2 ring-white">{{ $ownerUnreadCount > 99 ? '99+' : $ownerUnreadCount }}</span>
                @endif
            </button>
            <div id="owner-notification-menu" class="absolute right-0 top-12 z-[80] hidden w-[min(380px,calc(100vw-2rem))] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <div><p class="text-sm font-semibold text-slate-800">Notifikasi</p><p class="text-xs text-slate-500">{{ $ownerUnreadCount }} belum dibaca</p></div>
                    @if($ownerUnreadCount > 0)
                        <form method="POST" action="{{ route('owner.notifications.read-all') }}">@csrf @method('PATCH')<button class="text-xs font-semibold text-blue-700 hover:text-blue-900">Tandai semua dibaca</button></form>
                    @endif
                </div>
                <div class="max-h-[360px] overflow-y-auto divide-y divide-slate-100">
                    @forelse($ownerNotifications as $notification)
                        <a href="{{ route('owner.notifications.open', $notification->id) }}" class="flex gap-3 px-4 py-3 transition hover:bg-slate-50 {{ $notification->read_at ? '' : 'bg-blue-50/60' }}">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600"><i data-feather="{{ $notification->data['icon'] ?? 'bell' }}" class="h-4 w-4"></i></span>
                            <span class="min-w-0 flex-1"><span class="flex items-start justify-between gap-2"><span class="text-xs font-semibold text-slate-800">{{ $notification->data['title'] ?? 'Notifikasi baru' }}</span>@unless($notification->read_at)<span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-600"></span>@endunless</span><span class="mt-1 block text-xs leading-5 text-slate-600">{{ $notification->data['message'] ?? '' }}</span><span class="mt-1 block text-[11px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span></span>
                        </a>
                    @empty
                        <div class="px-4 py-10 text-center"><i data-feather="bell-off" class="mx-auto mb-2 h-6 w-6 text-slate-300"></i><p class="text-sm font-medium text-slate-600">Belum ada notifikasi</p><p class="mt-1 text-xs text-slate-400">Aktivitas penting akan muncul di sini.</p></div>
                    @endforelse
                </div>
                <a href="{{ route('owner.notifications.index') }}" class="block border-t border-slate-100 px-4 py-3 text-center text-xs font-semibold text-blue-700 hover:bg-slate-50">Lihat semua notifikasi</a>
            </div>
        </div>

        <div class="hidden h-11 items-center gap-3 rounded-xl border border-slate-100 px-3 sm:flex">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1D4E80] text-white">
                <i data-feather="user" class="h-4 w-4"></i>
            </div>
            <div class="max-w-[140px]">
                <p class="truncate text-[13px] font-semibold leading-5 text-slate-800">
                    {{ auth()->user()->fname ?? 'owner' }} {{ auth()->user()->lname ?? '' }}</p>
                <p class="text-[11px] leading-4 text-slate-500">owner</p>
            </div>
        </div>

        <form id="logout-form" method="POST" action="{{ route('logout') }}"
            data-confirm-modal
            data-confirm-variant="danger"
            data-confirm-title="Konfirmasi keluar"
            data-confirm-message="Apakah kamu yakin ingin keluar dari akun nesaKost Management?"
            data-confirm-accept="Ya, keluar">
            @csrf
            <button type="submit" id="logout-button"
                class="inline-flex h-10 items-center gap-2 rounded-lg bg-red-50 px-3 text-[13px] font-semibold text-red-600 transition hover:bg-red-100 sm:px-4">
                <i data-feather="log-out" class="h-4 w-4"></i>
                <span class="hidden sm:inline">Keluar</span>
            </button>
        </form>
    </div>
</header>


<script>
(() => {
    const root = document.getElementById('owner-notification-root');
    const toggle = document.getElementById('owner-notification-toggle');
    const menu = document.getElementById('owner-notification-menu');
    if (!root || !toggle || !menu || root.dataset.bound === '1') return;
    root.dataset.bound = '1';
    const close = () => { menu.classList.add('hidden'); toggle.setAttribute('aria-expanded', 'false'); };
    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        const opening = menu.classList.contains('hidden');
        menu.classList.toggle('hidden', !opening);
        toggle.setAttribute('aria-expanded', String(opening));
    });
    document.addEventListener('click', (event) => { if (!root.contains(event.target)) close(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
})();
</script>
