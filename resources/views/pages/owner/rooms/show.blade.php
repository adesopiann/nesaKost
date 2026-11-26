@extends('components.layouts.owner.container')
@section('main')
<div class="space-y-6 text-slate-800">
    @include('pages.owner.rooms.partials.flash')
    <section class="rounded-xl bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500"><a href="{{ route('owner.rooms.index') }}" class="hover:text-blue-800">Data Kamar</a> / <span class="font-semibold text-blue-800">Detail Kamar</span></p>
        <div class="mt-3 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-3xl font-bold">{{ $room->room_code }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <span class="font-semibold text-blue-800">{{ $room->type }}</span><span>•</span>
                    <span>{{ $room->branch?->name ?? 'Cabang tidak ditemukan' }}</span><span>•</span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold">{{ ucfirst($room->status) }}</span>
                </div>
            </div>
            <a href="{{ route('owner.rooms.edit', $room) }}" class="rounded-lg bg-blue-900 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-800">Edit Kamar</a>
        </div>
    </section>
    <section class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 rounded-xl bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="text-lg font-bold">Informasi Kamar</h2>
            @if($room->images->isNotEmpty())
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach($room->images as $image)
                        <div class="relative">
                            <img src="{{ asset('storage/'.$image->image_path) }}" alt="Foto kamar {{ $room->room_code }}" class="h-44 w-full rounded-xl object-cover">
                            @if($image->is_primary)
                                <span class="absolute left-2 top-2 rounded-full bg-blue-900 px-2 py-1 text-xs font-semibold text-white">Foto utama</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @elseif($room->image_path)
                <img src="{{ asset('storage/'.$room->image_path) }}" alt="Foto kamar {{ $room->room_code }}" class="max-h-[420px] w-full rounded-xl object-cover">
                <p class="text-xs text-slate-500">Foto ini berasal dari data lama. Unggah foto baru melalui menu Edit untuk mulai menggunakan galeri.</p>
            @else
                <div class="flex h-56 items-center justify-center rounded-xl bg-slate-100 text-sm text-slate-400">Foto kamar belum tersedia</div>
            @endif
            <div class="grid gap-5 sm:grid-cols-2">
                <div><p class="text-sm font-semibold text-slate-500">Kode kamar</p><p class="mt-2 font-medium">{{ $room->room_code }}</p></div>
                <div><p class="text-sm font-semibold text-slate-500">Cabang</p><p class="mt-2 font-medium">{{ $room->branch?->name ?? '—' }}</p></div>
                <div><p class="text-sm font-semibold text-slate-500">Tipe kamar</p><p class="mt-2 font-medium">{{ $room->type }}</p></div>
                <div><p class="text-sm font-semibold text-slate-500">Harga sewa per bulan</p><p class="mt-2 text-xl font-bold">Rp {{ number_format((float) $room->monthly_price, 0, ',', '.') }}</p></div>
                <div class="sm:col-span-2"><p class="text-sm font-semibold text-slate-500">Deskripsi</p><p class="mt-2 whitespace-pre-line leading-relaxed">{{ $room->description ?: 'Belum ada deskripsi.' }}</p></div>
            </div>
            <div class="border-t border-slate-100 pt-5">
                <h3 class="text-sm font-semibold text-slate-500">Fasilitas Kamar</h3>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($room->facilities as $facility)
                        <span class="rounded-lg bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-900">{{ $facility->facility_name }}</span>
                    @empty
                        <p class="text-sm text-slate-400">Belum ada fasilitas yang terhubung ke kamar ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <aside class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold">Ringkasan</h2>
            <dl class="mt-4 space-y-4 text-sm">
                <div><dt class="text-slate-500">ID kamar</dt><dd class="mt-1 font-semibold">{{ $room->id }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd class="mt-1 font-semibold">{{ ucfirst($room->status) }}</dd></div>
                <div><dt class="text-slate-500">Dibuat</dt><dd class="mt-1 font-semibold">{{ $room->created_at?->format('d M Y, H:i') ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Terakhir diperbarui</dt><dd class="mt-1 font-semibold">{{ $room->updated_at?->format('d M Y, H:i') ?? '—' }}</dd></div>
            </dl>
            <a href="{{ route('owner.rooms.index') }}" class="mt-6 inline-flex w-full justify-center rounded-lg bg-slate-100 px-4 py-3 text-sm font-semibold hover:bg-slate-200">Kembali ke daftar</a>
        </aside>
    </section>

    <section class="space-y-4 rounded-xl bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold">Transaksi Berlangsung</h2>
                <p class="mt-1 text-sm text-slate-500">Booking yang menunggu pembayaran dan masa sewa yang sedang berjalan atau terjadwal.</p>
            </div>
            <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-800">
                {{ $currentTransactions->count() }} transaksi
            </span>
        </div>

        @if ($currentTransactions->isNotEmpty())
            <div class="space-y-3">
                @foreach ($currentTransactions as $transaction)
                    @php
                        $statusLabels = [
                            'pending_payment' => 'Menunggu pembayaran',
                            'active' => 'Berjalan',
                            'scheduled' => 'Terjadwal',
                            'confirmed' => 'Dikonfirmasi',
                        ];
                        $statusClasses = [
                            'pending_payment' => 'bg-amber-100 text-amber-800',
                            'active' => 'bg-emerald-100 text-emerald-800',
                            'scheduled' => 'bg-blue-100 text-blue-800',
                            'confirmed' => 'bg-slate-100 text-slate-700',
                        ];
                    @endphp
                    <article class="rounded-xl border border-slate-200 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-bold text-slate-900">{{ $transaction['type'] }} · {{ $transaction['code'] }}</span>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$transaction['status']] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ $statusLabels[$transaction['status']] ?? ucfirst($transaction['status']) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm font-semibold">{{ $transaction['tenant_name'] }}</p>
                            </div>
                            <div class="text-sm sm:text-right">
                                <p class="text-xs text-slate-500">Periode</p>
                                <p class="mt-1 font-medium">
                                    {{ \Carbon\Carbon::parse($transaction['start_date'])->translatedFormat('d M Y') }}
                                    – {{ \Carbon\Carbon::parse($transaction['end_date'])->translatedFormat('d M Y') }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 border-t border-slate-100 pt-3 sm:grid-cols-3">
                            @if ($transaction['payment_code'])
                                <div>
                                    <p class="text-xs text-slate-500">Pembayaran</p>
                                    <p class="mt-1 text-sm font-medium">{{ $transaction['payment_code'] }}</p>
                                </div>
                            @endif
                            @if ($transaction['payment_status'])
                                <div>
                                    <p class="text-xs text-slate-500">Status pembayaran</p>
                                    <p class="mt-1 text-sm font-medium">{{ ucfirst($transaction['payment_status']) }}</p>
                                </div>
                            @endif
                            @if ($transaction['payment_method'])
                                <div>
                                    <p class="text-xs text-slate-500">Metode pembayaran</p>
                                    <p class="mt-1 text-sm font-medium">{{ str_replace('_', ' ', ucfirst($transaction['payment_method'])) }}</p>
                                </div>
                            @endif
                            @if ($transaction['payment_amount'] !== null)
                                <div>
                                    <p class="text-xs text-slate-500">Nominal pembayaran</p>
                                    <p class="mt-1 text-sm font-semibold">Rp {{ number_format((float) $transaction['payment_amount'], 0, ',', '.') }}</p>
                                </div>
                            @endif
                            @if ($transaction['expires_at'])
                                <div>
                                    <p class="text-xs text-slate-500">Batas pembayaran</p>
                                    <p class="mt-1 text-sm font-medium">{{ \Carbon\Carbon::parse($transaction['expires_at'])->translatedFormat('d M Y, H:i') }}</p>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-xl bg-slate-50 px-4 py-8 text-center">
                <p class="font-medium text-slate-700">Tidak ada transaksi yang sedang berlangsung.</p>
                <p class="mt-1 text-sm text-slate-500">Booking pending dan sewa aktif/terjadwal akan muncul di bagian ini.</p>
            </div>
        @endif
    </section>

    <details class="group rounded-xl bg-white p-6 shadow-sm">
        <summary class="flex cursor-pointer list-none flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold">Riwayat Transaksi</h2>
                <p class="mt-1 text-sm text-slate-500">Transaksi selesai, dibatalkan, kedaluwarsa, atau yang periodenya sudah berakhir.</p>
            </div>
            <span class="flex items-center gap-2">
                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">{{ $transactionHistory->count() }} riwayat</span>
                <span class="text-sm text-blue-800 group-open:hidden">Lihat riwayat</span>
                <span class="hidden text-sm text-blue-800 group-open:inline">Tutup riwayat</span>
            </span>
        </summary>

        <div class="mt-5">
            @if ($transactionHistory->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Transaksi</th>
                                <th class="px-4 py-3 font-semibold">Penyewa</th>
                                <th class="px-4 py-3 font-semibold">Periode</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 font-semibold">Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($transactionHistory as $transaction)
                                @php
                                    $historyStatusLabels = [
                                        'expired' => 'Kedaluwarsa',
                                        'cancelled' => 'Dibatalkan',
                                        'completed' => 'Selesai',
                                        'active' => 'Periode berakhir',
                                        'scheduled' => 'Periode berakhir',
                                        'confirmed' => 'Periode berakhir',
                                    ];
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold">{{ $transaction['type'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $transaction['code'] }}</p>
                                    </td>
                                    <td class="px-4 py-3">{{ $transaction['tenant_name'] }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($transaction['start_date'])->translatedFormat('d M Y') }}
                                        – {{ \Carbon\Carbon::parse($transaction['end_date'])->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                            {{ $historyStatusLabels[$transaction['status']] ?? ucfirst($transaction['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($transaction['payment_status'])
                                            <span class="text-sm">{{ ucfirst($transaction['payment_status']) }}</span>
                                            @if ($transaction['payment_code'])
                                                <p class="text-xs text-slate-500">{{ $transaction['payment_code'] }}</p>
                                            @endif
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    Belum ada riwayat transaksi untuk kamar ini.
                </div>
            @endif
        </div>
    </details>
</div>
@endsection
