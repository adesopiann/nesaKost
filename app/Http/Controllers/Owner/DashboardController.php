<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $year = Carbon::now()->year;

        // Statistik utama.
        $stats = [
            'owners' => DB::table('owners')->count(),
            'branches' => DB::table('branch_dorm')->count(),
            'rooms' => DB::table('room')->count(),
            'tenants' => DB::table('tenants')->count(),
        ];

        // Total kamar per cabang.
        $branches = DB::table('branch_dorm as b')
            ->leftJoin('room as rm', 'rm.branch_dorm_id', '=', 'b.id')
            ->select('b.id', 'b.name', DB::raw('COUNT(DISTINCT rm.id) as total_rooms'))
            ->groupBy('b.id', 'b.name')
            ->orderBy('b.name')
            ->get();

        // Kamar dianggap terisi jika memiliki sewa berstatus active yang mencakup tanggal hari ini.
        // Jika aplikasi menggunakan nilai status lain, sesuaikan daftar status di bawah.
        $occupiedByBranch = DB::table('rent as rt')
            ->join('room as rm', 'rm.id', '=', 'rt.room_id')
            ->whereIn('rt.status', ['active'])
            ->whereDate('rt.start_date', '<=', $today)
            ->whereDate('rt.end_date', '>=', $today)
            ->select('rm.branch_dorm_id', DB::raw('COUNT(DISTINCT rm.id) as occupied_rooms'))
            ->groupBy('rm.branch_dorm_id')
            ->pluck('occupied_rooms', 'branch_dorm_id');

        $branchOccupancy = $branches->map(function ($branch) use ($occupiedByBranch) {
            $total = (int) $branch->total_rooms;
            $occupied = min((int) ($occupiedByBranch[$branch->id] ?? 0), $total);

            return [
                'id' => (string) $branch->id,
                'name' => $branch->name,
                'total' => $total,
                'occupied' => $occupied,
                'vacant' => max(0, $total - $occupied),
                'percentage' => $total > 0 ? round(($occupied / $total) * 100) : 0,
            ];
        })->values();

        // Pendapatan bulanan: jumlah pembayaran yang sudah memiliki paid_at.
        // Asumsi paid_at hanya diisi ketika pembayaran berhasil/terverifikasi.
        $monthlyPaymentRows = DB::table('payment as p')
            ->join('booking as bk', 'bk.id', '=', 'p.booking_id')
            ->join('room as rm', 'rm.id', '=', 'bk.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->whereNotNull('p.paid_at')
            ->whereYear('p.paid_at', $year)
            ->select(
                'b.id as branch_id',
                'b.name as branch_name',
                DB::raw('MONTH(p.paid_at) as month_number'),
                DB::raw('SUM(p.amount) as total_amount')
            )
            ->groupBy('b.id', 'b.name', DB::raw('MONTH(p.paid_at)'))
            ->orderBy('b.name')
            ->get();

        $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $monthlyRevenueDatasets = $branches->map(function ($branch) use ($monthlyPaymentRows) {
            $values = array_fill(1, 12, 0);

            foreach ($monthlyPaymentRows->where('branch_id', $branch->id) as $row) {
                $values[(int) $row->month_number] = (float) $row->total_amount;
            }

            return [
                'label' => $branch->name,
                'data' => array_values($values),
            ];
        })->values();

        $totalPaidAmount = (float) DB::table('payment')
            ->whereNotNull('paid_at')
            ->sum('amount');

        $paidTransactions = DB::table('payment')
            ->whereNotNull('paid_at')
            ->count();

        // Ringkasan pembayaran terbaru.
        $recentPayments = DB::table('payment as p')
            ->join('booking as bk', 'bk.id', '=', 'p.booking_id')
            ->leftJoin('tenants as t', 't.id', '=', 'bk.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->select(
                'p.payment_code',
                'p.booking_id',
                'p.amount',
                'p.payment_method',
                'p.status',
                'p.paid_at',
                'bk.booking_code',
                'u.fname',
                'u.lname'
            )
            ->whereNotNull('p.paid_at')
            ->orderByDesc('p.paid_at')
            ->limit(5)
            ->get();

        // Ringkasan sewa aktif dan selesai. Sesuaikan nilai status jika enum aplikasi berbeda.
        $rentQuery = DB::table('rent');
        $totalRents = (clone $rentQuery)->count();
        $activeRents = (clone $rentQuery)->where('status', 'active')->count();
        $finishedRents = (clone $rentQuery)->whereIn('status', ['completed', 'finished'])->count();

        $occupiedRooms = $branchOccupancy->sum('occupied');
        $roomCount = (int) $stats['rooms'];

        $paymentMethodTotals = DB::table('payment')
            ->whereNotNull('paid_at')
            ->select('payment_method', DB::raw('COUNT(*) as transaction_count'))
            ->groupBy('payment_method')
            ->orderByDesc('transaction_count')
            ->get();

        return view('pages.owner.dashboard.index', compact(
            'stats',
            'branchOccupancy',
            'monthLabels',
            'monthlyRevenueDatasets',
            'year',
            'totalPaidAmount',
            'paidTransactions',
            'recentPayments',
            'totalRents',
            'activeRents',
            'finishedRents',
            'occupiedRooms',
            'roomCount',
            'paymentMethodTotals'
        ));
    }
}
