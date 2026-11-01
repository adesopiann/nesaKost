<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index (Request $request)
    {
       $today = Carbon::today();
        $year = Carbon::now()->year;

        // 1. Identifikasi owner yang sedang login.
        $ownerId = DB::table('owners')
            ->where('user_id', auth()->id())
            ->value('id');

        abort_unless($ownerId, 403, 'Data owner tidak ditemukan.');

        // 2. Ambil seluruh cabang milik owner.
        $branches = DB::table('branch_dorm')
            ->where('owner_id', $ownerId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // 3. Validasi filter cabang.
        $selectedBranchId = $request->query('branch_id');

        if ($selectedBranchId !== null) {
            abort_unless(
                $branches->contains(
                    fn ($branch) => (string) $branch->id === (string) $selectedBranchId
                ),
                404
            );

            $selectedBranchId = (string) $selectedBranchId;
        }

        // Query dasar kamar berdasarkan cabang.
        $roomQuery = DB::table('room as rm')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->where('b.owner_id', $ownerId);

        if ($selectedBranchId !== null) {
            $roomQuery->where('rm.branch_dorm_id', $selectedBranchId);
        }

        // 4. Statistik utama.
        $stats = [
            'branches' => $selectedBranchId !== null
                ? 1
                : $branches->count(),

            'rooms' => (clone $roomQuery)->count(),

            // Penyewa yang memiliki kontrak sewa pada cabang terkait.
            'tenants' => DB::table('rent as rt')
                ->join('room as rm', 'rm.id', '=', 'rt.room_id')
                ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
                ->where('b.owner_id', $ownerId)
                ->when(
                    $selectedBranchId !== null,
                    fn ($q) => $q->where('rm.branch_dorm_id', $selectedBranchId)
                )
                ->distinct()
                ->count('rt.tenant_id'),
        ];

        // 5. Data okupansi kamar.
        $branchQuery = DB::table('branch_dorm as b')
            ->leftJoin('room as rm', 'rm.branch_dorm_id', '=', 'b.id')
            ->where('b.owner_id', $ownerId)
            ->when(
                $selectedBranchId !== null,
                fn ($q) => $q->where('b.id', $selectedBranchId)
            )
            ->select(
                'b.id',
                'b.name',
                DB::raw('COUNT(DISTINCT rm.id) as total_rooms')
            )
            ->groupBy('b.id', 'b.name')
            ->orderBy('b.name');

        $branchRoomData = $branchQuery->get();

        $occupiedByBranch = DB::table('rent as rt')
            ->join('room as rm', 'rm.id', '=', 'rt.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->where('b.owner_id', $ownerId)
            ->where('rt.status', 'active')
            ->whereDate('rt.start_date', '<=', $today)
            ->whereDate('rt.end_date', '>=', $today)
            ->when(
                $selectedBranchId !== null,
                fn ($q) => $q->where('rm.branch_dorm_id', $selectedBranchId)
            )
            ->select(
                'rm.branch_dorm_id',
                DB::raw('COUNT(DISTINCT rm.id) as occupied_rooms')
            )
            ->groupBy('rm.branch_dorm_id')
            ->pluck('occupied_rooms', 'branch_dorm_id');

        $branchOccupancy = $branchRoomData->map(function ($branch) use ($occupiedByBranch) {
            $total = (int) $branch->total_rooms;
            $occupied = min(
                (int) ($occupiedByBranch[$branch->id] ?? 0),
                $total
            );

            return [
                'id' => (string) $branch->id,
                'name' => $branch->name,
                'total' => $total,
                'occupied' => $occupied,
                'vacant' => max(0, $total - $occupied),
                'percentage' => $total > 0
                    ? round(($occupied / $total) * 100)
                    : 0,
            ];
        })->values();

        // 6. Ringkasan data sewa.
        $rentQuery = DB::table('rent as rt')
            ->join('room as rm', 'rm.id', '=', 'rt.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->where('b.owner_id', $ownerId)
            ->when(
                $selectedBranchId !== null,
                fn ($q) => $q->where('rm.branch_dorm_id', $selectedBranchId)
            );

        $totalRents = (clone $rentQuery)->count();

        $activeRents = (clone $rentQuery)
            ->where('rt.status', 'active')
            ->count();

        $finishedRents = (clone $rentQuery)
            ->whereIn('rt.status', ['completed', 'finished'])
            ->count();

        // 7. Rasio okupansi.
        $occupiedRooms = $branchOccupancy->sum('occupied');
        $roomCount = (int) $stats['rooms'];

        // 8. Query dasar pembayaran berdasarkan cabang.
        $paymentQuery = DB::table('payment as p')
            ->join('booking as bk', 'bk.id', '=', 'p.booking_id')
            ->join('room as rm', 'rm.id', '=', 'bk.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->where('b.owner_id', $ownerId)
            ->whereNotNull('p.paid_at')
            ->when(
                $selectedBranchId !== null,
                fn ($q) => $q->where('rm.branch_dorm_id', $selectedBranchId)
            );

        $totalPaidAmount = (float) (clone $paymentQuery)
            ->sum('p.amount');

        $paidTransactions = (clone $paymentQuery)
            ->count();

        // 9. Ringkasan metode pembayaran.
        $paymentMethodTotals = (clone $paymentQuery)
            ->select(
                'p.payment_method',
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('p.payment_method')
            ->orderByDesc('transaction_count')
            ->get();

        // 10. Grafik pendapatan bulanan.
        $monthlyPaymentRows = (clone $paymentQuery)
            ->whereYear('p.paid_at', $year)
            ->select(
                'b.id as branch_id',
                'b.name as branch_name',
                DB::raw('MONTH(p.paid_at) as month_number'),
                DB::raw('SUM(p.amount) as total_amount')
            )
            ->groupBy(
                'b.id',
                'b.name',
                DB::raw('MONTH(p.paid_at)')
            )
            ->orderBy('b.name')
            ->get();

        $monthLabels = [
            'Jan', 'Feb', 'Mar', 'Apr',
            'Mei', 'Jun', 'Jul', 'Agu',
            'Sep', 'Okt', 'Nov', 'Des'
        ];

        $monthlyRevenueDatasets = $branchRoomData->map(function ($branch) use ($monthlyPaymentRows) {
            $values = array_fill(0, 12, 0);

            foreach ($monthlyPaymentRows->where('branch_id', $branch->id) as $row) {
                $values[(int) $row->month_number - 1] =
                    (float) $row->total_amount;
            }

            return [
                'label' => $branch->name,
                'data' => $values,
            ];
        })->values();

        // 11. Lima pembayaran terbaru.
        $recentPayments = DB::table('payment as p')
            ->join('booking as bk', 'bk.id', '=', 'p.booking_id')
            ->join('room as rm', 'rm.id', '=', 'bk.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->leftJoin('tenants as t', 't.id', '=', 'bk.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->where('b.owner_id', $ownerId)
            ->whereNotNull('p.paid_at')
            ->when(
                $selectedBranchId !== null,
                fn ($q) => $q->where('rm.branch_dorm_id', $selectedBranchId)
            )
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
            ->orderByDesc('p.paid_at')
            ->limit(5)
            ->get();

        return view('pages.owner.dashboard.index', compact(
            'stats',
            'branches',
            'selectedBranchId',
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
