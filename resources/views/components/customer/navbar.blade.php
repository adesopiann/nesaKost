<nav class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <a href="{{ route('customer.home') }}" class="flex items-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-900 font-bold text-white">n</span>
            <span class="text-xl font-semibold tracking-tight">nesa<span class="text-blue-900">Kost</span></span>
        </a>

        <div class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex">
            <a href="{{ route('customer.home') }}#cari" class="transition hover:text-blue-900">Cari Kost</a>
            <a href="{{ route('customer.home') }}#properti" class="transition hover:text-blue-900">Rekomendasi</a>
            <a href="{{ route('customer.home') }}#pemilik" class="transition hover:text-blue-900">Untuk Pemilik Kost</a>
        </div>

        <div class="flex items-center gap-3">
            @if(\Illuminate\Support\Facades\Route::has('login'))
                <a href="{{ route('login') }}" class="hidden text-sm font-medium text-slate-600 hover:text-blue-900 sm:block">Masuk</a>
                <a href="{{ route('register.role') }}" class="rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-800">Daftar</a>
            @else
                <span class="rounded-lg bg-slate-100 px-4 py-2.5 text-xs text-slate-500">Autentikasi belum dikonfigurasi</span>
            @endif
        </div>
    </div>
</nav>
