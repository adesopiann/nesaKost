<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RentController extends Controller
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
}
