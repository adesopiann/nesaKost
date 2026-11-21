<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransactionController extends Controller
{
    /**
     * Mengambil identitas owner dan cabang yang dimilikinya.
     */
    private function ownerContext(Request $request): array
    {
        $ownerId = DB::table('owners')
            ->where('user_id', $request->user()->id)
            ->value('id');

        abort_unless($ownerId, 403, 'Data owner tidak ditemukan.');

        $branches = DB::table('branch_dorm')
            ->where('owner_id', $ownerId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $branchId = $request->query('branch_id');

        if ($branchId !== null && $branchId !== '') {
            abort_unless(
                $branches->contains(
                    fn ($branch) => (string) $branch->id === (string) $branchId
                ),
                404
            );

            $branchId = (int) $branchId;
        } else {
            $branchId = null;
        }

        return [$ownerId, $branches, $branchId];
    }

    /**
     * Validasi filter periode dan pencarian.
     *
     * Month dan year bersifat opsional.
     * Jika tidak dipilih, nilainya null.
     */
    private function validatedPeriod(Request $request): array
    {
        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,' . (now()->year + 1)],
            'status' => ['nullable', 'string', 'max:30'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return [
            isset($validated['month']) && $validated['month'] !== ''
                ? (int) $validated['month']
                : null,

            isset($validated['year']) && $validated['year'] !== ''
                ? (int) $validated['year']
                : null,

            $validated['status'] ?? 'all',
            trim($validated['q'] ?? ''),
        ];
    }

    /**
     * Menerapkan filter bulan dan tahun secara independen.
     */
    private function applyPeriodFilter($query, string $column, ?int $month, ?int $year)
    {
        return $query
            ->when(
                $month !== null,
                fn ($q) => $q->whereMonth($column, $month)
            )
            ->when(
                $year !== null,
                fn ($q) => $q->whereYear($column, $year)
            );
    }

    /**
     * Halaman Data Sewa Owner.
     */
    public function rents(Request $request): View
    {
        [$ownerId, $branches, $branchId] = $this->ownerContext($request);

        [$month, $year, $status, $search] = $this->validatedPeriod($request);

        $query = DB::table('rent as rt')
            ->join('room as rm', 'rm.id', '=', 'rt.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->leftJoin('tenants as t', 't.id', '=', 'rt.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->where('b.owner_id', $ownerId)
            ->when(
                $branchId !== null,
                fn ($q) => $q->where('b.id', $branchId)
            );

        $this->applyPeriodFilter(
            $query,
            'rt.created_at',
            $month,
            $year
        );

        if ($status !== 'all') {
            abort_unless(
                in_array($status, ['scheduled', 'active', 'completed', 'cancelled'], true),
                404
            );

            $query->where('rt.status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('rt.rent_code', 'like', "%{$search}%")
                    ->orWhere('rm.room_code', 'like', "%{$search}%")
                    ->orWhere('b.name', 'like', "%{$search}%")
                    ->orWhere('u.fname', 'like', "%{$search}%")
                    ->orWhere('u.lname', 'like', "%{$search}%");
            });
        }

        // Ringkasan kontrak mengikuti filter cabang dan periode.
        $summaryQuery = DB::table('rent as rt')
            ->join('room as rm', 'rm.id', '=', 'rt.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->where('b.owner_id', $ownerId)
            ->when(
                $branchId !== null,
                fn ($q) => $q->where('b.id', $branchId)
            );

        $this->applyPeriodFilter(
            $summaryQuery,
            'rt.created_at',
            $month,
            $year
        );

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'scheduled' => (clone $summaryQuery)
                ->where('rt.status', 'scheduled')
                ->count(),
            'active' => (clone $summaryQuery)
                ->where('rt.status', 'active')
                ->count(),
            'completed' => (clone $summaryQuery)
                ->where('rt.status', 'completed')
                ->count(),
            'cancelled' => (clone $summaryQuery)
                ->where('rt.status', 'cancelled')
                ->count(),
        ];

        $rents = $query->select(
            'rt.id',
            'rt.rent_code',
            'rt.booking_id',
            'rt.start_date',
            'rt.end_date',
            'rt.status',
            'rt.total_price',
            'rt.created_at',
            'rm.room_code',
            'b.name as branch_name',
            'u.fname',
            'u.lname'
        )
            ->orderByDesc('rt.created_at')
            ->orderByDesc('rt.id')
            ->paginate(15)
            ->withQueryString();

        return view('pages.owner.rents.index', compact(
            'rents',
            'summary',
            'branches',
            'branchId',
            'month',
            'year',
            'status',
            'search'
        ));
    }

    /**
     * Halaman Data Pembayaran Owner.
     */
    public function payments(Request $request): View
    {
        [$ownerId, $branches, $branchId] = $this->ownerContext($request);

        [$month, $year, $status, $search] = $this->validatedPeriod($request);

        $baseQuery = DB::table('payment as p')
            ->join('booking as bk', 'bk.id', '=', 'p.booking_id')
            ->join('room as rm', 'rm.id', '=', 'bk.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->leftJoin('tenants as t', 't.id', '=', 'bk.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->where('b.owner_id', $ownerId)
            ->when(
                $branchId !== null,
                fn ($q) => $q->where('b.id', $branchId)
            );

        /*
         * Filter tanggal pembayaran:
         *
         * - Pembayaran paid menggunakan paid_at.
         * - Pembayaran selain paid menggunakan created_at.
         * - Jika paid_at null, created_at digunakan.
         *
         * Filter bulan dan tahun dapat dipilih secara independen.
         * Jika keduanya kosong, seluruh transaksi ditampilkan.
         */
        if ($month !== null || $year !== null) {
            $baseQuery->where(function ($dateQuery) use ($month, $year) {
                // Pembayaran berhasil menggunakan paid_at.
                $dateQuery->where(function ($paidQuery) use ($month, $year) {
                    $paidQuery
                        ->where('p.status', 'paid')
                        ->whereNotNull('p.paid_at');

                    $this->applyPeriodFilter(
                        $paidQuery,
                        'p.paid_at',
                        $month,
                        $year
                    );
                });

                // Pembayaran selain berhasil menggunakan created_at.
                $dateQuery->orWhere(function ($attemptQuery) use ($month, $year) {
                    $attemptQuery->where(function ($notPaid) {
                        $notPaid
                            ->where('p.status', '!=', 'paid')
                            ->orWhereNull('p.paid_at');
                    });

                    $this->applyPeriodFilter(
                        $attemptQuery,
                        'p.created_at',
                        $month,
                        $year
                    );
                });
            });
        }

        if ($status !== 'all') {
            abort_unless(
                in_array($status, ['pending', 'paid', 'failed', 'expired'], true),
                404
            );

            $baseQuery->where('p.status', $status);
        }

        if ($search !== '') {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('p.payment_code', 'like', "%{$search}%")
                    ->orWhere('bk.booking_code', 'like', "%{$search}%")
                    ->orWhere('rm.room_code', 'like', "%{$search}%")
                    ->orWhere('b.name', 'like', "%{$search}%")
                    ->orWhere('u.fname', 'like', "%{$search}%")
                    ->orWhere('u.lname', 'like', "%{$search}%");
            });
        }

        // Ringkasan hanya menghitung pembayaran yang benar-benar diterima.
        $summaryBase = DB::table('payment as p')
            ->join('booking as bk', 'bk.id', '=', 'p.booking_id')
            ->join('room as rm', 'rm.id', '=', 'bk.room_id')
            ->join('branch_dorm as b', 'b.id', '=', 'rm.branch_dorm_id')
            ->where('b.owner_id', $ownerId)
            ->where('p.status', 'paid')
            ->whereNotNull('p.paid_at')
            ->when(
                $branchId !== null,
                fn ($q) => $q->where('b.id', $branchId)
            );

        $this->applyPeriodFilter(
            $summaryBase,
            'p.paid_at',
            $month,
            $year
        );

        $summary = [
            'transactions' => (clone $summaryBase)->count(),
            'amount' => (float) (clone $summaryBase)->sum('p.amount'),
        ];

        $payments = $baseQuery->select(
            'p.id',
            'p.payment_code',
            'p.amount',
            'p.payment_method',
            'p.status',
            'p.paid_at',
            'p.created_at',
            'p.expires_at',
            'bk.booking_code',
            'rm.room_code',
            'b.name as branch_name',
            'u.fname',
            'u.lname'
        )
            ->orderByRaw('COALESCE(p.paid_at, p.created_at) DESC')
            ->orderByDesc('p.id')
            ->paginate(15)
            ->withQueryString();

        return view('pages.owner.payments.index', compact(
            'payments',
            'summary',
            'branches',
            'branchId',
            'month',
            'year',
            'status',
            'search'
        ));
    }
}
