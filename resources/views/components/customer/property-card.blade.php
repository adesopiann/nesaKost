@props(['property'])

<div class="group overflow-hidden rounded-xl border border-slate-200 bg-white transition duration-300 hover:-translate-y-1 hover:shadow-lg">
    <a href="{{ route('customer.properties.show', $property->id) }}">
        <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
            @if($property->image_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($property->image_path) }}"
                     alt="{{ $property->name }}"
                     loading="lazy"
                     class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <div class="flex h-full items-center justify-center text-sm text-slate-400">Foto kost belum tersedia</div>
            @endif

            <span class="absolute left-3 top-3 rounded-md bg-white px-3 py-1 text-xs font-medium capitalize text-slate-700 shadow-sm">
                {{ $property->gender_type }}
            </span>
        </div>

        <div class="p-4">
            <div class="mb-2 flex items-start justify-between gap-2">
                <h3 class="font-semibold text-slate-800 transition group-hover:text-blue-900">{{ $property->name }}</h3>
                <span class="shrink-0 rounded-full bg-green-50 px-2 py-1 text-xs text-green-700">Tersedia</span>
            </div>

            <p class="mb-3 line-clamp-2 text-sm text-slate-500">{{ $property->address }}</p>

            <div class="mb-4 text-xs text-slate-500">
                {{ number_format((float) $property->distance, 2, ',', '.') }} km dari kampus
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                <div>
                    <p class="text-xs text-slate-400">Mulai dari</p>
                    <p class="font-semibold text-blue-900">
                        Rp{{ number_format((float) $property->starting_price, 0, ',', '.') }}
                        <span class="text-xs font-normal text-slate-500">/ bulan</span>
                    </p>
                </div>
                <span class="text-sm font-medium text-blue-900">Lihat detail →</span>
            </div>
        </div>
    </a>
</div>
