<header
    class="fixed right-0 top-0 z-30 flex h-[72px] items-center justify-between border-b border-slate-100 bg-white px-4 shadow-[0_2px_8px_rgba(15,23,42,0.04)] sm:px-6 lg:left-[260px] lg:px-8">
    <div class="flex min-w-0 flex-1 items-center gap-3">
        <button id="sidebar-open" type="button" aria-label="Buka menu"
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 lg:hidden">
            <i data-feather="menu" class="h-5 w-5"></i>
        </button>
        <span class="truncate text-sm font-semibold text-slate-700 md:hidden">nesaKost Management</span>
    </div>

    <div class="ml-3 flex shrink-0 items-center gap-3 sm:gap-5">
        <button type="button" aria-label="Notifikasi"
            class="relative flex h-10 w-9 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100">
            <i data-feather="bell" class="h-5 w-5"></i>
            <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-600 ring-2 ring-white"></span>
        </button>

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

        <form id="logout-form" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="button" id="logout-button"
                class="inline-flex h-10 items-center gap-2 rounded-lg bg-red-50 px-3 text-[13px] font-semibold text-red-600 transition hover:bg-red-100 sm:px-4">
                <i data-feather="log-out" class="h-4 w-4"></i>
                <span class="hidden sm:inline">Keluar</span>
            </button>
        </form>
    </div>
</header>


<!-- Logout Confirmation Modal -->
<div id="logout-modal"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/50 px-4 backdrop-blur-[2px]">

    <div id="logout-modal-content"
        class="w-full max-w-sm scale-95 rounded-2xl bg-white p-6 opacity-0 shadow-2xl transition-all duration-200">

        <!-- Icon -->
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-50">
            <i data-feather="log-out" class="h-6 w-6 text-red-600"></i>
        </div>

        <!-- Content -->
        <div class="mt-5 text-center">
            <h3 class="text-lg font-bold text-slate-800">
                Konfirmasi Keluar
            </h3>

            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                Apakah Anda yakin ingin keluar dari akun nesaKost Management?
            </p>
        </div>

        <!-- Actions -->
        <div class="mt-7 flex gap-3">
            <button type="button" id="cancel-logout"
                class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                Batal
            </button>

            <button type="button" id="confirm-logout"
                class="flex-1 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                Ya, Keluar
            </button>
        </div>
    </div>
</div>