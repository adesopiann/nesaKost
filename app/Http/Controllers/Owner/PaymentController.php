<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
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
                $branches->contains(fn ($branch) => (string) $branch->id === (string) $branchId),
                404
            );
            $branchId = (int) $branchId;
        } else {
            $branchId = null;
        }

        return [$ownerId, $branches, $branchId];
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,' . (now()->year + 1)],
            'status' => ['nullable', 'string', 'max:30'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return [
            filled($validated['month'] ?? null) ? (int) $validated['month'] : null,
            filled($validated['year'] ?? null) ? (int) $validated['year'] : null,
            $validated['status'] ?? 'all',
            trim($validated['q'] ?? ''),
        ];
    }

    private function applyPeriodFilter($query, string $column, ?int $month, ?int $year)
    {
        return $query
            ->when($month !== null, fn ($q) => $q->whereMonth($column, $month))
            ->when($year !== null, fn ($q) => $q->whereYear($column, $year));
    }

    public function index(Request $request): View
    {
        [$ownerId, $branches, $branchId] = $this->ownerContext($request);

        [$month, $year, $status, $search] = $this->validatedFilters($request);

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
