@extends('components.layouts.tenant.container')

@section('title', 'Informasi Sewa')

@section('content')
<div class="min-h-screen bg-slate-50 px-6 py-10">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8">
            <p class="text-sm font-medium text-blue-900">Area Penyewa</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 md:text-3xl">Informasi Sewa</h1>
            <p class="mt-2 text-sm text-slate-500">Pantau masa sewa, biaya, dan informasi perpanjangan kamar kamu.</p>
        </div>

        <section class="mb-10">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-slate-900">Sewa Saat Ini</h2>
                <p class="mt-1 text-sm text-slate-500">Penyewaan yang sedang berlangsung atau sudah dijadwalkan.</p>
            </div>

            @forelse ($currentRents as $rent)
                @php
                    $isActive = $rent->status === 'active';
                    $booking = $rent->booking;
                    $renewal = $rent->latest_renewal;
                    $detailId = 'current-rent-detail-' . $rent->id;
                @endphp
                <article class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
                        <div>
                            <p class="text-xs text-slate-500">Kode penyewaan</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ $rent->rent_code ?? '-' }}</p>
                        </div>
                        @if ($isActive)
                            <span class="rounded-full bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700">Sedang berlangsung</span>
                        @else
                            <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">Akan dimulai</span>
                        @endif
                    </div>

                    <div class="grid gap-6 p-6 lg:grid-cols-[1fr_1.2fr]">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Informasi kamar</p>
                            <h3 class="mt-2 text-xl font-bold text-slate-900">{{ $rent->room?->branchDorm?->name ?? 'Kost' }}</h3>
                            <p class="mt-1 text-sm text-slate-600">Kamar {{ $rent->room?->room_code ?? '-' }} · {{ $rent->room?->type ?? '-' }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ $rent->room?->branchDorm?->address ?? 'Alamat tidak tersedia' }}</p>
                        </div>

                        <div class="grid content-start grid-cols-2 gap-4 rounded-xl bg-slate-50 p-5">
                            <div>
                                <p class="text-xs text-slate-500">Tanggal mulai</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $rent->start_date?->translatedFormat('d M Y') ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Tanggal berakhir</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $rent->end_date?->translatedFormat('d M Y') ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Durasi</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $booking?->duration_month ?? '-' }} bulan</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Total biaya sewa</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">Rp{{ number_format((float) $rent->total_price, 0, ',', '.') }}</p>
                            </div>
                            <div class="col-span-2 border-t border-slate-200 pt-3">
                                @if ($isActive)
                                    <p class="text-sm font-semibold text-slate-900">
                                        @if ($rent->days_remaining > 0)
                                            Sisa {{ $rent->days_remaining }} hari masa sewa
                                        @elseif ($rent->days_remaining === 0)
                                            Masa sewa berakhir hari ini
                                        @else
                                            Masa sewa telah melewati tanggal akhir
                                        @endif
                                    </p>
                                @else
                                    <p class="text-sm font-semibold text-slate-900">
                                        @if ($rent->days_remaining > 0)
                                            Mulai dalam {{ $rent->days_remaining }} hari
                                        @elseif ($rent->days_remaining === 0)
                                            Masa sewa dimulai hari ini
                                        @else
                                            Tanggal mulai telah lewat, status menunggu pembaruan
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 px-6 py-4">
                        <button type="button"
                            class="flex w-full items-center justify-between gap-3 text-left text-sm font-semibold text-blue-900 hover:text-blue-700"
                            aria-expanded="false"
                            aria-controls="{{ $detailId }}"
                            data-accordion-toggle>
                            <span>Lihat Detail Sewa</span>
                            <i data-feather="chevron-down" class="h-5 w-5 transition-transform duration-200" data-accordion-icon></i>
                        </button>

                        <div id="{{ $detailId }}" class="hidden pt-5" data-accordion-panel>
                            <div class="grid gap-6 md:grid-cols-2">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">Identitas Penyewaan</h4>
                                    <dl class="mt-3 space-y-3 text-sm">
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Kode sewa</dt><dd class="font-medium text-slate-900">{{ $rent->rent_code ?? '-' }}</dd></div>
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Kode booking</dt><dd class="font-medium text-slate-900">{{ $booking?->booking_code ?? '-' }}</dd></div>
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Tipe booking</dt><dd class="font-medium text-slate-900">{{ $booking?->booking_type === 'renewal' ? 'Perpanjangan' : 'Booking baru' }}</dd></div>
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Kode kamar</dt><dd class="font-medium text-slate-900">{{ $rent->room?->room_code ?? '-' }}</dd></div>
                                    </dl>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">Rincian Biaya</h4>
                                    <dl class="mt-3 space-y-3 text-sm">
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Harga per bulan saat booking</dt><dd class="font-medium text-slate-900">Rp{{ number_format((float) ($booking?->price_snapshot ?? 0), 0, ',', '.') }}</dd></div>
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Durasi</dt><dd class="font-medium text-slate-900">{{ $booking?->duration_month ?? '-' }} bulan</dd></div>
                                        <div class="flex justify-between gap-4 border-t border-slate-100 pt-3"><dt class="font-semibold text-slate-700">Total biaya sewa</dt><dd class="font-bold text-slate-900">Rp{{ number_format((float) $rent->total_price, 0, ',', '.') }}</dd></div>
                                    </dl>
                                </div>
                            </div>

                            <div class="mt-6 border-t border-slate-100 pt-5">
                                <h4 class="text-sm font-semibold text-slate-900">Informasi Perpanjangan</h4>
                                @if ($renewal)
                                    <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                                        @if ($renewal->status === 'pending_payment')
                                            <p class="text-sm font-semibold text-amber-900">Menunggu pembayaran perpanjangan</p>
                                            <p class="mt-1 text-sm text-amber-800">Selesaikan pembayaran sebelum {{ $renewal->expires_at?->format('d M Y, H:i') ?? 'batas waktu berakhir' }}.</p>
                                            <a href="{{ route('tenant.bookings.show', $renewal) }}" class="mt-3 inline-flex text-sm font-semibold text-blue-900 underline">Lihat pengajuan dan pembayaran</a>
                                        @elseif ($renewal->status === 'confirmed' && $renewal->rent)
                                            <p class="text-sm font-semibold text-green-800">Perpanjangan berhasil dikonfirmasi</p>
                                            <p class="mt-1 text-sm text-green-800">Periode baru: {{ $renewal->rent->start_date?->format('d M Y') }} – {{ $renewal->rent->end_date?->format('d M Y') }}.</p>
                                        @else
                                            <p class="text-sm font-semibold text-amber-900">Kesempatan perpanjangan sudah digunakan</p>
                                            <p class="mt-1 text-sm text-amber-800">Status pengajuan: {{ ucfirst(str_replace('_', ' ', $renewal->status)) }}. Pengajuan baru untuk masa sewa ini tidak tersedia.</p>
                                        @endif
                                    </div>
                                @elseif ($rent->renewal_eligibility['eligible'])
                                    <div class="mt-3 flex flex-col justify-between gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4 sm:flex-row sm:items-center">
                                        <div>
                                            <p class="text-sm font-semibold text-blue-950">Periode perpanjangan tersedia</p>
                                            <p class="mt-1 text-sm text-blue-900">{{ $rent->renewal_eligibility['message'] }} Pembayaran harus diselesaikan dalam 72 jam setelah pengajuan.</p>
                                        </div>
                                        <a href="{{ route('tenant.rents.renewal.create', $rent) }}" class="inline-flex shrink-0 justify-center rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-950">Ajukan Perpanjangan</a>
                                    </div>
                                @else
                                    <p class="mt-2 text-sm text-slate-600">{{ $rent->renewal_eligibility['message'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
                    <p class="text-sm font-medium text-slate-800">Belum ada penyewaan saat ini.</p>
                    <p class="mt-1 text-sm text-slate-500">Penyewaan yang sudah dibayar akan muncul di halaman ini.</p>
                </div>
            @endforelse
        </section>

        <section>
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-slate-900">Riwayat Sewa</h2>
                <p class="mt-1 text-sm text-slate-500">Daftar penyewaan yang telah selesai atau dibatalkan.</p>
            </div>
            @forelse ($historicalRents as $rent)
                @php
                    $booking = $rent->booking;
                    $renewal = $rent->latest_renewal;
                    $detailId = 'history-rent-detail-' . $rent->id;
                @endphp
                <article class="mb-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4 p-5">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900">{{ $rent->rent_code ?? '-' }}</p>
                            <p class="mt-1 text-sm text-slate-700">{{ $rent->room?->branchDorm?->name ?? '-' }} · Kamar {{ $rent->room?->room_code ?? '-' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $rent->start_date?->format('d M Y') }} – {{ $rent->end_date?->format('d M Y') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            @if ($rent->status === 'completed')
                                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">Selesai</span>
                            @else
                                <span class="rounded-full bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700">Dibatalkan</span>
                            @endif
                            <span class="text-sm font-semibold text-slate-900">Rp{{ number_format((float) $rent->total_price, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="border-t border-slate-100 px-5 py-4">
                        <button type="button"
                            class="flex w-full items-center justify-between gap-3 text-left text-sm font-semibold text-blue-900 hover:text-blue-700"
                            aria-expanded="false"
                            aria-controls="{{ $detailId }}"
                            data-accordion-toggle>
                            <span>Lihat Detail Sewa</span>
                            <i data-feather="chevron-down" class="h-5 w-5 transition-transform duration-200" data-accordion-icon></i>
                        </button>
                        <div id="{{ $detailId }}" class="hidden pt-5" data-accordion-panel>
                            <div class="grid gap-6 md:grid-cols-2">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">Identitas Penyewaan</h4>
                                    <dl class="mt-3 space-y-3 text-sm">
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Kode sewa</dt><dd class="font-medium text-slate-900">{{ $rent->rent_code ?? '-' }}</dd></div>
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Kode booking</dt><dd class="font-medium text-slate-900">{{ $booking?->booking_code ?? '-' }}</dd></div>
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Tipe booking</dt><dd class="font-medium text-slate-900">{{ $booking?->booking_type === 'renewal' ? 'Perpanjangan' : 'Booking baru' }}</dd></div>
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Kost / kamar</dt><dd class="font-medium text-slate-900">{{ $rent->room?->branchDorm?->name ?? '-' }} / {{ $rent->room?->room_code ?? '-' }}</dd></div>
                                    </dl>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">Rincian Biaya</h4>
                                    <dl class="mt-3 space-y-3 text-sm">
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Harga per bulan saat booking</dt><dd class="font-medium text-slate-900">Rp{{ number_format((float) ($booking?->price_snapshot ?? 0), 0, ',', '.') }}</dd></div>
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Durasi</dt><dd class="font-medium text-slate-900">{{ $booking?->duration_month ?? '-' }} bulan</dd></div>
                                        <div class="flex justify-between gap-4 border-t border-slate-100 pt-3"><dt class="font-semibold text-slate-700">Total biaya sewa</dt><dd class="font-bold text-slate-900">Rp{{ number_format((float) $rent->total_price, 0, ',', '.') }}</dd></div>
                                    </dl>
                                </div>
                            </div>
                            @if ($renewal)
                                <div class="mt-6 border-t border-slate-100 pt-5">
                                    <h4 class="text-sm font-semibold text-slate-900">Riwayat Perpanjangan</h4>
                                    <p class="mt-2 text-sm text-slate-600">Pengajuan renewal: {{ ucfirst(str_replace('_', ' ', $renewal->status)) }}</p>
                                    @if ($renewal->rent)
                                        <p class="mt-1 text-sm text-slate-600">Periode baru: {{ $renewal->rent->start_date?->format('d M Y') }} – {{ $renewal->rent->end_date?->format('d M Y') }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-8 text-center text-sm text-slate-500">Belum ada riwayat sewa.</div>
            @endforelse
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-accordion-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const panelId = button.getAttribute('aria-controls');
            const panel = document.getElementById(panelId);
            const icon = button.querySelector('[data-accordion-icon]');
            const isExpanded = button.getAttribute('aria-expanded') === 'true';

            button.setAttribute('aria-expanded', String(!isExpanded));
            panel.classList.toggle('hidden', isExpanded);

            if (icon) {
                icon.classList.toggle('rotate-180', !isExpanded);
            }

            const label = button.querySelector('span');
            if (label) {
                label.textContent = isExpanded ? 'Lihat Detail Sewa' : 'Tutup Detail Sewa';
            }
        });
    });
});
</script>
@endsection
