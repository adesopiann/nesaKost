@extends('components.layouts.tenant.container')

@section('title', 'Notifikasi - nesaKost')

@section('content')
<section class="min-h-[70vh] bg-slate-50 px-4 py-10 sm:px-6">
    <div class="mx-auto max-w-4xl">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-blue-900">Pusat Informasi</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Notifikasi</h1>
                <p class="mt-2 text-sm text-slate-500">Informasi terbaru terkait masa sewa dan aktivitas akun Anda.</p>
            </div>
            @if(auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('tenant.notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <button class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                        Tandai semua dibaca
                    </button>
                </form>
            @endif
        </div>

        @if(session('success'))
            <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            @forelse($notifications as $notification)
                @php($data = $notification->data ?? [])
                <div class="flex gap-4 border-b border-slate-100 p-5 last:border-b-0 {{ $notification->read_at ? '' : 'bg-blue-50/50' }}">
                    <div class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $notification->read_at ? 'bg-slate-100 text-slate-500' : 'bg-blue-100 text-blue-900' }}">
                        <i data-feather="bell" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-semibold text-slate-900">{{ $data['title'] ?? 'Notifikasi' }}</h2>
                            @unless($notification->read_at)
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-900">Baru</span>
                            @endunless
                        </div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $data['message'] ?? 'Anda memiliki notifikasi baru.' }}</p>
                        <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at?->translatedFormat('d F Y, H:i') }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-4">
                            <a href="{{ route('tenant.notifications.open', $notification->id) }}" class="text-sm font-semibold text-blue-900 hover:underline">Lihat detail</a>
                            @unless($notification->read_at)
                                <form method="POST" action="{{ route('tenant.notifications.read', $notification->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-sm text-slate-500 hover:text-slate-800">Tandai dibaca</button>
                                </form>
                            @endunless
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400"><i data-feather="bell-off" class="h-6 w-6"></i></div>
                    <h2 class="mt-4 text-base font-semibold text-slate-800">Belum ada notifikasi</h2>
                    <p class="mt-1 text-sm text-slate-500">Notifikasi penting akan muncul di halaman ini.</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="mt-6">{{ $notifications->links() }}</div>
        @endif
    </div>
</section>
@endsection
