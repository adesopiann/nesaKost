@extends('components.layouts.owner.container')

@section('title', 'Dashboard')

@section('main')
    @php
        $money = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
        $branchColors = ['#2450D8', '#12A5E5', '#10B981', '#F59E0B', '#8B5CF6', '#EF4444', '#64748B'];
        $maxMethodCount = max(1, (int) ($paymentMethodTotals->max('transaction_count') ?? 1));
    @endphp

    <div class="space-y-6">
        <section class="rounded-xl border border-slate-100 bg-white px-5 py-5 sm:px-6">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Ringkasan operasional pengelolaan kos.</p>
        </section>

        <section class="flex flex-col gap-3 rounded-xl border border-slate-100 bg-white p-3 sm:flex-row sm:items-center">
            <div class="flex flex-wrap gap-2">
                <button type="button" data-branch-filter="all"
                    class="branch-filter rounded-lg bg-[#1D4E80] px-3 py-2 text-xs font-semibold text-white">Semua
                    Cabang</button>
                @foreach ($branchOccupancy as $branch)
                    <button type="button" data-branch-filter="{{ $branch['id'] }}"
                        class="branch-filter rounded-lg bg-[#F0F3FF] px-3 py-2 text-xs font-medium text-slate-600 hover:bg-blue-100">
                        {{ $branch['name'] }}
                    </button>
                @endforeach
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @php
                $statCards = [
                    [
                        'label' => 'Total Cabang Kos',
                        'value' => $stats['branches'],
                        'entity' => 'cabang',
                        'icon' => 'grid',
                        'note' => 'Cabang terdaftar',
                    ],
                    [
                        'label' => 'Total Kamar',
                        'value' => $stats['rooms'],
                        'entity' => 'kamar',
                        'icon' => 'columns',
                        'note' => 'Kamar terdata',
                    ],
                    [
                        'label' => 'Total Penyewa',
                        'value' => $stats['tenants'],
                        'entity' => 'penyewa',
                        'icon' => 'user-check',
                        'note' => 'Penyewa terdaftar',
                    ],
                ];
            @endphp
            @foreach ($statCards as $card)
                <article class="rounded-xl border border-slate-100 bg-white p-5">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-[#315F96]">
                            <i data-feather="{{ $card['icon'] }}" class="h-5 w-5"></i>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span
                            class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($card['value']) }}</span>
                        <span class="text-xs font-medium text-slate-500">{{ $card['entity'] }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ $card['note'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="rounded-xl border border-slate-100 bg-white p-5 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="flex items-center gap-2 text-base font-semibold text-slate-800"><span
                            class="h-2 w-2 rounded-full bg-[#1D4E80]"></span>Okupansi Kamar per Cabang</h2>
                    <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500">Dihitung dari jumlah kamar dan data sewa
                        aktif yang tanggal sewanya mencakup hari ini.</p>
                </div>
                <div class="flex flex-wrap gap-3 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-blue-700"></span>Kamar
                        Terisi</span>
                    <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-sky-500"></span>Kamar
                        Kosong</span>
                </div>
            </div>

            @if ($branchOccupancy->isEmpty())
                <div class="mt-4 rounded-lg bg-[#F0F3FF] px-4 py-12 text-center text-sm text-slate-500">Belum ada data
                    cabang atau kamar untuk ditampilkan.</div>
            @else
                <div class="mt-4 rounded-lg bg-[#F0F3FF] p-4 sm:p-5">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3" id="occupancy-chart-grid">
                        @foreach ($branchOccupancy as $index => $branch)
                            <article class="occupancy-card min-w-0 rounded-lg" data-branch="{{ $branch['id'] }}">
                                <div class="mx-auto h-40 w-40 sm:h-44 sm:w-44">
                                    <canvas id="occupancy-chart-{{ $branch['id'] }}"
                                        aria-label="Grafik okupansi {{ $branch['name'] }}"></canvas>
                                </div>
                                <p class="mt-3 text-center text-xs font-semibold text-slate-600">{{ $branch['name'] }}</p>
                                <div class="mt-3 rounded-lg border border-slate-100 bg-white p-3">
                                    <p class="flex items-center gap-2 text-xs text-slate-500">
                                        <span class="h-2 w-2 rounded-full"
                                            style="background-color: {{ $branchColors[$index % count($branchColors)] }}"></span>
                                        {{ $branch['name'] }}
                                    </p>
                                    <p class="mt-1 text-xl font-bold text-slate-800">{{ $branch['percentage'] }}%</p>
                                    <p class="text-[11px] text-slate-500">{{ $branch['occupied'] }} terisi /
                                        {{ $branch['total'] }} kamar · {{ $branch['vacant'] }} kosong</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <p class="mt-4 text-[11px] leading-5 text-slate-500">Kamar kosong dihitung dari total kamar dikurangi
                        kamar yang memiliki sewa aktif. Pastikan status sewa di database menggunakan nilai
                        <code>active</code>.
                    </p>
                </div>
            @endif
        </section>

        <section class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            <article class="rounded-xl border border-slate-100 bg-white p-5 sm:p-6">
                <h2 class="flex items-center gap-2 text-base font-semibold text-slate-800"><span
                        class="h-2 w-2 rounded-full bg-[#1D4E80]"></span>Ringkasan Data Sewa</h2>
                <p class="mt-1 text-xs text-slate-500">Ringkasan berdasarkan data pada tabel rent.</p>

                <div class="mt-5 grid grid-cols-3 gap-2">
                    <div class="rounded-lg bg-[#F0F3FF] p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Total Kontrak</p>
                        <p class="mt-1 text-xl font-bold text-slate-800">{{ number_format($totalRents) }}</p>
                        <p class="mt-1 text-[11px] text-slate-500">Kontrak tercatat</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-800">Berjalan</p>
                        <p class="mt-1 text-xl font-bold text-emerald-800">{{ number_format($activeRents) }}</p>
                        <p class="mt-1 text-[11px] text-emerald-800">Status active</p>
                    </div>
                    <div class="rounded-lg bg-slate-100 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Selesai</p>
                        <p class="mt-1 text-xl font-bold text-slate-700">{{ number_format($finishedRents) }}</p>
                        <p class="mt-1 text-[11px] text-slate-500">Kontrak selesai</p>
                    </div>
                </div>

                <div class="mt-5">
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <span class="font-medium text-slate-600">Rasio Okupansi Kamar ({{ $occupiedRooms }} /
                            {{ $roomCount }})</span>
                        <span
                            class="font-semibold text-slate-700">{{ $roomCount > 0 ? round(($occupiedRooms / $roomCount) * 100) : 0 }}%
                            Terisi</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-blue-100">
                        <div class="h-full rounded-full bg-[#1D4E80]"
                            style="width: {{ $roomCount > 0 ? min(100, round(($occupiedRooms / $roomCount) * 100)) : 0 }}%">
                        </div>
                    </div>
                </div>
                <a href="{{ url('/owner/sewa') }}"
                    class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg bg-blue-50 px-4 py-3 text-xs font-semibold text-[#315F96] transition hover:bg-blue-100">
                    Kelola Data Sewa <i data-feather="arrow-right" class="h-4 w-4"></i>
                </a>
            </article>

            <article class="rounded-xl border border-slate-100 bg-white p-5 sm:p-6">
                <h2 class="flex items-center gap-2 text-base font-semibold text-slate-800"><span
                        class="h-2 w-2 rounded-full bg-emerald-700"></span>Ringkasan Pembayaran</h2>
                <p class="mt-1 text-xs text-slate-500">Akumulasi pembayaran dengan tanggal pembayaran terisi.</p>
                <div class="mt-5 rounded-lg bg-[#F0F3FF] p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Total Pembayaran Tercatat
                    </p>
                    <p class="mt-1 text-2xl font-extrabold tracking-tight text-[#1D4E80] sm:text-3xl">
                        {{ $money($totalPaidAmount) }}</p>
                    <div class="mt-2 flex items-center justify-between gap-3 text-xs text-slate-500">
                        <span>Total pembayaran terverifikasi</span>
                        <strong class="text-slate-700">{{ number_format($paidTransactions) }} transaksi</strong>
                    </div>
                </div>
                <div class="mt-3 space-y-2">
                    @forelse ($paymentMethodTotals as $method)
                        <div class="flex items-center gap-3 rounded-lg bg-[#F0F3FF] px-3 py-3 text-xs">
                            <i data-feather="credit-card" class="h-4 w-4 shrink-0 text-slate-500"></i>
                            <span
                                class="flex-1 text-slate-600">{{ $method->payment_method ?: 'Metode tidak dicatat' }}</span>
                            <div class="h-1.5 w-14 overflow-hidden rounded-full bg-blue-100">
                                <div class="h-full bg-[#1D4E80]"
                                    style="width: {{ min(100, round(($method->transaction_count / $maxMethodCount) * 100)) }}%">
                                </div>
                            </div>
                            <strong
                                class="w-10 text-right text-slate-700">{{ number_format($method->transaction_count) }}</strong>
                        </div>
                    @empty
                        <p class="rounded-lg bg-[#F0F3FF] px-3 py-4 text-xs text-slate-500">Belum ada pembayaran
                            terverifikasi.</p>
                    @endforelse
                </div>
                <a href="{{ url('/owner/pembayaran') }}"
                    class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg bg-[#1D4E80] px-4 py-3 text-xs font-semibold text-white transition hover:bg-[#173e66]">
                    Kelola Data Pembayaran <i data-feather="arrow-right" class="h-4 w-4"></i>
                </a>
            </article>
        </section>

        <section class="rounded-xl border border-slate-100 bg-white p-5 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="flex items-center gap-2 text-base font-semibold text-slate-800"><span
                            class="h-2 w-2 rounded-full bg-blue-700"></span>Pendapatan Bulanan per Cabang</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Akumulasi pembayaran yang memiliki paid_at pada tahun
                        {{ $year }}. Nominal ditampilkan dalam rupiah.</p>
                </div>
                <span class="rounded-md bg-blue-50 px-3 py-1.5 text-xs font-semibold text-[#315F96]">Tahun
                    {{ $year }}</span>
            </div>
            <div class="mt-4 rounded-lg bg-[#F0F3FF] p-3 sm:p-5">
                <div class="h-[280px] sm:h-[320px]">
                    <canvas id="monthlyRevenueChart"
                        aria-label="Grafik pendapatan bulanan per cabang untuk tahun {{ $year }}"></canvas>
                </div>
                <p class="mt-2 text-[11px] text-slate-500">Sumber: tabel payment → booking → room → branch_dorm. Pembayaran
                    dihitung jika kolom paid_at tidak NULL.</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-100 bg-white">
            <div class="flex flex-col gap-3 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Pembayaran Terbaru</h2>
                    <p class="mt-1 text-xs text-slate-500">Lima pembayaran terbaru yang telah memiliki tanggal pembayaran.
                    </p>
                </div>
                <a href="{{ url('/owner/pembayaran') }}"
                    class="inline-flex items-center justify-center gap-2 self-start rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-[#315F96] transition hover:bg-blue-100 sm:self-auto">
                    Lihat Semua Pembayaran <i data-feather="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table id="recent-payments-table" class="min-w-[850px] w-full border-collapse text-left">
                    <thead class="bg-[#F0F3FF] text-[10px] uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-5 py-4 font-bold">Tanggal Pembayaran</th>
                            <th class="px-5 py-4 font-bold">Kode Pembayaran</th>
                            <th class="px-5 py-4 font-bold">Kode Booking</th>
                            <th class="px-5 py-4 font-bold">Penyewa</th>
                            <th class="px-5 py-4 font-bold">Metode Pembayaran</th>
                            <th class="px-5 py-4 text-right font-bold">Total Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                        @forelse ($recentPayments as $payment)
                            @php
                                $tenantName =
                                    trim(($payment->fname ?? '') . ' ' . ($payment->lname ?? '')) ?: 'Penyewa';
                                $paidDate = $payment->paid_at
                                    ? \Carbon\Carbon::parse($payment->paid_at)->format('d M Y')
                                    : '-';
                            @endphp
                            <tr class="payment-row hover:bg-slate-50"
                                data-search="{{ strtolower(($payment->payment_code ?? '') . ' ' . ($payment->booking_code ?? '') . ' ' . $tenantName . ' ' . ($payment->payment_method ?? '')) }}">
                                <td class="whitespace-nowrap px-5 py-4">{{ $paidDate }}</td>
                                <td class="whitespace-nowrap px-5 py-4 font-semibold text-[#315F96]">
                                    {{ $payment->payment_code }}</td>
                                <td class="whitespace-nowrap px-5 py-4">{{ $payment->booking_code }}</td>
                                <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-800">{{ $tenantName }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">{{ $payment->payment_method ?: '-' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-800">
                                    {{ $money($payment->amount) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-sm text-slate-500">Belum ada
                                    pembayaran terverifikasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <p id="payments-empty" class="hidden px-5 py-8 text-center text-sm text-slate-500">Tidak ada transaksi
                    yang cocok dengan pencarian.</p>
            </div>
        </section>

        <p class="text-center text-[11px] text-slate-400">Dashboard eKost · Statistik dan grafik diperoleh dari database.
        </p>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.feather) feather.replace();

            const branchOccupancy = @json($branchOccupancy);
            const branchColors = @json($branchColors);
            const monthLabels = @json($monthLabels);
            const revenueDatasets = @json($monthlyRevenueDatasets);

            if (typeof Chart !== 'undefined') {
                branchOccupancy.forEach(function(branch, index) {
                    const canvas = document.getElementById('occupancy-chart-' + branch.id);
                    if (!canvas) return;

                    new Chart(canvas, {
                        type: 'pie',
                        data: {
                            labels: ['Kamar Terisi', 'Kamar Kosong'],
                            datasets: [{
                                data: [branch.occupied, branch.vacant],
                                backgroundColor: ['#2450D8', '#12A5E5'],
                                hoverOffset: 5,
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.label + ': ' + context.raw +
                                                ' kamar';
                                        }
                                    }
                                }
                            }
                        }
                    });
                });

                const revenueCanvas = document.getElementById('monthlyRevenueChart');
                if (revenueCanvas) {
                    const chartDatasets = revenueDatasets.map(function(dataset, index) {
                        return {
                            label: dataset.label,
                            data: dataset.data,
                            backgroundColor: branchColors[index % branchColors.length],
                            borderRadius: 4,
                            maxBarThickness: 18
                        };
                    });

                    new Chart(revenueCanvas, {
                        type: 'bar',
                        data: {
                            labels: monthLabels,
                            datasets: chartDatasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 7,
                                        font: {
                                            size: 11
                                        }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + new Intl.NumberFormat(
                                                'id-ID', {
                                                    style: 'currency',
                                                    currency: 'IDR',
                                                    maximumFractionDigits: 0
                                                }).format(context.raw);
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: '#64748b',
                                        font: {
                                            size: 10
                                        }
                                    },
                                    border: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        color: '#64748b',
                                        font: {
                                            size: 10
                                        },
                                        callback: function(value) {
                                            return new Intl.NumberFormat('id-ID', {
                                                notation: 'compact',
                                                style: 'currency',
                                                currency: 'IDR',
                                                maximumFractionDigits: 1
                                            }).format(value);
                                        }
                                    },
                                    grid: {
                                        color: '#dfe6f5'
                                    },
                                    border: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // Filter cabang pada grafik okupansi.
            const filterButtons = document.querySelectorAll('.branch-filter');
            const occupancyCards = document.querySelectorAll('.occupancy-card');
            filterButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const selected = button.dataset.branchFilter;
                    filterButtons.forEach(function(item) {
                        item.classList.remove('bg-[#1D4E80]', 'text-white',
                            'font-semibold');
                        item.classList.add('bg-[#F0F3FF]', 'text-slate-600', 'font-medium');
                    });
                    button.classList.remove('bg-[#F0F3FF]', 'text-slate-600', 'font-medium');
                    button.classList.add('bg-[#1D4E80]', 'text-white', 'font-semibold');

                    occupancyCards.forEach(function(card) {
                        card.classList.toggle('hidden', selected !== 'all' && card.dataset
                            .branch !== selected);
                    });
                });
            });

            // Pencarian pembayaran terbaru.
            const searchInput = document.getElementById('dashboard-search');
            const paymentRows = document.querySelectorAll('.payment-row');
            const emptyMessage = document.getElementById('payments-empty');

            searchInput?.addEventListener('input', function() {
                const query = searchInput.value.trim().toLowerCase();
                let visibleRows = 0;
                paymentRows.forEach(function(row) {
                    const text = (row.dataset.search || row.textContent).toLowerCase();
                    const matches = text.includes(query);
                    row.classList.toggle('hidden', !matches);
                    if (matches) visibleRows++;
                });
                emptyMessage?.classList.toggle('hidden', visibleRows !== 0);
            });
        });
    </script>
@endpush
