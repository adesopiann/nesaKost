<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
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
            'paidTotal',
            'recentPayments'
        ));
    }
}
