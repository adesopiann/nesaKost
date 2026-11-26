@extends('components.layouts.guest.container')

@section('title', 'Instruksi Pembayaran - nesaKost')

@section('content')
    @php
        $isVa = str_starts_with($payment->payment_method, 'va_');
        $isQris = $payment->payment_method === 'qris';
        $amount = (float) $payment->amount;
        $reference = $payment->payment_reference ?: $payment->provider_reference;
        $methodName = $method['label'] ?? strtoupper($payment->payment_method);
        $expiryIso = $booking->expires_at?->toIso8601String();
        $bankName = $method['bank'] ?? $methodName;
    @endphp

    <section class="min-h-[70vh] bg-slate-50 py-8 text-slate-800 md:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('tenant.bookings.show', $booking->booking_code) }}"
               class="inline-flex items-center gap-2 text-sm font-medium text-blue-900 transition hover:text-blue-700">
                <i data-feather="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                Kembali ke detail booking
            </a>

            <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_350px]">
                <main class="min-w-0">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-blue-900">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-50">2</span>
                                    Langkah 2 dari 2
                                </div>
                                <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">
                                    Selesaikan pembayaran
                                </h1>
                                <p class="mt-2 text-sm leading-6 text-slate-500">
                                    Ikuti instruksi simulasi berikut untuk menyelesaikan booking kost kamu.
                                </p>
                            </div>
                            <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                Menunggu pembayaran
                            </span>
                        </div>

                        <div class="mt-6 flex items-center gap-2" aria-label="Progres pembayaran">
                            <span class="h-1.5 flex-1 rounded-full bg-blue-900"></span>
                            <span class="h-1.5 flex-1 rounded-full bg-blue-900"></span>
                        </div>

                        <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 sm:px-5">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 ring-1 ring-amber-100">
                                    <i data-feather="clock" class="h-5 w-5" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Selesaikan sebelum batas waktu</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $booking->expires_at?->format('d M Y, H:i') ?? '-' }} WIB</p>
                                </div>
                            </div>
                            <div id="payment-countdown"
                                 data-expiry="{{ $expiryIso }}"
                                 class="font-mono text-2xl font-bold tracking-tight text-slate-900 tabular-nums">
                                -- : -- : --
                            </div>
                        </div>

                        <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-blue-50/70 px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 min-w-12 items-center justify-center rounded-lg border border-blue-100 bg-white px-2 text-[11px] font-bold text-blue-950">
                                        {{ $isQris ? 'QRIS' : ($isVa ? strtoupper($method['bank']) : $methodName) }}
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $methodName }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">Metode pembayaran yang dipilih</p>
                                    </div>
                                </div>
                                <a href="{{ route('tenant.bookings.payments.create', $booking->booking_code) }}"
                                   class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-900 transition hover:text-blue-700">
                                    <i data-feather="refresh-cw" class="h-4 w-4" aria-hidden="true"></i>
                                    Ubah metode
                                </a>
                            </div>

                            @if ($isVa)
                                <div class="p-5 sm:p-6">
                                    <p class="text-xs font-medium text-slate-500">Nomor Virtual Account simulasi</p>
                                    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                                        <p id="payment-reference" class="break-all font-mono text-2xl font-bold tracking-wide text-slate-900 sm:text-3xl">{{ $reference }}</p>
                                        <button type="button"
                                                data-copy="{{ $reference }}"
                                                class="copy-button inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-900">
                                            <i data-feather="copy" class="h-4 w-4" aria-hidden="true"></i>
                                            Salin
                                        </button>
                                    </div>
                                </div>
                            @elseif ($isQris)
                                <div class="flex flex-col items-center gap-5 p-5 sm:flex-row sm:p-6">
                                    <div class="flex h-48 w-48 shrink-0 flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                        <div class="grid h-28 w-28 grid-cols-7 gap-0.5 opacity-80" aria-hidden="true">
                                            @for ($i = 0; $i < 49; $i++)
                                                <span class="{{ in_array($i, [0,1,2,7,9,14,15,16,6,12,13,20,28,29,30,34,35,36,42,43,44,48,40,41,46,22,24,26,32,38]) ? 'bg-blue-950' : 'bg-slate-100' }}"></span>
                                            @endfor
                                        </div>
                                        <span class="mt-2 text-[10px] font-bold tracking-[0.18em] text-blue-950">QRIS DEMO</span>
                                    </div>
                                    <div class="text-center sm:text-left">
                                        <p class="text-base font-semibold text-slate-900">QRIS simulasi</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-500">
                                            Kode QR ini hanya ilustrasi untuk demonstrasi tampilan. Kode tidak dapat dipindai dan bukan kode pembayaran aktif.
                                        </p>
                                        <p class="mt-3 break-all text-xs text-slate-400">Referensi: {{ $reference }}</p>
                                    </div>
                                </div>
                            @else
                                <div class="p-5 sm:p-6">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-900">
                                            <span aria-hidden="true" class="font-semibold">▯</span>
                                        </span>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">Pembayaran melalui {{ $methodName }}</p>
                                            <p class="mt-1 text-sm leading-6 text-slate-500">
                                                Pada integrasi gateway, kamu akan diarahkan ke aplikasi {{ $methodName }}.
                                                Untuk versi simulasi ini, gunakan tombol simulasi di bawah.
                                            </p>
                                            <p class="mt-2 break-all text-xs text-slate-400">Referensi simulasi: {{ $reference }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
                                <div>
                                    <p class="text-xs font-medium text-slate-500">Jumlah yang harus dibayar</p>
                                    <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Rp{{ number_format($amount, 0, ',', '.') }}</p>
                                </div>
                                <button type="button"
                                        data-copy="{{ number_format($amount, 0, ',', '.') }}"
                                        class="copy-button inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-900">
                                    <i data-feather="copy" class="h-4 w-4" aria-hidden="true"></i>
                                    Salin nominal
                                </button>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <form action="{{ route('tenant.bookings.payments.simulate', ['bookingCode' => $booking->booking_code, 'paymentCode' => $payment->payment_code]) }}" method="POST">
                                @csrf
                                <input type="hidden" name="outcome" value="success">
                                <button type="submit"
                                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 py-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                    <i data-feather="check" class="h-4 w-4" aria-hidden="true"></i>
                                    Simulasikan pembayaran berhasil
                                </button>
                            </form>

                            <form action="{{ route('tenant.bookings.payments.simulate', ['bookingCode' => $booking->booking_code, 'paymentCode' => $payment->payment_code]) }}" method="POST">
                                @csrf
                                <input type="hidden" name="outcome" value="failed">
                                <button type="submit"
                                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-4 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-100">
                                    <span aria-hidden="true" class="font-semibold">!</span>
                                    Simulasikan pembayaran gagal
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Pembatalan booking menggunakan mekanisme BookingController yang sudah ada. --}}
                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <button
                            type="button"
                            onclick="document.getElementById('cancelBookingModal').showModal()"
                            class="w-full rounded-xl border border-red-200 bg-white px-4 py-3 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-100">
                            Batalkan Booking
                        </button>
                    </div>

                    <dialog
                        id="cancelBookingModal"
                        aria-labelledby="cancelBookingModalTitle"
                        class="m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl border-0 bg-white p-0 text-left shadow-2xl backdrop:bg-slate-950/60">
                        <form action="{{ route('tenant.bookings.cancel', $booking->booking_code) }}" method="POST" class="p-6">
                            @csrf

                            <div class="flex items-start gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                                    <i data-feather="alert-triangle" class="h-6 w-6" aria-hidden="true"></i>
                                </div>
                                <div class="min-w-0">
                                    <h3 id="cancelBookingModalTitle" class="text-lg font-semibold text-slate-900">Konfirmasi Pembatalan Booking</h3>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">
                                        Apakah kamu yakin ingin membatalkan booking ini? Booking akan dibatalkan dan kamar kembali tersedia. Tindakan ini tidak dapat dibatalkan.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5">
                                <label for="cancellation_reason" class="mb-2 block text-sm font-medium text-slate-700">
                                    Alasan pembatalan <span class="font-normal text-slate-400">(opsional)</span>
                                </label>
                                <textarea
                                    id="cancellation_reason"
                                    name="cancellation_reason"
                                    rows="3"
                                    maxlength="1000"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"
                                    placeholder="Tuliskan alasan pembatalan jika ada...">{{ old('cancellation_reason') }}</textarea>
                                @error('cancellation_reason')
                                    <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <button
                                    type="button"
                                    onclick="document.getElementById('cancelBookingModal').close()"
                                    class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    Kembali
                                </button>
                                <button
                                    type="submit"
                                    class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-800">
                                    Ya, Batalkan Booking
                                </button>
                            </div>
                        </form>
                    </dialog>

                    <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex items-center gap-2">
                            <span aria-hidden="true" class="font-semibold">≡</span>
                            <h2 class="text-base font-semibold text-slate-900">Cara pembayaran</h2>
                        </div>

                        @if ($isVa)
                            <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm leading-6 text-slate-600">
                                <li>Buka aplikasi atau layanan perbankan {{ $bankName }}.</li>
                                <li>Pilih menu Virtual Account atau pembayaran VA.</li>
                                <li>Masukkan nomor VA simulasi yang ditampilkan di atas.</li>
                                <li>Periksa nominal pembayaran dan konfirmasi.</li>
                            </ol>
                        @elseif ($isQris)
                            <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm leading-6 text-slate-600">
                                <li>Pada pembayaran QRIS sungguhan, buka aplikasi bank atau e-wallet yang mendukung QRIS.</li>
                                <li>Pindai kode QR dari merchant.</li>
                                <li>Periksa nama merchant dan nominal sebelum mengonfirmasi.</li>
                                <li>QR pada halaman ini hanya ilustrasi dan tidak dapat dipindai.</li>
                            </ol>
                        @else
                            <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm leading-6 text-slate-600">
                                <li>Buka aplikasi {{ $methodName }}.</li>
                                <li>Pada integrasi gateway, ikuti permintaan pembayaran dari aplikasi.</li>
                                <li>Periksa nominal dan konfirmasi pembayaran.</li>
                                <li>Untuk simulasi ini, gunakan tombol pemeriksaan status di atas.</li>
                            </ol>
                        @endif
                    </div>

                    <p class="mt-4 text-center text-xs leading-5 text-slate-400">
                        SIMULASI PEMBAYARAN — nomor VA dan referensi di halaman ini bukan instrumen pembayaran sungguhan.
                    </p>
                </main>

                <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-24">
                    <div class="flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-900">
                            <i data-feather="file-text" class="h-5 w-5" aria-hidden="true"></i>
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
                            <span class="text-slate-500">Metode</span>
                            <strong class="text-right font-semibold text-slate-800">{{ $methodName }}</strong>
                        </div>
                    </div>

                    <div class="my-5 border-t border-dashed border-slate-200"></div>

                    <p class="text-sm font-medium text-slate-600">Total pembayaran</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Rp{{ number_format($amount, 0, ',', '.') }}</p>
                    <p class="mt-4 text-xs leading-5 text-slate-500">
                        Pembayaran mencakup seluruh periode sewa yang dipilih.
                    </p>
                </aside>
            </div>
        </div>
    </section>

    <script>
        (() => {
            const countdown = document.getElementById('payment-countdown');

            if (countdown?.dataset.expiry) {
                const expiry = new Date(countdown.dataset.expiry).getTime();

                const updateCountdown = () => {
                    const remaining = Math.max(0, expiry - Date.now());
                    const hours = Math.floor(remaining / 3600000);
                    const minutes = Math.floor((remaining % 3600000) / 60000);
                    const seconds = Math.floor((remaining % 60000) / 1000);

                    countdown.textContent = [hours, minutes, seconds]
                        .map(value => String(value).padStart(2, '0'))
                        .join(' : ');

                    if (remaining <= 0) {
                        countdown.textContent = '00 : 00 : 00';
                        countdown.classList.add('text-red-700');
                    }
                };

                updateCountdown();
                window.setInterval(updateCountdown, 1000);
            }

            document.querySelectorAll('.copy-button').forEach(button => {
                button.addEventListener('click', async () => {
                    const value = button.dataset.copy || '';

                    try {
                        await navigator.clipboard.writeText(value);
                        const original = button.innerHTML;
                        button.innerHTML = '<i data-feather="check" class="h-4 w-4" aria-hidden="true"></i> Tersalin';
                        if (window.feather) window.feather.replace();
                        window.setTimeout(() => {
                            button.innerHTML = original;
                            if (window.feather) window.feather.replace();
                        }, 1400);
                    } catch (error) {
                        window.prompt('Salin teks berikut:', value);
                    }
                });
            });

            if (window.feather) window.feather.replace();
        })();
    </script>
@endsection
