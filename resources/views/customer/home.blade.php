@extends('components.layouts.customer')

@section('title', 'nesaKost - Temukan Kost Dekat UNESA')

@section('content')
<section id="cari" class="bg-gradient-to-b from-slate-50 to-white">
    <div class="mx-auto grid max-w-7xl items-center gap-12 px-6 py-20 lg:grid-cols-2 lg:py-28">
        <div>
            <span class="mb-5 inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-blue-900">
                Kost di sekitar UNESA
            </span>

            <h1 class="max-w-xl text-4xl font-semibold leading-tight tracking-tight text-slate-900 md:text-5xl lg:text-6xl">
                Temukan tempat tinggal yang <span class="text-blue-900">pas untukmu.</span>
            </h1>

            <p class="mt-6 max-w-lg text-sm leading-7 text-slate-500 md:text-base">
                Cari kost berdasarkan kampus tujuan dan tipe hunian. Mulai pencarianmu dengan informasi yang ringkas, jelas, dan mudah dibandingkan.
            </p>

            <div class="mt-5 flex items-center gap-2 text-xs text-slate-500">
                <span class="text-green-600">✓</span>
                Pencarian berdasarkan radius maksimal 5 km dari kampus pilihan.
            </div>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/60 md:p-8">
            <h2 class="text-xl font-semibold text-slate-900">Mulai cari kost</h2>
            <p class="mt-1 text-sm text-slate-500">Pilih kampus sebagai titik referensi pencarian.</p>

            <form action="{{ route('customer.home') }}#properti" method="GET" class="mt-7 space-y-6">
                <fieldset>
                    <legend class="mb-3 block text-sm font-medium text-slate-700">Kampus tujuan</legend>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach([
                            'kampus_1' => ['UNESA Kampus 1', 'Ketintang'],
                            'kampus_2' => ['UNESA Kampus 2', 'Lidah Wetan'],
                        ] as $value => $campusOption)
                            <label class="cursor-pointer">
                                <input type="radio" name="campus" value="{{ $value }}" class="peer sr-only"
                                    {{ $campusKey === $value ? 'checked' : '' }}>
                                <span class="block rounded-xl border border-slate-200 p-4 transition peer-checked:border-blue-900 peer-checked:bg-blue-50">
                                    <span class="mb-3 flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-blue-900">⌖</span>
                                    <span class="block text-sm font-semibold text-slate-800">{{ $campusOption[0] }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">{{ $campusOption[1] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="mb-3 block text-sm font-medium text-slate-700">Tipe kost (opsional)</legend>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach(['putra' => 'Putra', 'putri' => 'Putri', 'campuran' => 'Campuran'] as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="gender" value="{{ $value }}" class="peer sr-only"
                                    {{ $selectedGender === $value ? 'checked' : '' }}>
                                <span class="block rounded-lg border border-slate-200 px-2 py-3 text-center text-xs font-medium text-slate-600 transition peer-checked:border-blue-900 peer-checked:bg-blue-50 peer-checked:text-blue-900">
                                    {{ $label }}
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <label class="mt-3 flex cursor-pointer items-center gap-2 text-xs text-slate-500">
                        <input type="radio" name="gender" value="" class="accent-blue-900"
                            {{ $selectedGender === '' ? 'checked' : '' }}>
                        Semua kategori
                    </label>
                </fieldset>

                <button type="submit" class="w-full rounded-lg bg-blue-900 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-800">
                    Cari kost di sekitar kampus →
                </button>
            </form>
        </div>
    </div>
</section>

<section id="properti" class="bg-slate-50 py-20">
    <div class="mx-auto max-w-7xl px-6">
        <div class="mb-10 flex flex-col justify-between gap-5 md:flex-row md:items-end">
            <div>
                <span class="text-xs font-semibold uppercase tracking-widest text-blue-900">Pilihan kost</span>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Informasi penting, mudah dibandingkan.</h2>
                <p class="mt-3 max-w-xl text-sm leading-7 text-slate-500">
                    Temukan properti kost di sekitar kampus UNESA dengan informasi harga, jarak, tipe, dan ketersediaan.
                </p>
            </div>
            <div class="text-sm text-slate-500">{{ $properties->total() }} properti ditemukan</div>
        </div>

        @if(!$hasCoordinates)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-800">
                Koordinat kampus belum dikonfigurasi. Lengkapi koordinat UNESA pada file .env untuk mengaktifkan pencarian.
            </div>
        @elseif($properties->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
                <div class="text-4xl">⌕</div>
                <h3 class="mt-4 font-semibold text-slate-800">Belum ada kost yang ditemukan</h3>
                <p class="mt-2 text-sm text-slate-500">Coba pilih kategori lain atau kampus tujuan yang berbeda.</p>
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($properties as $property)
                    <x-customer.property-card :property="$property" />
                @endforeach
            </div>

            <div class="mt-10">{{ $properties->links() }}</div>
        @endif

        <p class="mt-6 text-xs text-slate-400">
            Jarak merupakan estimasi garis lurus dari titik kampus tujuan, bukan jarak perjalanan melalui jalan.
        </p>
    </div>
</section>

<section class="scroll-mt-24 bg-white py-20">
    <div class="mx-auto max-w-7xl px-6">
        <div class="max-w-xl">
            <span class="text-xs font-semibold uppercase tracking-widest text-blue-900">Untuk mahasiswa UNESA</span>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Mencari kost tanpa berputar-putar.</h2>
        </div>

        <div class="mt-12 grid gap-10 md:grid-cols-3">
            <div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-900">⌖</div>
                <h3 class="mt-5 font-semibold text-slate-800">Mulai dari kampusmu</h3>
                <p class="mt-3 text-sm leading-7 text-slate-500">Tentukan Kampus 1 atau Kampus 2 untuk melihat pilihan kost di sekitar lokasi kampus tujuan.</p>
            </div>
            <div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-900">▦</div>
                <h3 class="mt-5 font-semibold text-slate-800">Bandingkan dengan jelas</h3>
                <p class="mt-3 text-sm leading-7 text-slate-500">Harga, jarak, kategori, dan ketersediaan kamar disajikan agar mudah dipahami.</p>
            </div>
            <div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-900">✓</div>
                <h3 class="mt-5 font-semibold text-slate-800">Informasi yang transparan</h3>
                <p class="mt-3 text-sm leading-7 text-slate-500">Detail kost dapat diketahui sebelum kamu melanjutkan proses booking.</p>
            </div>
        </div>
    </div>
</section>

<section id="pemilik" class="bg-white pb-20">
    <div class="mx-auto max-w-7xl px-6">
        <div class="overflow-hidden rounded-2xl bg-blue-900 p-8 text-white md:p-14">
            <div class="grid items-center gap-10 md:grid-cols-2">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-blue-200">Untuk pemilik kost</span>
                    <h2 class="mt-4 text-3xl font-semibold leading-tight md:text-4xl">Kelola informasi kost dengan lebih teratur.</h2>
                    <p class="mt-5 max-w-lg text-sm leading-7 text-blue-100">
                        Kenalkan properti Anda kepada calon penghuni UNESA. Kelola informasi kamar, harga, dan ketersediaan melalui satu platform.
                    </p>
                    @if(\Illuminate\Support\Facades\Route::has('login'))
                        <a href="{{ route('login') }}" class="mt-8 inline-flex rounded-lg bg-white px-5 py-3 text-sm font-semibold text-blue-900 transition hover:bg-blue-50">
                            Pelajari untuk pemilik kost →
                        </a>
                    @else
                        <span class="mt-8 inline-flex rounded-lg bg-white/15 px-5 py-3 text-sm text-white">Pendaftaran pemilik kost segera hadir</span>
                    @endif
                </div>

                <div class="rounded-xl border border-white/20 bg-white/10 p-6">
                    <p class="text-sm font-medium text-blue-100">Ringkasan pengelolaan properti</p>
                    <div class="mt-6 grid grid-cols-2 gap-4">
                        <div class="rounded-lg bg-white/10 p-4">
                            <p class="text-xs text-blue-200">Data properti</p>
                            <p class="mt-2 text-xl font-semibold">Teratur</p>
                        </div>
                        <div class="rounded-lg bg-white/10 p-4">
                            <p class="text-xs text-blue-200">Informasi kamar</p>
                            <p class="mt-2 text-xl font-semibold">Terpusat</p>
                        </div>
                    </div>
                    <div class="mt-4 rounded-lg bg-white/10 p-4">
                        <p class="text-xs text-blue-200">Kelola informasi kost dalam satu platform</p>
                        <div class="mt-4 h-2 rounded-full bg-white/20">
                            <div class="h-2 w-3/4 rounded-full bg-white"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
