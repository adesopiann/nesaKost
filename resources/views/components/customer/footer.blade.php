<footer class="border-t border-slate-100 bg-white">
    <div class="mx-auto flex max-w-7xl flex-col gap-5 px-6 py-8 md:flex-row md:items-center md:justify-between">
        <a href="{{ route('customer.home') }}" class="text-lg font-semibold text-slate-900">nesa<span class="text-blue-900">Kost</span></a>
        <p class="text-xs text-slate-400">Platform pencarian kost untuk mahasiswa UNESA.</p>
        <div class="flex gap-6 text-xs text-slate-500">
            <a href="{{ route('customer.home') }}#properti" class="hover:text-blue-900">Cari Kost</a>
            <a href="{{ route('customer.home') }}#pemilik" class="hover:text-blue-900">Untuk Pemilik</a>
        </div>
    </div>
    <div class="border-t border-slate-100 py-4 text-center text-xs text-slate-400">
        © {{ date('Y') }} nesaKost. All rights reserved.
    </div>
</footer>
