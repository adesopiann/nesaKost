@extends('components.layouts.guest.container')

@section('title', 'Pilih Pembayaran - nesaKost')

@section('content')
    @php
        $groups = [
            'Virtual Account' => ['va_bca', 'va_mandiri', 'va_bni', 'va_bri', 'va_permata'],
            'E-wallet' => ['dana', 'ovo', 'gopay', 'shopeepay'],
            'QRIS' => ['qris'],
        ];

        $descriptions = [
            'va_bca' => 'ATM, m-BCA, atau KlikBCA',
            'va_mandiri' => 'ATM dan Livin’ by Mandiri',
            'va_bni' => 'ATM dan BNI Mobile Banking',
            'va_bri' => 'ATM dan BRImo',
            'va_permata' => 'ATM dan mobile banking Permata',
            'dana' => 'Lanjutkan melalui aplikasi DANA',
            'ovo' => 'Lanjutkan melalui aplikasi OVO',
            'gopay' => 'Lanjutkan melalui aplikasi GoPay',
            'shopeepay' => 'Lanjutkan melalui aplikasi ShopeePay',
            'qris' => 'Satu kode untuk aplikasi bank dan e-wallet',
        ];

        $logos = [
            'va_bca' => 'BCA', 'va_mandiri' => 'mandiri', 'va_bni' => 'BNI',
            'va_bri' => 'BRI', 'va_permata' => 'Permata', 'dana' => 'DANA',
            'ovo' => 'OVO', 'gopay' => 'GoPay', 'shopeepay' => 'SPay', 'qris' => 'QRIS',
        ];

        $currentMethod = old('payment_method', $latestPayment?->payment_method);
        $total = (float) ($latestPayment?->amount ?? ((float) $booking->price_snapshot * (int) $booking->duration_month));
    @endphp

    <section class="min-h-[70vh] bg-slate-50 py-8 text-slate-800 md:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('tenant.bookings.show', $booking->id) }}"
               class="inline-flex items-center gap-2 text-sm font-medium text-blue-900 transition hover:text-blue-700">
                <span aria-hidden="true" class="font-semibold">←</span>
                Kembali ke detail booking
            </a>

            <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_350px]">
                <form action="{{ route('tenant.bookings.payments.method', $booking->id) }}" method="POST" class="min-w-0 space-y-5">
                    @csrf

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-blue-900">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-50">1</span>
                                    Langkah 1 dari 2
                                </div>
                                <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">
                                    Pilih metode pembayaran
                                </h1>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                    Pilih metode pembayaran yang paling nyaman untuk menyelesaikan booking kost kamu.
                                </p>
                            </div>
                            <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                Menunggu pembayaran
                            </span>
                        </div>

                        <div class="mt-6 flex items-center gap-2" aria-label="Progres pembayaran">
                            <span class="h-1.5 flex-1 rounded-full bg-blue-900"></span>
                            <span class="h-1.5 flex-1 rounded-full bg-slate-200"></span>
                        </div>

                        @if ($errors->any())
                            <div class="mt-5 flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                <span aria-hidden="true" class="font-semibold">!</span>
                                <span>{{ $errors->first() }}</span>
                            </div>
                        @endif

                        @if ($latestPayment && in_array($latestPayment->status, ['failed', 'expired'], true))
                            <div class="mt-5 flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                                <span aria-hidden="true" class="font-semibold">i</span>
                                <span>
                                    Percobaan pembayaran sebelumnya {{ $latestPayment->status === 'failed' ? 'gagal' : 'kedaluwarsa' }}.
                                    Pilih metode untuk mencoba kembali. Riwayat percobaan sebelumnya tetap tersimpan.
                                </span>
                            </div>
                        @endif

                        @foreach ($groups as $group => $methods)
                            <div class="mt-8 first:mt-7">
                                <div class="mb-3 flex items-center justify-between">
                                    <h2 class="text-base font-semibold text-slate-900">{{ $group }}</h2>
                                    <span class="text-xs text-slate-400">{{ count($methods) }} metode</span>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($methods as $code)
                                        @php($method = $paymentMethods[$code])
                                        <label class="group relative flex min-h-[76px] cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-blue-300 hover:bg-blue-50/40 has-[:checked]:border-blue-900 has-[:checked]:bg-blue-50 has-[:checked]:ring-1 has-[:checked]:ring-blue-900 {{ $code === 'qris' ? 'sm:col-span-2' : '' }}">
                                            <input
                                                type="radio"
                                                name="payment_method"
                                                value="{{ $code }}"
                                                required
                                                {{ $currentMethod === $code ? 'checked' : '' }}
                                                class="h-4 w-4 shrink-0 accent-blue-900"
                                            >
                                            <span class="flex h-10 min-w-12 items-center justify-center rounded-lg border border-slate-100 bg-slate-50 px-2 text-[11px] font-bold text-blue-950">
                                                {{ $logos[$code] }}
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-semibold text-slate-900">{{ $method['label'] }}</span>
                                                <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $descriptions[$code] }}</span>
                                            </span>
                                            <span class="hidden h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-900 text-white group-has-[:checked]:flex">
                                                <span aria-hidden="true" class="font-semibold">✓</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div class="mt-7 flex gap-3 rounded-xl border border-blue-100 bg-blue-50/70 p-4">
                            <span aria-hidden="true" class="font-semibold">✓</span>
                            <p class="text-xs leading-5 text-slate-600">
                                <strong class="font-semibold text-blue-950">Pembayaran simulasi.</strong>
                                Nomor Virtual Account, QRIS, dan instruksi pada tahap berikutnya hanya untuk demonstrasi.
                                Tidak ada transaksi atau pemindahan uang sungguhan.
                            </p>
                        </div>
                    </div>

                    <button type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-900 px-5 py-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-100">
                        Lanjutkan ke instruksi pembayaran
                        <span aria-hidden="true" class="font-semibold">→</span>
                    </button>
                </form>

                <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-24">
                    <div class="flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-900">
                            <span aria-hidden="true" class="font-semibold">▤</span>
                        </span>
                        <h2 class="text-base font-semibold text-slate-900">Ringkasan booking</h2>
                    </div>

                    <div class="mt-5 space-y-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Kode booking</span>
                            <strong class="text-right font-semibold text-slate-800">{{ $booking->booking_code }}</strong>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Kamar</span>
                            <strong class="text-right font-semibold text-slate-800">{{ $booking->room->room_code }}</strong>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Periode sewa</span>
                            <strong class="text-right font-semibold text-slate-800">{{ $booking->duration_month }} bulan</strong>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Mulai sewa</span>
                            <strong class="text-right font-semibold text-slate-800">{{ $booking->start_date->format('d M Y') }}</strong>
                        </div>
                    </div>

                    <div class="my-5 border-t border-dashed border-slate-200"></div>

                    <div class="flex items-end justify-between gap-3">
                        <span class="text-sm font-medium text-slate-600">Total pembayaran</span>
                        <span class="text-xl font-bold tracking-tight text-slate-900">Rp{{ number_format($total, 0, ',', '.') }}</span>
                    </div>

                    <div class="mt-5 rounded-xl bg-slate-50 p-4">
                        <div class="flex items-start gap-2">
                            <span aria-hidden="true" class="font-semibold">◷</span>
                            <p class="text-xs leading-5 text-slate-500">
                                Selesaikan pembayaran sebelum
                                <strong class="font-semibold text-slate-700">{{ $booking->expires_at?->format('d M Y, H:i') ?? '-' }} WIB</strong>.
                                Booking akan kedaluwarsa setelah batas waktu berakhir.
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection
