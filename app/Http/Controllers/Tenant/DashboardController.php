<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Mengambil data tenant berdasarkan akun yang login.
        $tenant = $user->tenant;

        if (!$tenant) {
            abort(403, 'Data tenant tidak ditemukan.');
        }

        // Mengambil seluruh data penyewaan tenant.
        $rents = $tenant->rents()
            ->with([
                'room.branchDorm',
                'booking.payments',
            ])
            ->latest()
            ->get();

        // Penyewaan yang sedang aktif.
        $activeRent = $rents->firstWhere('status', 'active');

        // Penyewaan yang sudah terjadwal.
        $scheduledRent = $rents
            ->where('status', 'scheduled')
            ->sortBy('start_date')
            ->first();

        // Prioritaskan penyewaan aktif untuk ditampilkan.
        $currentRent = $activeRent ?? $scheduledRent;

        // Mengambil pengajuan renewal yang berkaitan dengan rent yang sedang
        // ditampilkan. Relasi ini dipakai untuk menampilkan status dan detail
        // rent baru setelah pembayaran renewal berhasil.
        $renewalBooking = null;

        if ($currentRent) {
            $renewalBooking = Booking::query()
                ->where('tenant_id', $tenant->id)
                ->where('booking_type', 'renewal')
                ->where(function ($query) use ($currentRent) {
                    $query->where('previous_rent_id', $currentRent->id)
                        ->orWhere('id', $currentRent->booking_id);
                })
                ->with(['rent', 'payments'])
                ->latest('id')
                ->first();
        }

        // Status informasi renewal untuk ditampilkan pada dashboard tenant.
        // Rentang pengajuan berlaku inklusif dari H-30 sampai H-7.
        $daysUntilRentEnd = null;

        if ($currentRent) {
            $today = CarbonImmutable::today(config('app.timezone'));
            $rentEndDate = CarbonImmutable::parse($currentRent->end_date)->startOfDay();
            $daysUntilRentEnd = (int) $today->diffInDays($rentEndDate, false);
        }

        $renewalPaymentDeadlinePassed = $renewalBooking
            && $renewalBooking->status === 'pending_payment'
            && $renewalBooking->expires_at
            && $renewalBooking->expires_at->isPast();

        $renewalAttemptExpired = $renewalBooking && (
            in_array($renewalBooking->status, ['expired', 'cancelled', 'failed'], true)
            || $renewalPaymentDeadlinePassed
        );

        $renewalWindowOpen = $currentRent
            && $currentRent->status === 'active'
            && $daysUntilRentEnd !== null
            && $daysUntilRentEnd >= 7
            && $daysUntilRentEnd <= 30
            && !$renewalBooking;

        $renewalWindowClosed = $currentRent
            && $currentRent->status === 'active'
            && $daysUntilRentEnd !== null
            && $daysUntilRentEnd >= 0
            && $daysUntilRentEnd <= 6
            && !$renewalBooking;

        // Menghitung seluruh pembayaran berhasil milik tenant.
        $paidTotal = Payment::whereHas('booking', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })
            ->where('status', 'paid')
            ->sum('amount');

        // Mengambil lima pembayaran terbaru.
        $recentPayments = Payment::with([
                'booking.room.branchDorm',
            ])
            ->whereHas('booking', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })
            ->latest()
            ->take(5)
            ->get();

        return view('pages.tenant.dashboard.index', compact(
            'user',
            'tenant',
            'rents',
            'activeRent',
            'scheduledRent',
            'currentRent',
            'renewalBooking',
            'daysUntilRentEnd',
            'renewalPaymentDeadlinePassed',
            'renewalAttemptExpired',
            'renewalWindowOpen',
            'renewalWindowClosed',
            'paidTotal',
            'recentPayments'
        ));
    }
}
