@extends('components.layouts.guest.container')

@section('title', 'Untuk Pemilik Kost - nesaKost')

@section('content')
    {{-- HERO --}}
    <section data-page="owner-landing" class="relative overflow-hidden bg-gradient-to-b from-slate-50 to-white">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-6 py-16 md:py-20 lg:grid-cols-2 lg:gap-16 lg:py-24">
            <div data-owner-hero-copy class="relative z-10">
                <span data-owner-intro class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-blue-900">
                    <i data-feather="home" class="h-4 w-4"></i>
                    Untuk pemilik kost
                </span>
                <h1 data-owner-intro class="mt-6 max-w-2xl text-4xl font-semibold leading-tight tracking-tight text-slate-900 md:text-5xl lg:text-6xl">
                    Kelola properti kost dengan <span class="text-blue-900">lebih teratur.</span>
                </h1>
                <p data-owner-intro class="mt-6 max-w-xl text-sm leading-7 text-slate-500 md:text-base md:leading-8">
                    Kenalkan properti Anda kepada mahasiswa UNESA dan kelola informasi properti, kamar, booking, serta pembayaran melalui satu platform.
                </p>
                <div data-owner-intro class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('register.create', ['role' => 'owner']) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-900 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/10 transition hover:bg-blue-800">
                        Daftar sebagai pemilik <i data-feather="arrow-right" class="h-4 w-4"></i>
                    </a>
                    <a href="#fitur" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-6 py-3.5 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:text-blue-900">
                        Lihat fitur
                    </a>
                </div>
                <div class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-2"><i data-feather="check-circle" class="h-4 w-4 text-blue-900"></i> Informasi lebih terpusat</span>
                    <span class="inline-flex items-center gap-2"><i data-feather="check-circle" class="h-4 w-4 text-blue-900"></i> Dirancang untuk area UNESA</span>
                </div>
            </div>

            {{-- Ilustrasi dashboard, dibuat dari komponen UI agar selaras dengan produk --}}
            <div data-owner-dashboard class="relative mx-auto w-full max-w-xl">
                <div class="absolute -inset-5 rounded-[2rem] bg-blue-100/70 blur-2xl"></div>
                <div data-dashboard-float class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl shadow-slate-300/50 sm:p-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <p class="text-xs text-slate-400">Dashboard pemilik</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">Ringkasan properti</p>
                        </div>
                        <span class="rounded-lg bg-blue-50 p-2 text-blue-900"><i data-feather="layout" class="h-5 w-5"></i></span>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between text-slate-400"><span class="text-xs">Properti</span><i data-feather="home" class="h-4 w-4"></i></div>
                            <p class="mt-3 text-2xl font-semibold text-slate-900">Terdata</p>
                            <p class="mt-1 text-[11px] text-slate-500">Informasi dalam satu tempat</p>
                        </div>
                        <div class="rounded-xl bg-blue-50 p-4">
                            <div class="flex items-center justify-between text-blue-700"><span class="text-xs">Kamar</span><i data-feather="grid" class="h-4 w-4"></i></div>
                            <p class="mt-3 text-2xl font-semibold text-blue-950">Terpantau</p>
                            <p class="mt-1 text-[11px] text-blue-800/70">Harga dan status kamar</p>
                        </div>
                    </div>
                    <div class="mt-4 rounded-xl border border-slate-100 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold text-slate-700">Aktivitas pengelolaan</p>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-medium text-emerald-700">Terorganisasi</span>
                        </div>
                        <div class="mt-4 space-y-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-900"><i data-feather="building" class="h-4 w-4"></i></span>
                                <div class="min-w-0 flex-1"><div class="h-2 w-28 rounded bg-slate-200"></div><div class="mt-2 h-1.5 w-20 rounded bg-slate-100"></div></div>
                                <span class="text-[10px] text-slate-400">Properti</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-700"><i data-feather="calendar" class="h-4 w-4"></i></span>
                                <div class="min-w-0 flex-1"><div class="h-2 w-24 rounded bg-slate-200"></div><div class="mt-2 h-1.5 w-16 rounded bg-slate-100"></div></div>
                                <span class="text-[10px] text-slate-400">Booking</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700"><i data-feather="credit-card" class="h-4 w-4"></i></span>
                                <div class="min-w-0 flex-1"><div class="h-2 w-32 rounded bg-slate-200"></div><div class="mt-2 h-1.5 w-20 rounded bg-slate-100"></div></div>
                                <span class="text-[10px] text-slate-400">Pembayaran</span>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 text-center text-[11px] text-slate-400">Ilustrasi tampilan pengelolaan nesaKost</p>
                </div>
            </div>
        </div>
    </section>

    {{-- VALUE PROPOSITION --}}
    <section class="border-y border-slate-100 bg-white">
        <div data-owner-benefits class="mx-auto grid max-w-7xl gap-8 px-6 py-12 md:grid-cols-3 md:gap-10 md:py-14">
            <div data-owner-benefit class="flex gap-4 rounded-xl p-2 transition-colors hover:bg-slate-50">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-900"><i data-feather="users" class="h-5 w-5"></i></span>
                <div><h2 class="text-sm font-semibold text-slate-900">Menjangkau mahasiswa UNESA</h2><p class="mt-2 text-sm leading-6 text-slate-500">Tampilkan informasi kost agar lebih mudah ditemukan calon penghuni di sekitar kampus.</p></div>
            </div>
            <div data-owner-benefit class="flex gap-4 rounded-xl p-2 transition-colors hover:bg-slate-50">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-900"><i data-feather="layers" class="h-5 w-5"></i></span>
                <div><h2 class="text-sm font-semibold text-slate-900">Informasi lebih terpusat</h2><p class="mt-2 text-sm leading-6 text-slate-500">Kelola data properti dan kamar tanpa harus berpindah-pindah pencatatan.</p></div>
            </div>
            <div data-owner-benefit class="flex gap-4 rounded-xl p-2 transition-colors hover:bg-slate-50">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-900"><i data-feather="clipboard" class="h-5 w-5"></i></span>
                <div><h2 class="text-sm font-semibold text-slate-900">Aktivitas lebih mudah dipantau</h2><p class="mt-2 text-sm leading-6 text-slate-500">Pantau booking, data penyewa, masa sewa, dan pembayaran melalui sistem.</p></div>
            </div>
        </div>
    </section>

    {{-- FEATURES --}}
    <section id="fitur" class="scroll-mt-24 bg-slate-50 py-16 md:py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div data-owner-section-heading class="mx-auto max-w-2xl text-center">
                <span class="text-xs font-semibold uppercase tracking-widest text-blue-900">Fitur pengelolaan</span>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 md:text-4xl">Hal penting dalam satu platform.</h2>
                <p class="mt-4 text-sm leading-7 text-slate-500">Dari informasi properti hingga transaksi penyewaan, nesaKost membantu pemilik mengelola operasional kost dengan lebih terstruktur.</p>
            </div>

            <div data-owner-features class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $ownerFeatures = [
                        ['icon' => 'home', 'title' => 'Manajemen properti', 'description' => 'Simpan dan kelola informasi properti kost, termasuk properti pada beberapa lokasi atau cabang.'],
                        ['icon' => 'grid', 'title' => 'Manajemen kamar', 'description' => 'Atur tipe kamar, harga sewa, deskripsi, fasilitas, foto, dan status ketersediaan.'],
                        ['icon' => 'calendar-check', 'title' => 'Booking penyewa', 'description' => 'Lihat informasi pemesanan kamar yang masuk melalui platform nesaKost.'],
                        ['icon' => 'users', 'title' => 'Data penyewa', 'description' => 'Akses informasi penyewa dan keterkaitannya dengan kamar serta masa sewa.'],
                        ['icon' => 'wallet', 'title' => 'Pemantauan pembayaran', 'description' => 'Periksa informasi pembayaran yang berkaitan dengan transaksi penyewaan.'],
                        ['icon' => 'layout', 'title' => 'Dashboard pengelola', 'description' => 'Lihat ringkasan informasi operasional properti melalui halaman dashboard.'],
                    ];
                @endphp
                @foreach ($ownerFeatures as $feature)
                    <article data-motion-hover class="rounded-2xl border border-slate-200 bg-white p-6 transition hover:-translate-y-1 hover:border-blue-200 hover:shadow-lg hover:shadow-slate-200/50">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-900"><i data-feather="{{ $feature['icon'] }}" class="h-5 w-5"></i></span>
                        <h3 class="mt-5 text-base font-semibold text-slate-900">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-500">{{ $feature['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- HOW IT WORKS --}}
    <section class="bg-white py-16 md:py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-blue-900">Mulai bergabung</span>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 md:text-4xl">Mulai dari informasi kost Anda.</h2>
                    <p class="mt-4 text-sm leading-7 text-slate-500">Siapkan informasi properti dan kamar agar calon penyewa mendapatkan gambaran yang jelas sebelum melakukan booking.</p>
                    <a href="{{ route('register.create', ['role' => 'owner']) }}" class="mt-7 inline-flex items-center gap-2 text-sm font-semibold text-blue-900 hover:text-blue-700">Mulai pendaftaran <i data-feather="arrow-right" class="h-4 w-4"></i></a>
                </div>
                <div data-owner-steps class="space-y-7">
                    @php
                        $ownerSteps = [
                            ['number' => '01', 'title' => 'Buat akun pemilik', 'description' => 'Daftarkan akun melalui formulir pendaftaran pemilik kost.'],
                            ['number' => '02', 'title' => 'Lengkapi informasi properti', 'description' => 'Masukkan nama, lokasi, informasi, dan detail properti kost Anda.'],
                            ['number' => '03', 'title' => 'Tambahkan kamar', 'description' => 'Atur tipe, harga, fasilitas, foto, dan informasi kamar yang ditawarkan.'],
                            ['number' => '04', 'title' => 'Kelola aktivitas penyewaan', 'description' => 'Pantau booking, penyewa, masa sewa, dan informasi pembayaran melalui dashboard.'],
                        ];
                    @endphp
                    @foreach ($ownerSteps as $step)
                        <div class="flex gap-5">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-900 text-xs font-semibold text-white">{{ $step['number'] }}</span>
                            <div class="border-b border-slate-100 pb-6">
                                <h3 class="text-base font-semibold text-slate-900">{{ $step['title'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-500">{{ $step['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="bg-slate-50 py-16 md:py-20">
        <div class="mx-auto max-w-4xl px-6">
            <div class="text-center">
                <span class="text-xs font-semibold uppercase tracking-widest text-blue-900">FAQ</span>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Pertanyaan yang sering diajukan.</h2>
            </div>
            <div class="mt-10 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white px-6">
                @php
                    $ownerFaqs = [
                        ['question' => 'Siapa yang dapat mendaftar sebagai pemilik?', 'answer' => 'Pemilik atau pihak yang berwenang mengelola properti kost dapat membuat akun pemilik melalui halaman pendaftaran nesaKost.'],
                        ['question' => 'Apakah saya dapat mengelola lebih dari satu properti?', 'answer' => 'Ya. Sistem pemilik dirancang untuk mengelola properti dan cabang yang berada dalam cakupan akun pemilik.'],
                        ['question' => 'Informasi apa yang perlu disiapkan?', 'answer' => 'Siapkan informasi properti seperti nama, alamat, lokasi, deskripsi, serta informasi kamar seperti tipe, harga, fasilitas, dan foto.'],
                        ['question' => 'Bagaimana saya mengetahui booking yang masuk?', 'answer' => 'Informasi booking dan aktivitas penyewaan dapat dipantau melalui dashboard pengelola setelah Anda masuk ke akun.'],
                        ['question' => 'Apakah ada biaya untuk bergabung?', 'answer' => 'Informasi biaya atau kebijakan berlangganan belum dicantumkan pada halaman ini. Silakan hubungi pengelola nesaKost untuk informasi lebih lanjut.'],
                    ];
                @endphp
                @foreach ($ownerFaqs as $faq)
                    <details class="group py-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-slate-800">
                            {{ $faq['question'] }}
                            <i data-feather="plus" class="h-4 w-4 shrink-0 text-blue-900 transition group-open:rotate-45"></i>
                        </summary>
                        <p class="mt-3 max-w-3xl pr-8 text-sm leading-7 text-slate-500">{{ $faq['answer'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FINAL CTA --}}
    <section class="bg-white py-16 md:py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div data-owner-final-cta class="overflow-hidden rounded-2xl bg-blue-900 px-6 py-12 text-center text-white md:px-12 md:py-16">
                <span class="text-xs font-semibold uppercase tracking-widest text-blue-200">Bergabung dengan nesaKost</span>
                <h2 class="mx-auto mt-4 max-w-2xl text-3xl font-semibold tracking-tight md:text-4xl">Siap mengelola kost dengan lebih teratur?</h2>
                <p class="mx-auto mt-4 max-w-xl text-sm leading-7 text-blue-100">Mulai perkenalkan properti Anda kepada mahasiswa UNESA dan kelola informasi penyewaan melalui nesaKost.</p>
                <a href="{{ route('register.create', ['role' => 'owner']) }}" class="mt-8 inline-flex items-center justify-center gap-2 rounded-lg bg-white px-6 py-3.5 text-sm font-semibold text-blue-900 transition hover:bg-blue-50">Daftar sebagai pemilik <i data-feather="arrow-right" class="h-4 w-4"></i></a>
                <p class="mt-4 text-xs text-blue-200">Sudah memiliki akun? <a href="{{ route('login') }}" class="font-semibold text-white underline underline-offset-4">Masuk di sini</a></p>
            </div>
        </div>
    </section>
@endsection
