@php
    $statusLabels = [
        'pending' => 'Menunggu',
        'paid' => 'Berhasil',
        'failed' => 'Gagal',
        'expired' => 'Kedaluwarsa',
        'cancelled' => 'Dibatalkan',
    ];
    $statusClasses = [
        'pending' => 'bg-amber-50 text-amber-700',
        'paid' => 'bg-green-50 text-green-700',
        'failed' => 'bg-red-50 text-red-700',
        'expired' => 'bg-slate-100 text-slate-600',
        'cancelled' => 'bg-slate-100 text-slate-600',
    ];
    $methodLabels = [
        'bank_transfer' => 'Transfer Bank',
        'bank_transfer_va' => 'Virtual Account',
        'virtual_account' => 'Virtual Account',
        'qris' => 'QRIS',
        'ewallet' => 'E-Wallet',
    ];
@endphp

<div class="overflow-x-auto">
    <table class="w-full min-w-[760px] text-left text-sm">
        <thead class="bg-slate-50 text-xs text-slate-500">
            <tr>
                <th class="px-5 py-4 font-medium">Kode Pembayaran</th>
                <th class="px-5 py-4 font-medium">Tanggal</th>
                <th class="px-5 py-4 font-medium">Metode</th>
                <th class="px-5 py-4 font-medium">Jumlah</th>
                <th class="px-5 py-4 font-medium">Status</th>
                <th class="px-5 py-4 text-right font-medium">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($payments as $payment)
                @php
                    $transactionDate = $payment->status === 'paid'
                        ? $payment->paid_at
                        : $payment->created_at;
                    $method = $payment->payment_method;
                @endphp
                <tr class="hover:bg-slate-50/70">
                    <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-800">
                        {{ $payment->payment_code ?? 'PAY-' . $payment->id }}
                    </td>
                    <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                        {{ $transactionDate?->translatedFormat('d M Y') ?? '-' }}
                    </td>
                    <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                        {{ $methodLabels[$method] ?? ($method ? str($method)->replace('_', ' ')->title() : '-') }}
                    </td>
                    <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-800">
                        Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}
                    </td>
                    <td class="whitespace-nowrap px-5 py-4">
                        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses[$payment->status] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ $statusLabels[$payment->status] ?? ucfirst($payment->status) }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-5 py-4 text-right">
                        <a href="{{ route('tenant.riwayat-pembayaran.show', $payment->payment_code) }}"
                           class="font-medium text-blue-900 hover:underline">
                            Detail <span aria-hidden="true">→</span>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
