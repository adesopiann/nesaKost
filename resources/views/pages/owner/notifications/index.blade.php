@extends('components.layouts.owner.container')

@section('title', 'Notifikasi')

@section('main')
<div class="space-y-6">
    <section class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-medium text-blue-800">Pusat Informasi</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Notifikasi</h1>
            <p class="mt-1 text-sm text-slate-500">Pantau booking, pembayaran, dan aktivitas sewa dari seluruh cabangmu.</p>
        </div>
        @if(auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('owner.notifications.read-all') }}">
                @csrf @method('PATCH')
                <button class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Tandai semua dibaca</button>
            </form>
        @endif
    </section>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        @forelse($notifications as $notification)
            @php($data = $notification->data ?? [])
            <article class="flex gap-4 border-b border-slate-100 p-5 last:border-b-0 {{ $notification->read_at ? '' : 'bg-blue-50/50' }}">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $notification->read_at ? 'bg-slate-100 text-slate-500' : 'bg-blue-100 text-blue-800' }}">
                    <i data-feather="{{ $data['icon'] ?? 'bell' }}" class="h-5 w-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-sm font-semibold text-slate-900">{{ $data['title'] ?? 'Notifikasi' }}</h2>
                        @unless($notification->read_at)<span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">Baru</span>@endunless
                        @if(!empty($data['branch_name']))<span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $data['branch_name'] }}</span>@endif
                    </div>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $data['message'] ?? 'Ada aktivitas baru pada akunmu.' }}</p>
                    <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at?->translatedFormat('d F Y, H:i') }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-4">
                        <a href="{{ route('owner.notifications.open', $notification->id) }}" class="text-sm font-semibold text-blue-800 hover:underline">Lihat detail</a>
                        @unless($notification->read_at)
                            <form method="POST" action="{{ route('owner.notifications.read', $notification->id) }}">@csrf @method('PATCH')<button class="text-sm font-medium text-slate-500 hover:text-slate-800">Tandai sudah dibaca</button></form>
                        @endunless
                    </div>
                </div>
            </article>
        @empty
            <div class="px-6 py-16 text-center">
                <i data-feather="bell-off" class="mx-auto h-9 w-9 text-slate-300"></i>
                <h2 class="mt-3 text-sm font-semibold text-slate-700">Belum ada notifikasi</h2>
                <p class="mt-1 text-sm text-slate-500">Aktivitas penting dari seluruh cabang akan muncul di sini.</p>
            </div>
        @endforelse
    </section>
    <div>{{ $notifications->links() }}</div>
</div>
@endsection
