@if (session('roomStatusBlockers'))
    @php
        $transactionStatusLabels = [
            'pending_payment' => 'Menunggu pembayaran',
            'confirmed' => 'Dikonfirmasi',
            'expired' => 'Kedaluwarsa',
            'cancelled' => 'Dibatalkan',
            'scheduled' => 'Terjadwal',
            'active' => 'Berjalan',
            'completed' => 'Selesai',
        ];
    @endphp

    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-900" role="alert">
        <div class="flex items-start gap-3">
            <i data-feather="alert-circle" class="mt-0.5 h-5 w-5 shrink-0"></i>
            <div class="min-w-0 flex-1">
                <h3 class="font-semibold">Status kamar belum dapat diubah</h3>
                <p class="mt-1 text-sm text-red-800">
                    Kamar ini masih memiliki data booking atau penyewaan. Selesaikan atau batalkan transaksi terkait terlebih dahulu.
                </p>

                <div class="mt-4 space-y-3">
                    @foreach (session('roomStatusBlockers') as $transaction)
                        <article class="rounded-lg border border-red-200 bg-white p-3 text-slate-800">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="font-semibold">
                                    {{ $transaction['type'] }} · {{ $transaction['code'] }}
                                </div>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    {{ $transactionStatusLabels[$transaction['status']] ?? ucfirst(str_replace('_', ' ', $transaction['status'])) }}
                                </span>
                            </div>

                            <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs text-slate-500">Penyewa</dt>
                                    <dd class="font-medium">{{ $transaction['tenant_name'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Periode</dt>
                                    <dd class="font-medium">
                                        {{ \Illuminate\Support\Carbon::parse($transaction['start_date'])->translatedFormat('d M Y') }}
                                        – 
                                        {{ \Illuminate\Support\Carbon::parse($transaction['end_date'])->translatedFormat('d M Y') }}
                                    </dd>
                                </div>
                            </dl>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
