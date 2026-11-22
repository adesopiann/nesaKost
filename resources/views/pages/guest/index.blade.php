@extends('components.layouts.guest.container')

@section('title', 'nesaKost - Temukan Kost Dekat UNESA')

@section('content')
    {{-- HERO + PENCARIAN --}}
    <section id="cari" data-page="landing" class="relative isolate overflow-hidden bg-gradient-to-b from-slate-50 via-white to-white">
        {{-- BLUE GLOW DI BELAKANG CARD PENCARIAN --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div data-motion-orb="large" class="absolute right-[-8%] top-1/2 h-[24rem] w-[34rem] -translate-y-1/2 rounded-full bg-blue-200/60 blur-[100px] md:right-[2%] md:h-[30rem] md:w-[38rem]"></div>
            <div data-motion-orb="small" class="absolute right-[18%] top-[12%] h-56 w-56 rounded-full bg-sky-100/70 blur-[85px]"></div>
            <div class="hero-grid pointer-events-none absolute inset-0 opacity-40"></div>
        </div>
        <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-6 py-12 md:py-16 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16 lg:py-20">
            <div class="relative z-10">
                <span data-hero="eyebrow" class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-white/80 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-blue-900 shadow-sm shadow-blue-100/70 backdrop-blur">
                    <span class="hero-pulse-dot"></span>
                    Platform pencarian kost untuk mahasiswa UNESA
                </span>

                <h1 data-hero="headline" class="mt-6 max-w-2xl text-4xl font-semibold leading-[1.12] tracking-tight text-slate-900 md:text-5xl lg:text-6xl">
                    Temukan kost yang <span class="hero-typed-word text-blue-900" data-typewriter data-words="lebih dekat|lebih nyaman|lebih sesuai">lebih dekat</span><span class="hero-type-cursor" aria-hidden="true"></span> dengan kampusmu.
                </h1>

                <p data-hero="description" class="mt-6 max-w-xl text-sm leading-7 text-slate-500 md:text-base md:leading-8">
                    Jelajahi pilihan kost di sekitar UNESA Kampus 1 dan Kampus 2. Bandingkan informasi lokasi, harga, tipe hunian, dan ketersediaan kamar sebelum menentukan pilihan.
                </p>

                <div data-hero="benefits" class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-3 text-xs font-medium text-slate-600">
                    <span class="inline-flex items-center gap-2"><i data-feather="check-circle" class="h-4 w-4 text-blue-900"></i> Pilihan berdasarkan kampus</span>
                    <span class="inline-flex items-center gap-2"><i data-feather="check-circle" class="h-4 w-4 text-blue-900"></i> Informasi lebih mudah dibandingkan</span>
                </div>

                <a data-hero="explore" href="#properti" class="hero-explore mt-8 inline-flex items-center gap-2 text-sm font-semibold text-blue-900 transition hover:text-blue-700">
                    Jelajahi pilihan kost <i data-feather="arrow-down" class="h-4 w-4" data-motion-arrow></i>
                </a>
            </div>

            {{-- FORM PENCARIAN --}}
            <div data-hero="search-card" class="hero-search-card relative z-10 mx-auto w-full max-w-xl rounded-2xl border border-white/80 bg-white/90 p-6 shadow-2xl shadow-blue-950/10 ring-1 ring-slate-200/70 backdrop-blur-xl md:p-8">
                <div class="hero-card-accent" aria-hidden="true"></div>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-widest text-blue-900">Mulai pencarian</span>
                        <h2 class="mt-2 text-xl font-semibold text-slate-900 md:text-2xl">Kost seperti apa yang kamu cari?</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Pilih kampus tujuan untuk melihat kost di area sekitarnya.</p>
                    </div>
                    <span data-motion-icon="search" class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-900 sm:flex">
                        <i data-feather="search" class="h-5 w-5"></i>
                    </span>
                </div>

                <form action="{{ url()->current() }}#properti" method="GET" class="mt-7 space-y-6">
                    <fieldset>
                        <legend class="mb-3 block text-sm font-semibold text-slate-700">Kampus tujuan</legend>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach([
                                'kampus_1' => ['UNESA Kampus 1', 'Ketintang'],
                                'kampus_2' => ['UNESA Kampus 2', 'Lidah Wetan'],
                            ] as $value => $campusOption)
                                <label class="cursor-pointer">
                                    <input type="radio" name="campus" value="{{ $value }}" class="peer sr-only"
                                        {{ ($campusKey ?? '') === $value ? 'checked' : '' }}>
                                    <span class="campus-option block rounded-xl border border-slate-200 p-4 transition hover:border-blue-300 hover:shadow-md peer-checked:border-blue-900 peer-checked:bg-blue-50 peer-checked:shadow-md">
                                        <span class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-blue-900">
                                            <i data-feather="map-pin" class="h-4 w-4"></i>
                                        </span>
                                        <span class="block text-sm font-semibold text-slate-800">{{ $campusOption[0] }}</span>
                                        <span class="mt-1 block text-xs text-slate-500">{{ $campusOption[1] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="mb-3 block text-sm font-semibold text-slate-700">Tipe kost <span class="font-normal text-slate-400">(opsional)</span></legend>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach(['putra' => 'Putra', 'putri' => 'Putri', 'campuran' => 'Campuran'] as $value => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" name="gender" value="{{ $value }}" class="peer sr-only"
                                        {{ ($selectedGender ?? '') === $value ? 'checked' : '' }}>
                                    <span class="block rounded-lg border border-slate-200 px-2 py-3 text-center text-xs font-medium text-slate-600 transition hover:border-blue-200 peer-checked:border-blue-900 peer-checked:bg-blue-50 peer-checked:text-blue-900">
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <label class="mt-3 inline-flex cursor-pointer items-center gap-2 text-xs text-slate-500">
                            <input type="radio" name="gender" value="" class="accent-blue-900"
                                {{ ($selectedGender ?? '') === '' ? 'checked' : '' }}>
                            Semua kategori
                        </label>
                    </fieldset>

                    <button type="submit" data-motion-button class="hero-search-button inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-900 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/15 transition hover:bg-blue-800">
                        Cari kost di sekitar kampus <i data-feather="arrow-right" class="h-4 w-4" data-motion-arrow></i>
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- DAFTAR PROPERTI --}}
    <section id="properti" class="scroll-mt-24 bg-slate-50 py-16 md:py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div data-motion="reveal" class="mb-10 flex flex-col justify-between gap-5 md:flex-row md:items-end">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-blue-900">Pilihan kost</span>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 md:text-4xl">
                        @auth
                            @if(auth()->user()->role === 'tenant')
                                Kost di Sekitar Kampus UNESA
                            @else
                                Temukan tempat tinggal yang sesuai.
                            @endif
                        @else
                            Temukan tempat tinggal yang sesuai.
                        @endauth
                    </h2>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-500">
                        Bandingkan informasi harga, jarak, tipe kost, dan ketersediaan kamar untuk membantu menentukan pilihan.
                    </p>
                </div>
                <div class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-600">
                    <i data-feather="home" class="h-4 w-4 text-blue-900"></i>
                    {{ $properties->total() }} properti ditemukan
                </div>
            </div>

            @if(!$hasCoordinates)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-800">
                    Koordinat kampus belum dikonfigurasi. Lengkapi koordinat UNESA pada file .env untuk mengaktifkan pencarian.
                </div>
            @elseif($properties->isEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-900"><i data-feather="search" class="h-6 w-6"></i></span>
                    <h3 class="mt-5 text-lg font-semibold text-slate-900">Belum ada kost yang ditemukan</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Coba pilih kategori lain atau kampus tujuan yang berbeda.</p>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($properties as $property)
                        <x-layouts.guest.property-card :property="$property" />
                    @endforeach
                </div>
                <div class="mt-10">{{ $properties->links() }}</div>
            @endif

            <p class="mt-6 text-xs leading-5 text-slate-400">
                Jarak merupakan estimasi garis lurus dari titik kampus tujuan, bukan jarak perjalanan melalui jalan.
            </p>
        </div>
    </section>

    @guest
        {{-- KEUNGGULAN UNTUK MAHASISWA --}}
        <section class="bg-white py-16 md:py-20">
            <div class="mx-auto max-w-7xl px-6">
                <div data-motion="reveal" class="mx-auto max-w-2xl text-center">
                    <span class="text-xs font-semibold uppercase tracking-widest text-blue-900">Pengalaman mencari kost</span>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 md:text-4xl">Cari kost dengan informasi yang lebih jelas.</h2>
                    <p class="mt-4 text-sm leading-7 text-slate-500">Mulai pencarian dari kampus tujuan, lalu kenali pilihan kost berdasarkan informasi yang penting bagimu.</p>
                </div>

                <div data-motion-stagger class="mt-12 grid gap-5 md:grid-cols-3">
                    <article data-motion-item class="rounded-2xl border border-slate-200 bg-white p-6 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-200/50">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-900"><i data-feather="map-pin" class="h-5 w-5"></i></span>
                        <h3 class="mt-5 text-base font-semibold text-slate-900">Mulai dari kampusmu</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-500">Pilih UNESA Kampus 1 Ketintang atau Kampus 2 Lidah Wetan sebagai titik referensi pencarian.</p>
                    </article>
                    <article data-motion-item class="rounded-2xl border border-slate-200 bg-white p-6 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-200/50">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-900"><i data-feather="sliders" class="h-5 w-5"></i></span>
                        <h3 class="mt-5 text-base font-semibold text-slate-900">Sesuaikan pilihanmu</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-500">Gunakan kategori kost dan informasi harga serta jarak untuk mempersempit pilihan.</p>
                    </article>
                    <article data-motion-item class="rounded-2xl border border-slate-200 bg-white p-6 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-200/50">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-900"><i data-feather="eye" class="h-5 w-5"></i></span>
                        <h3 class="mt-5 text-base font-semibold text-slate-900">Kenali sebelum booking</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-500">Lihat detail properti dan informasi kamar terlebih dahulu sebelum melanjutkan proses pemesanan.</p>
                    </article>
                </div>
            </div>
        </section>

        {{-- SECTION KHUSUS PEMILIK KOST --}}
        <section id="pemilik" class="scroll-mt-24 overflow-hidden bg-slate-50 py-16 md:py-20">
            <div class="mx-auto max-w-7xl px-6">
                <div data-motion="reveal" class="overflow-hidden rounded-3xl bg-blue-900 text-white shadow-xl shadow-blue-950/10">
                    <div class="grid items-center gap-10 p-7 md:p-10 lg:grid-cols-[1.05fr_0.95fr] lg:gap-14 lg:p-14">
                        <div>
                            <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-blue-100">
                                <i data-feather="briefcase" class="h-4 w-4"></i>
                                Solusi untuk pemilik kost
                            </span>
                            <h2 class="mt-6 max-w-xl text-3xl font-semibold leading-tight tracking-tight md:text-4xl">
                                Properti Anda layak dikenal lebih banyak calon penghuni UNESA.
                            </h2>
                            <p class="mt-5 max-w-xl text-sm leading-7 text-blue-100 md:text-base">
                                Hadirkan informasi kost Anda di platform pencarian yang berfokus pada mahasiswa UNESA. Kelola informasi properti, kamar, booking, dan penyewaan dengan lebih terstruktur.
                            </p>

                            <div class="mt-7 grid gap-3 sm:grid-cols-2">
                                <div class="flex items-start gap-3">
                                    <i data-feather="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-blue-200"></i>
                                    <span class="text-sm leading-6 text-blue-50">Jangkau pencari kost di sekitar dua kampus UNESA</span>
                                </div>
                                <div class="flex items-start gap-3">
                                    <i data-feather="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-blue-200"></i>
                                    <span class="text-sm leading-6 text-blue-50">Tampilkan harga dan informasi kamar dengan jelas</span>
                                </div>
                                <div class="flex items-start gap-3">
                                    <i data-feather="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-blue-200"></i>
                                    <span class="text-sm leading-6 text-blue-50">Kelola beberapa properti dalam satu akun</span>
                                </div>
                                <div class="flex items-start gap-3">
                                    <i data-feather="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-blue-200"></i>
                                    <span class="text-sm leading-6 text-blue-50">Pantau booking, penyewa, dan pembayaran</span>
                                </div>
                            </div>

                            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                                <a href="{{ route('guest.owner') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-3.5 text-sm font-semibold text-blue-900 transition hover:bg-blue-50">
                                    Pelajari untuk pemilik kost <i data-feather="arrow-right" class="h-4 w-4"></i>
                                </a>
                                <a href="{{ route('register.create', ['role' => 'owner']) }}" class="inline-flex items-center justify-center rounded-lg border border-white/30 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">
                                    Daftar sebagai pemilik
                                </a>
                            </div>
                        </div>

                        {{-- ILUSTRASI DASHBOARD PENGELOLA --}}
                        <div data-motion="reveal-right" class="relative mx-auto w-full max-w-lg">
                            <div class="absolute -inset-5 rounded-[2rem] bg-blue-400/20 blur-2xl"></div>
                            <div class="relative rounded-2xl border border-white/20 bg-white p-4 text-slate-800 shadow-2xl sm:p-6">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                                    <div>
                                        <p class="text-xs text-slate-400">Ringkasan pengelolaan</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900">Dashboard pemilik</p>
                                    </div>
                                    <span class="rounded-lg bg-blue-50 p-2 text-blue-900"><i data-feather="layout-dashboard" class="h-5 w-5"></i></span>
                                </div>
                                <div class="mt-5 grid grid-cols-2 gap-3">
                                    <div class="rounded-xl bg-slate-50 p-4">
                                        <span class="flex items-center gap-2 text-xs text-slate-500"><i data-feather="home" class="h-4 w-4 text-blue-900"></i> Properti</span>
                                        <p class="mt-3 text-lg font-semibold text-slate-900">Terpusat</p>
                                        <p class="mt-1 text-[11px] text-slate-500">Data lokasi dan cabang</p>
                                    </div>
                                    <div class="rounded-xl bg-blue-50 p-4">
                                        <span class="flex items-center gap-2 text-xs text-blue-700"><i data-feather="grid" class="h-4 w-4"></i> Kamar</span>
                                        <p class="mt-3 text-lg font-semibold text-blue-950">Terpantau</p>
                                        <p class="mt-1 text-[11px] text-blue-800/70">Harga dan ketersediaan</p>
                                    </div>
                                </div>
                                <div class="mt-3 rounded-xl border border-slate-100 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs font-semibold text-slate-700">Aktivitas penyewaan</p>
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-medium text-emerald-700">Dalam satu platform</span>
                                    </div>
                                    <div class="mt-4 space-y-3">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-900"><i data-feather="calendar" class="h-4 w-4"></i></span>
                                            <div class="min-w-0 flex-1"><p class="text-xs font-medium text-slate-700">Booking</p><p class="mt-1 text-[10px] text-slate-400">Informasi pemesanan kamar</p></div>
                                            <i data-feather="check" class="h-4 w-4 text-emerald-600"></i>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700"><i data-feather="users" class="h-4 w-4"></i></span>
                                            <div class="min-w-0 flex-1"><p class="text-xs font-medium text-slate-700">Penyewa</p><p class="mt-1 text-[10px] text-slate-400">Data dan masa sewa</p></div>
                                            <i data-feather="check" class="h-4 w-4 text-emerald-600"></i>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-700"><i data-feather="credit-card" class="h-4 w-4"></i></span>
                                            <div class="min-w-0 flex-1"><p class="text-xs font-medium text-slate-700">Pembayaran</p><p class="mt-1 text-[10px] text-slate-400">Riwayat transaksi sewa</p></div>
                                            <i data-feather="check" class="h-4 w-4 text-emerald-600"></i>
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-4 text-center text-[10px] text-slate-400">Ilustrasi fitur pengelolaan nesaKost</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div data-motion-stagger class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div data-motion-item class="rounded-2xl border border-slate-200 bg-white p-5">
                        <p class="text-sm font-semibold text-slate-900">Lebih mudah ditemukan</p>
                        <p class="mt-2 text-xs leading-6 text-slate-500">Informasi kost ditampilkan kepada calon penghuni yang mencari hunian di sekitar UNESA.</p>
                    </div>
                    <div data-motion-item class="rounded-2xl border border-slate-200 bg-white p-5">
                        <p class="text-sm font-semibold text-slate-900">Informasi lebih konsisten</p>
                        <p class="mt-2 text-xs leading-6 text-slate-500">Harga, tipe, fasilitas, foto, dan status kamar dapat dikelola melalui sistem.</p>
                    </div>
                    <div data-motion-item class="rounded-2xl border border-slate-200 bg-white p-5">
                        <p class="text-sm font-semibold text-slate-900">Operasional lebih terstruktur</p>
                        <p class="mt-2 text-xs leading-6 text-slate-500">Informasi booking, penyewa, masa sewa, dan pembayaran tersimpan dalam satu platform.</p>
                    </div>
                </div>
            </div>
        </section>
    @endguest
@endsection
