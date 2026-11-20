<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MyRoomController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        abort_unless($tenant, 403, 'Data tenant tidak ditemukan.');

        $rents = $tenant->rents()
            ->with([
                'room.images' => fn ($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('id'),
                'room.facilities',
                'room.branchDorm.facilities',
                'booking',
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $activeRents = $rents
            ->where('status', 'active')
            ->sortByDesc('start_date')
            ->values();

        $upcomingRents = $rents
            ->where('status', 'scheduled')
            ->sortBy('start_date')
            ->values();

        $historicalRents = $rents
            ->whereIn('status', ['completed', 'cancelled'])
            ->sortByDesc('end_date')
            ->values();

        return view('pages.tenant.kamar-saya.index', compact(
            'activeRents',
            'upcomingRents',
            'historicalRents'
        ));
    }
}
