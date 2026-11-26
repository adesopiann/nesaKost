@extends('components.layouts.tenant.container')

@section('title', 'Perpanjangan Sewa')

@section('content')
<div class="min-h-screen bg-slate-50 px-6 py-10">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('tenant.dashboard') }}" class="text-sm font-medium text-blue-900 hover:underline">&larr; Kembali ke dashboard</a>

        <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
            <p class="text-sm font-semibold text-blue-900">Perpanjangan masa sewa</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Perpanjang kamar kamu</h1>
            <p class="mt-2 text-sm text-slate-600">Kamu dapat melanjutkan masa sewa kamar yang sama tanpa mengajukan booking baru.</p>

            <div class="mt-6 grid gap-4 rounded-xl bg-slate-50 p-5 sm:grid-cols-2">
                <div><p class="text-xs text-slate-500">Kost</p><p class="mt-1 font-semibold text-slate-900">{{ $rent->room?->branchDorm?->name ?? '-' }}</p></div>
                <div><p class="text-xs text-slate-500">Kamar</p><p class="mt-1 font-semibold text-slate-900">{{ $rent->room?->room_code ?? '-' }}</p></div>
                <div><p class="text-xs text-slate-500">Masa sewa saat ini berakhir</p><p class="mt-1 font-semibold text-slate-900">{{ $rent->end_date->format('d M Y') }}</p></div>
                <div><p class="text-xs text-slate-500">Mulai perpanjangan</p><p class="mt-1 font-semibold text-slate-900">{{ $rent->end_date->copy()->addDay()->format('d M Y') }}</p></div>
                <div><p class="text-xs text-slate-500">Harga per bulan saat ini</p><p class="mt-1 font-semibold text-slate-900">Rp{{ number_format((float) $rent->room->monthly_price, 0, ',', '.') }}</p></div>
            </div>

            @if($existingRenewal)
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5">
                    <p class="font-semibold text-amber-900">Kesempatan renewal sudah digunakan</p>
                    @if($existingRenewal->status === 'confirmed')
                        <p class="mt-1 text-sm text-amber-800">Pengajuan perpanjangan sebelumnya sudah berhasil dikonfirmasi.</p>
                        <a href="{{ route('tenant.bookings.show', $existingRenewal) }}" class="mt-3 inline-flex text-sm font-semibold text-blue-900 underline">Lihat pengajuan</a>
                    @elseif($existingRenewal->status === 'pending_payment')
                        <p class="mt-1 text-sm text-amber-800">Kamu masih memiliki pengajuan perpanjangan yang menunggu pembayaran.</p>
                        <a href="{{ route('tenant.bookings.show', $existingRenewal) }}" class="mt-3 inline-flex text-sm font-semibold text-blue-900 underline">Lihat pengajuan</a>
                    @else
                        <p class="mt-1 text-sm text-amber-800">Pengajuan sebelumnya berstatus {{ $existingRenewal->status }}. Kamu tidak dapat mengajukan renewal lagi untuk masa sewa ini. Jika masih ingin menyewa, silakan lakukan booking reguler melalui halaman utama setelah periode sewa saat ini berakhir.</p>
                    @endif
                </div>
            @elseif(!$eligibility['eligible'])
                <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <p class="font-semibold text-slate-900">Belum dapat mengajukan perpanjangan</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $eligibility['message'] }}</p>
                </div>
            @else
                <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50 p-5">
                    <p class="font-semibold text-blue-950">Periode pengajuan tersedia</p>
                    <p class="mt-1 text-sm text-blue-900">Pengajuan dibuka sampai {{ $eligibility['closes_at']->format('d M Y') }}. Setelah pengajuan dibuat, kamu memiliki waktu 72 jam untuk menyelesaikan pembayaran.</p>
                </div>

                <form method="POST" action="{{ route('tenant.rents.renewal.store', $rent) }}" class="mt-6 space-y-5">
                    @csrf
                    <div>
                        <label for="duration_month" class="mb-2 block text-sm font-semibold text-slate-800">Durasi perpanjangan</label>
                        <select id="duration_month" name="duration_month" required class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-blue-900 focus:outline-none focus:ring-1 focus:ring-blue-900">
                            <option value="1" @selected(old('duration_month') == 1)>1 bulan</option>
                            <option value="3" @selected(old('duration_month', 3) == 3)>3 bulan</option>
                            <option value="6" @selected(old('duration_month') == 6)>6 bulan</option>
                        </select>
                        @error('duration_month')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="rounded-lg border border-slate-200 p-4 text-sm text-slate-600">
                        <p class="font-semibold text-slate-800">Perlu diperhatikan</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            <li>Perpanjangan berlaku untuk kamar yang sama.</li>
                            <li>Pembayaran dilakukan penuh di awal periode.</li>
                            <li>Kamar ditahan selama 72 jam sejak pengajuan.</li>
                            <li>Jika pembayaran tidak selesai dalam 72 jam, pengajuan otomatis kedaluwarsa.</li>
                        </ul>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-900 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-950">Ajukan Perpanjangan</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
