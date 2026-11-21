<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();
        $currentYear = (int) Carbon::now()->year;
        $yearInput = $request->query('year');
        $year = ($yearInput !== null && $yearInput !== '') ? (int) $yearInput : null;
        $rentMonthInput = $request->query('rent_month');
        $rentMonth = ($rentMonthInput !== null && $rentMonthInput !== '') ? (int) $rentMonthInput : null;
        $rentYearInput = $request->query('rent_year');
        $rentYear = ($rentYearInput !== null && $rentYearInput !== '') ? (int) $rentYearInput : null;
        $paymentMonthInput = $request->query('payment_month');
        $paymentMonth = ($paymentMonthInput !== null && $paymentMonthInput !== '') ? (int) $paymentMonthInput : null;
        $paymentYearInput = $request->query('payment_year');
        $paymentYear = ($paymentYearInput !== null && $paymentYearInput !== '') ? (int) $paymentYearInput : null;

        abort_unless($year === null || ($year >= 2000 && $year <= $currentYear + 1), 404);
        abort_unless($rentMonth === null || ($rentMonth >= 1 && $rentMonth <= 12), 404);
        abort_unless($rentYear === null || ($rentYear >= 2000 && $rentYear <= $currentYear + 1), 404);
        abort_unless($paymentMonth === null || ($paymentMonth >= 1 && $paymentMonth <= 12), 404);
        abort_unless($paymentYear === null || ($paymentYear >= 2000 && $paymentYear <= $currentYear + 1), 404);
        $monthOptions = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $yearOptions = range($currentYear, max(2020, $currentYear - 5));

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

        // 3. Validasi filter cabang agar owner tidak dapat mengakses cabang lain.
        $selectedBranchId = $request->query('branch_id');

        if ($selectedBranchId !== null && $selectedBranchId !== '') {
            abort_unless(
                $branches->contains(
                    fn ($branch) => (string) $branch->id === (string) $selectedBranchId
                ),
                404
            );

            $selectedBranchId = (string) $selectedBranchId;
        } else {
            $selectedBranchId = null;
        }

        $applyBranchFilter = function ($query, string $roomColumn = 'rm.branch_dorm_id') use ($selectedBranchId) {
            return $query->when(
                $selectedBranchId !== null,
                fn ($q) => $q->where($roomColumn, $selectedBranchId)
            );
        };

        // 4. Inventaris kamar per cabang.
        $branchRoomData = DB::table('branch_dorm as b')
            ->leftJoin('room as rm', 'rm.branch_dorm_id', '=', 'b.id')
            ->where('b.owner_id', $ownerId)
            ->when($selectedBranchId !== null, fn ($q) => $q->where('b.id', $selectedBranchId))
            ->select(
                'b.id',
                'b.name',
                DB::raw('COUNT(DISTINCT rm.id) as total_rooms'),
                DB::raw("COUNT(DISTINCT CASE WHEN rm.status = 'active' THEN rm.id END) as active_rooms"),
                DB::raw("COUNT(DISTINCT CASE WHEN rm.status = 'inactive' THEN rm.id END) as inactive_rooms"),
                DB::raw("COUNT(DISTINCT CASE WHEN rm.status = 'maintenance' THEN rm.id END) as maintenance_rooms")
            )
            ->groupBy('b.id', 'b.name')
            ->orderBy('b.name')
            ->get();

        $totalRooms = (int) $branchRoomData->sum('total_rooms');
        $activeRooms = (int) $branchRoomData->sum('active_rooms');
        $inactiveRooms = (int) $branchRoomData->sum('inactive_rooms');
        $maintenanceRooms = (int) $branchRoomData->sum('maintenance_rooms');

        // 5. Statistik penyewa: total historis dan penyewa aktif hari ini.
        $tenantQuery = DB::table('rent as rt')
            ->join('room as rm', 'rm.id', '=', 'rt.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->where('b.owner_id', $ownerId);
        $applyBranchFilter($tenantQuery);

        $totalTenants = (clone $tenantQuery)->distinct()->count('rt.tenant_id');
        $activeTenants = (clone $tenantQuery)
            ->where('rt.status', 'active')
            ->whereDate('rt.start_date', '<=', $today)
            ->whereDate('rt.end_date', '>=', $today)
            ->distinct()
            ->count('rt.tenant_id');

        // 6. Ringkasan booking.
        $bookingQuery = DB::table('booking as bk')
            ->join('room as rm', 'rm.id', '=', 'bk.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->where('b.owner_id', $ownerId);
        $applyBranchFilter($bookingQuery);

        $bookingSummary = [
            'pending_payment' => (clone $bookingQuery)->where('bk.status', 'pending_payment')->count(),
            'confirmed' => (clone $bookingQuery)->where('bk.status', 'confirmed')->count(),
            'expired' => (clone $bookingQuery)->where('bk.status', 'expired')->count(),
            'cancelled' => (clone $bookingQuery)->where('bk.status', 'cancelled')->count(),
        ];

        // 7. Ringkasan rent.
        $rentQuery = DB::table('rent as rt')
            ->join('room as rm', 'rm.id', '=', 'rt.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->where('b.owner_id', $ownerId);
        $applyBranchFilter($rentQuery);
        $rentQuery->when($rentMonth !== null, fn ($q) => $q->whereMonth('rt.created_at', $rentMonth))
            ->when($rentYear !== null, fn ($q) => $q->whereYear('rt.created_at', $rentYear));

        $totalRents = (clone $rentQuery)->count();
        $activeRents = (clone $rentQuery)
            ->where('rt.status', 'active')
            ->whereDate('rt.start_date', '<=', $today)
            ->whereDate('rt.end_date', '>=', $today)
            ->count();
        $scheduledRents = (clone $rentQuery)->where('rt.status', 'scheduled')->count();
        $finishedRents = (clone $rentQuery)->where('rt.status', 'completed')->count();
        $cancelledRents = (clone $rentQuery)->where('rt.status', 'cancelled')->count();

        // 8. Okupansi hanya menggunakan kamar aktif sebagai denominator.
        $occupiedByBranchQuery = DB::table('rent as rt')
            ->join('room as rm', 'rm.id', '=', 'rt.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->where('b.owner_id', $ownerId)
            ->where('rm.status', 'active')
            ->where('rt.status', 'active')
            ->whereDate('rt.start_date', '<=', $today)
            ->whereDate('rt.end_date', '>=', $today);
        $applyBranchFilter($occupiedByBranchQuery);

        $occupiedByBranch = $occupiedByBranchQuery
            ->select('rm.branch_dorm_id', DB::raw('COUNT(DISTINCT rm.id) as occupied_rooms'))
            ->groupBy('rm.branch_dorm_id')
            ->pluck('occupied_rooms', 'branch_dorm_id');

        $branchOccupancy = $branchRoomData->map(function ($branch) use ($occupiedByBranch) {
            $active = (int) $branch->active_rooms;
            $occupied = min((int) ($occupiedByBranch[$branch->id] ?? 0), $active);

            return [
                'id' => (string) $branch->id,
                'name' => $branch->name,
                'total_rooms' => (int) $branch->total_rooms,
                'active_rooms' => $active,
                'inactive_rooms' => (int) $branch->inactive_rooms,
                'maintenance_rooms' => (int) $branch->maintenance_rooms,
                'total' => $active,
                'occupied' => $occupied,
                'vacant' => max(0, $active - $occupied),
                'percentage' => $active > 0 ? round(($occupied / $active) * 100) : 0,
            ];
        })->values();

        $occupiedRooms = (int) $branchOccupancy->sum('occupied');
        $roomCount = $activeRooms;

        // 9. Statistik utama dashboard.
        $stats = [
            'branches' => $selectedBranchId !== null ? 1 : $branches->count(),
            'rooms' => $totalRooms,
            'active_rooms' => $activeRooms,
            'inactive_rooms' => $inactiveRooms,
            'maintenance_rooms' => $maintenanceRooms,
            'tenants' => $totalTenants,
            'active_tenants' => $activeTenants,
            'pending_bookings' => $bookingSummary['pending_payment'],
        ];

        // 10. Hanya transaksi berstatus paid dan memiliki paid_at yang dihitung sebagai pendapatan.
        $paymentQuery = DB::table('payment as p')
            ->join('booking as bk', 'bk.id', '=', 'p.booking_id')
            ->join('room as rm', 'rm.id', '=', 'bk.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->where('b.owner_id', $ownerId)
            ->where('p.status', 'paid')
            ->whereNotNull('p.paid_at');
        $applyBranchFilter($paymentQuery);

        $monthlyPaidQuery = (clone $paymentQuery)
            ->when($paymentMonth !== null, fn ($q) => $q->whereMonth('p.paid_at', $paymentMonth))
            ->when($paymentYear !== null, fn ($q) => $q->whereYear('p.paid_at', $paymentYear));
        $totalPaidAmount = (float) (clone $monthlyPaidQuery)->sum('p.amount');
        $paidTransactions = (clone $monthlyPaidQuery)->count();

        $paymentMethodTotals = (clone $monthlyPaidQuery)
            ->select('p.payment_method', DB::raw('COUNT(*) as transaction_count'))
            ->groupBy('p.payment_method')
            ->orderByDesc('transaction_count')
            ->get();

        // 11. Grafik pendapatan bulanan, dengan seri terpisah untuk setiap cabang.
        $monthlyPaymentRows = (clone $paymentQuery)
            ->when($year !== null, fn ($q) => $q->whereYear('p.paid_at', $year))
            ->select(
                'b.id as branch_id',
                'b.name as branch_name',
                DB::raw('MONTH(p.paid_at) as month_number'),
                DB::raw('SUM(p.amount) as total_amount')
            )
            ->groupBy('b.id', 'b.name', DB::raw('MONTH(p.paid_at)'))
            ->orderBy('b.name')
            ->get();

        $monthLabels = [
            'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
        ];

        $monthlyRevenueDatasets = $branchRoomData->map(function ($branch) use ($monthlyPaymentRows) {
            $values = array_fill(0, 12, 0);

            foreach ($monthlyPaymentRows->where('branch_id', $branch->id) as $row) {
                $values[(int) $row->month_number - 1] = (float) $row->total_amount;
            }

            return ['label' => $branch->name, 'data' => $values];
        })->values();

        // 12. Lima pembayaran terbaru yang benar-benar berhasil.
        $recentPayments = DB::table('payment as p')
            ->join('booking as bk', 'bk.id', '=', 'p.booking_id')
            ->join('room as rm', 'rm.id', '=', 'bk.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->leftJoin('tenants as t', 't.id', '=', 'bk.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->where('b.owner_id', $ownerId)
            ->where('p.status', 'paid')
            ->whereNotNull('p.paid_at');
        $applyBranchFilter($recentPayments);

        $recentPayments = $recentPayments
            ->select(
                'p.id', 'p.payment_code', 'p.booking_id', 'p.amount', 'p.payment_method',
                'p.status', 'p.paid_at', 'bk.booking_code', 'u.fname', 'u.lname'
            )
            ->orderByDesc('p.paid_at')
            ->limit(5)
            ->get();

        // 13. Rent aktif yang akan berakhir dalam tujuh hari ke depan (termasuk hari ini).
        $expirationLimit = $today->copy()->addDays(7);
        $expiringRents = DB::table('rent as rt')
            ->join('room as rm', 'rm.id', '=', 'rt.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->leftJoin('tenants as t', 't.id', '=', 'rt.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->where('b.owner_id', $ownerId)
            ->where('rt.status', 'active')
            ->whereDate('rt.start_date', '<=', $today)
            ->whereBetween('rt.end_date', [$today->toDateString(), $expirationLimit->toDateString()]);
        $applyBranchFilter($expiringRents);

        $expiringRents = $expiringRents
            ->select(
                'rt.id', 'rt.rent_code', 'rt.end_date', 'rm.room_code',
                'b.name as branch_name', 'u.fname', 'u.lname'
            )
            ->orderBy('rt.end_date')
            ->get()
            ->map(function ($rent) use ($today) {
                $rent->days_remaining = $today->diffInDays(Carbon::parse($rent->end_date), false);
                return $rent;
            });

        // 14. Aktivitas terbaru dari booking, rent, dan pembayaran.
        $activityLimit = 8;

        $bookingActivities = DB::table('booking as bk')
            ->join('room as rm', 'rm.id', '=', 'bk.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->leftJoin('tenants as t', 't.id', '=', 'bk.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->where('b.owner_id', $ownerId);
        $applyBranchFilter($bookingActivities);
        $bookingActivities = $bookingActivities
            ->selectRaw("'booking' as activity_type, bk.created_at as activity_at, bk.booking_code as reference_code, bk.status as activity_status, CONCAT(COALESCE(u.fname, ''), ' ', COALESCE(u.lname, '')) as tenant_name, rm.room_code, b.name as branch_name")
            ->orderByDesc('bk.created_at')
            ->limit($activityLimit)
            ->get();

        $rentActivities = DB::table('rent as rt')
            ->join('room as rm', 'rm.id', '=', 'rt.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->leftJoin('tenants as t', 't.id', '=', 'rt.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->where('b.owner_id', $ownerId);
        $applyBranchFilter($rentActivities);
        $rentActivities = $rentActivities
            ->selectRaw("'rent' as activity_type, rt.created_at as activity_at, rt.rent_code as reference_code, rt.status as activity_status, CONCAT(COALESCE(u.fname, ''), ' ', COALESCE(u.lname, '')) as tenant_name, rm.room_code, b.name as branch_name")
            ->orderByDesc('rt.created_at')
            ->limit($activityLimit)
            ->get();

        $paymentActivities = DB::table('payment as p')
            ->join('booking as bk', 'bk.id', '=', 'p.booking_id')
            ->join('room as rm', 'rm.id', '=', 'bk.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->leftJoin('tenants as t', 't.id', '=', 'bk.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->where('b.owner_id', $ownerId)
            ->whereNotNull('p.paid_at');
        $applyBranchFilter($paymentActivities);
        $paymentActivities = $paymentActivities
            ->selectRaw("'payment' as activity_type, p.paid_at as activity_at, p.payment_code as reference_code, p.status as activity_status, CONCAT(COALESCE(u.fname, ''), ' ', COALESCE(u.lname, '')) as tenant_name, rm.room_code, b.name as branch_name")
            ->orderByDesc('p.paid_at')
            ->limit($activityLimit)
            ->get();

        $recentActivities = $bookingActivities
            ->concat($rentActivities)
            ->concat($paymentActivities)
            ->sortByDesc('activity_at')
            ->take($activityLimit)
            ->values();

        return view('pages.owner.dashboard.index', compact(
            'stats',
            'bookingSummary',
            'branches',
            'selectedBranchId',
            'branchOccupancy',
            'monthLabels',
            'monthlyRevenueDatasets',
            'year',
            'yearOptions',
            'monthOptions',
            'rentMonth',
            'rentYear',
            'paymentMonth',
            'paymentYear',
            'totalPaidAmount',
            'paidTransactions',
            'recentPayments',
            'totalRents',
            'activeRents',
            'scheduledRents',
            'finishedRents',
            'cancelledRents',
            'occupiedRooms',
            'roomCount',
            'paymentMethodTotals',
            'expiringRents',
            'recentActivities'
        ));
    }
}
