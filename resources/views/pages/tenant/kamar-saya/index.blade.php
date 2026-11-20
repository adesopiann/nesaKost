@extends('components.layouts.tenant.container')

@section('title', 'Kamar Saya')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-6 py-10">
        <header class="mb-8">
            <p class="mb-2 text-sm font-medium text-blue-900">Area Penyewa</p>
            <h1 class="text-2xl font-bold text-slate-900 md:text-3xl">Kamar Saya</h1>
            <p class="mt-2 text-sm text-slate-500">
                Kelola informasi kamar, fasilitas, lokasi, dan masa penyewaanmu.
            </p>
        </header>

        @php
            $sections = [
                [
                    'title' => 'Kamar Aktif',
                    'description' => 'Informasi kamar yang sedang kamu tempati saat ini.',
                    'rents' => $activeRents,
                    'empty' => 'Saat ini kamu belum memiliki kamar aktif.',
                    'badge' => 'Sedang ditempati',
                    'badgeClass' => 'bg-emerald-50 text-emerald-700',
                ],
                [
                    'title' => 'Pemesanan Mendatang',
                    'description' => 'Kamar yang sudah dibayar dan menunggu tanggal mulai sewa.',
                    'rents' => $upcomingRents,
                    'empty' => 'Belum ada pemesanan kamar mendatang.',
                    'badge' => 'Akan ditempati',
                    'badgeClass' => 'bg-blue-50 text-blue-700',
                ],
            ];
        @endphp

        @foreach ($sections as $section)
            <section class="mb-10">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $section['title'] }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $section['description'] }}</p>
                </div>

                @if ($section['rents']->isNotEmpty())
                    <div class="space-y-6">
                        @foreach ($section['rents'] as $rent)
                            @php
                                $room = $rent->room;
                                $branch = $room?->branchDorm;
                                $images = $room?->images ?? collect();
                                $primaryImage = $images->firstWhere('is_primary', true) ?? $images->first();
                                $galleryImages = $images->values();
                                $fallbackImage = $room?->image_path;
                                $mainImagePath = $primaryImage?->image_path ?: $fallbackImage;
                                $roomFacilities = $room?->facilities ?? collect();
                                $branchFacilities = $branch?->facilities ?? collect();
                                $description = trim((string) ($room?->description ?? ''));
                                $startDate = $rent->start_date;
                                $endDate = $rent->end_date;
                                $daysUntilStart = $startDate
                                    ? now()->startOfDay()->diffInDays($startDate->copy()->startOfDay(), false)
                                    : null;
                                $duration = $rent->booking?->duration_month;
                                $priceSnapshot = $rent->booking?->price_snapshot;
                            @endphp

                            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">
                                    <div class="bg-slate-100 p-3 sm:p-4">
                                        <div class="relative overflow-hidden rounded-xl bg-slate-200">
                                            @if ($mainImagePath)
                                                <img
                                                    id="room-main-image-{{ $rent->id }}"
                                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($mainImagePath) }}"
                                                    alt="Foto kamar {{ $room?->room_code ?? '' }}"
                                                    class="h-64 w-full object-cover sm:h-80"
                                                    loading="lazy"
                                                >
                                            @else
                                                <div class="flex h-64 items-center justify-center text-sm text-slate-500 sm:h-80">
                                                    Foto kamar belum tersedia
                                                </div>
                                            @endif
                                            <span class="absolute left-3 top-3 rounded-full px-3 py-1.5 text-xs font-semibold {{ $section['badgeClass'] }}">
                                                {{ $section['badge'] }}
                                            </span>
                                        </div>

                                        @if ($galleryImages->count() > 1)
                                            <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                                                @foreach ($galleryImages as $imageIndex => $image)
                                                    <button
                                                        type="button"
                                                        class="room-gallery-thumbnail shrink-0 overflow-hidden rounded-lg border-2 {{ $image->id === $primaryImage?->id ? 'border-blue-800' : 'border-transparent' }}"
                                                        data-target="room-main-image-{{ $rent->id }}"
                                                        data-image="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path) }}"
                                                        aria-label="Tampilkan foto kamar {{ $imageIndex + 1 }}"
                                                    >
                                                        <img
                                                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path) }}"
                                                            alt="Thumbnail kamar {{ $imageIndex + 1 }}"
                                                            class="h-16 w-20 object-cover"
                                                            loading="lazy"
                                                        >
                                                    </button>
                                                @endforeach
                                            </div>
                                            <p class="mt-2 text-xs text-slate-500">{{ $galleryImages->count() }} foto kamar · Pilih thumbnail untuk melihat foto lainnya.</p>
                                        @endif
                                    </div>

                                    <div class="p-5 sm:p-6">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm text-slate-500">{{ $branch?->name ?? 'Nama kost tidak tersedia' }}</p>
                                                <h3 class="mt-1 text-xl font-bold text-slate-900">Kamar {{ $room?->room_code ?? '-' }}</h3>
                                                <p class="mt-1 text-sm text-slate-500">Tipe {{ $room?->type ?? '-' }}</p>
                                            </div>
                                            <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $section['badgeClass'] }}">
                                                {{ $section['badge'] }}
                                            </span>
                                        </div>

                                        @if ($description !== '')
                                            <div class="mt-4">
                                                <h4 class="text-sm font-semibold text-slate-800">Deskripsi Kamar</h4>
                                                <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $description }}</p>
                                            </div>
                                        @endif

                                        <div class="mt-5 border-t border-slate-100 pt-4">
                                            <h4 class="mb-3 text-sm font-semibold text-slate-800">Fasilitas Kamar</h4>
                                            @if ($roomFacilities->isNotEmpty())
                                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                                    @foreach ($roomFacilities as $facility)
                                                        <div class="flex items-start gap-2 text-sm text-slate-600">
                                                            <i data-feather="check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600"></i>
                                                            <span>{{ $facility->facility_name }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-sm text-slate-400">Informasi fasilitas kamar belum tersedia.</p>
                                            @endif
                                        </div>

                                        <div class="mt-5 border-t border-slate-100 pt-4">
                                            <h4 class="mb-3 text-sm font-semibold text-slate-800">Fasilitas Umum Kost</h4>
                                            @if ($branchFacilities->isNotEmpty())
                                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                                    @foreach ($branchFacilities as $facility)
                                                        <div class="flex items-start gap-2 text-sm text-slate-600">
                                                            <i data-feather="check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-blue-700"></i>
                                                            <span>{{ $facility->facility_name }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-sm text-slate-400">Informasi fasilitas umum belum tersedia.</p>
                                            @endif
                                        </div>

                                        <div class="mt-5 border-t border-slate-100 pt-4">
                                            <h4 class="mb-2 text-sm font-semibold text-slate-800">Lokasi Kost</h4>
                                            <div class="flex items-start gap-2 text-sm leading-6 text-slate-600">
                                                <i data-feather="map-pin" class="mt-1 h-4 w-4 shrink-0 text-slate-400"></i>
                                                <span>{{ $branch?->address ?? 'Alamat belum tersedia' }}</span>
                                            </div>
                                            @if ($branch?->gender_type)
                                                <p class="mt-2 text-xs text-slate-500">
                                                    Tipe penghuni:
                                                    {{ $branch->gender_type === 'male' ? 'Putra' : ($branch->gender_type === 'female' ? 'Putri' : ucfirst($branch->gender_type)) }}
                                                </p>
                                            @endif
                                            @if ($branch?->latitude && $branch?->longitude)
                                                <a
                                                    href="https://www.google.com/maps?q={{ $branch->latitude }},{{ $branch->longitude }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-blue-900 hover:underline"
                                                >
                                                    Lihat lokasi di Google Maps
                                                    <i data-feather="external-link" class="h-4 w-4"></i>
                                                </a>
                                            @endif
                                        </div>

                                        <div class="mt-5 border-t border-slate-100 pt-4">
                                            <h4 class="mb-3 text-sm font-semibold text-slate-800">Informasi Penyewaan</h4>
                                            <div class="grid grid-cols-2 gap-x-4 gap-y-4">
                                                <div>
                                                    <p class="text-xs text-slate-400">Mulai sewa</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ $startDate?->translatedFormat('d M Y') ?? '-' }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-400">Akhir sewa</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ $endDate?->translatedFormat('d M Y') ?? '-' }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-400">Durasi sewa</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ $duration ? $duration . ' bulan' : '-' }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-400">Kode sewa</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ $rent->rent_code ?? '-' }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-400">Harga per bulan saat booking</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-800">
                                                        {{ $priceSnapshot !== null ? 'Rp' . number_format((float) $priceSnapshot, 0, ',', '.') : '-' }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-400">Total biaya sewa</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-800">Rp{{ number_format((float) $rent->total_price, 0, ',', '.') }}</p>
                                                </div>
                                            </div>

                                            @if ($rent->status === 'scheduled' && $daysUntilStart !== null)
                                                <div class="mt-4 rounded-lg bg-blue-50 px-4 py-3">
                                                    <p class="text-sm font-medium text-blue-900">
                                                        @if ($daysUntilStart > 0)
                                                            Masa sewa dimulai dalam {{ $daysUntilStart }} hari.
                                                        @elseif ($daysUntilStart === 0)
                                                            Masa sewa dimulai hari ini.
                                                        @else
                                                            Tanggal mulai sewa telah terlewati. Status sewa sedang menunggu sinkronisasi.
                                                        @endif
                                                    </p>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="mt-5 border-t border-slate-100 pt-4">
                                            <p class="text-xs text-slate-400">Kode booking</p>
                                            <p class="mt-1 text-sm font-medium text-slate-700">{{ $rent->booking?->booking_code ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                            <i data-feather="home" class="h-5 w-5"></i>
                        </div>
                        <p class="mt-3 text-sm font-medium text-slate-800">{{ $section['empty'] }}</p>
                        <p class="mt-1 text-sm text-slate-500">
                            @if ($section['title'] === 'Kamar Aktif')
                                Kamar akan ditampilkan di sini ketika masa sewa sudah dimulai.
                            @else
                                Kamar yang sudah berhasil dipesan akan ditampilkan di sini sebelum masa sewa dimulai.
                            @endif
                        </p>
                        <a href="{{ route('guest.home') }}#cari"
                            class="mt-4 inline-flex rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-800">
                            Cari Kost
                        </a>
                    </div>
                @endif
            </section>
        @endforeach

        <section>
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-slate-900">Riwayat Kamar</h2>
                <p class="mt-1 text-sm text-slate-500">Daftar penyewaan yang sudah selesai atau dibatalkan.</p>
            </div>

            @if ($historicalRents->isNotEmpty())
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kost / Kamar</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Periode Sewa</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kode Sewa</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Total Biaya</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($historicalRents as $rent)
                                    <tr>
                                        <td class="whitespace-nowrap px-5 py-4">
                                            <p class="text-sm font-medium text-slate-900">{{ $rent->room?->branchDorm?->name ?? 'Nama kost tidak tersedia' }}</p>
                                            <p class="mt-1 text-xs text-slate-500">Kamar {{ $rent->room?->room_code ?? '-' }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                            {{ $rent->start_date?->format('d/m/Y') ?? '-' }} – {{ $rent->end_date?->format('d/m/Y') ?? '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $rent->rent_code ?? '-' }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">Rp{{ number_format((float) $rent->total_price, 0, ',', '.') }}</td>
                                        <td class="whitespace-nowrap px-5 py-4">
                                            @if ($rent->status === 'completed')
                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Selesai</span>
                                            @else
                                                <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700">Dibatalkan</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-8 text-center text-sm text-slate-500">
                    Belum ada riwayat penyewaan.
                </div>
            @endif
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.room-gallery-thumbnail').forEach(function (button) {
        button.addEventListener('click', function () {
            const targetId = button.dataset.target;
            const imageUrl = button.dataset.image;
            const mainImage = document.getElementById(targetId);

            if (!mainImage || !imageUrl) return;

            mainImage.src = imageUrl;

            const group = button.parentElement;
            group.querySelectorAll('.room-gallery-thumbnail').forEach(function (thumbnail) {
                thumbnail.classList.remove('border-blue-800');
                thumbnail.classList.add('border-transparent');
            });

            button.classList.remove('border-transparent');
            button.classList.add('border-blue-800');
        });
    });
});
</script>
@endsection
