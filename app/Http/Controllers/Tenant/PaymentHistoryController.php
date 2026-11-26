<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentHistoryController extends Controller
{
    private const STATUSES = ['pending', 'paid', 'failed', 'expired', 'cancelled'];

    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        abort_if(!$tenant, 403, 'Data tenant tidak ditemukan.');

        $baseQuery = Payment::query()
            ->whereHas('booking', fn ($query) => $query->where('tenant_id', $tenant->id));

        $totalTransactions = (clone $baseQuery)->count();
        $totalPaid = (clone $baseQuery)->where('status', 'paid')->sum('amount');

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:all,pending,paid,failed,expired,cancelled'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        $query = (clone $baseQuery)->with([
            'booking.room.branchDorm',
            'booking.rent',
        ]);

        if (!empty($validated['status']) && $validated['status'] !== 'all') {
            $query->where('status', $validated['status']);
        }

        // Successful transactions use paid_at; non-successful attempts use created_at.
        if ($dateFrom || $dateTo) {
            $query->where(function ($dateQuery) use ($dateFrom, $dateTo) {
                $dateQuery->where(function ($paidQuery) use ($dateFrom, $dateTo) {
                    $paidQuery->where('status', 'paid');
                    if ($dateFrom) $paidQuery->whereDate('paid_at', '>=', $dateFrom);
                    if ($dateTo) $paidQuery->whereDate('paid_at', '<=', $dateTo);
                })->orWhere(function ($attemptQuery) use ($dateFrom, $dateTo) {
                    $attemptQuery->where('status', '!=', 'paid');
                    if ($dateFrom) $attemptQuery->whereDate('created_at', '>=', $dateFrom);
                    if ($dateTo) $attemptQuery->whereDate('created_at', '<=', $dateTo);
                });
            });
        }

        $payments = $query->orderByRaw('COALESCE(paid_at, created_at) DESC')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('pages.tenant.riwayat-pembayaran.index', compact(
            'payments',
            'totalTransactions',
            'totalPaid',
            'validated'
        ));
    }

    public function show(Request $request, string $paymentCode)
    {
        $tenant = $request->user()->tenant;
        abort_if(!$tenant, 403, 'Data tenant tidak ditemukan.');

        $payment = Payment::query()
            ->where('payment_code', $paymentCode)
            ->whereHas('booking', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->with(['booking.room.branchDorm', 'booking.rent'])
            ->firstOrFail();

        return view('pages.tenant.riwayat-pembayaran.show', compact('payment'));
    }
}
