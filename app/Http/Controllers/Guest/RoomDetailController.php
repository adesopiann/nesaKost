<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\RoomAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomDetailController extends Controller
{
    /**
     * Menampilkan detail kamar aktif beserta hasil pemeriksaan periode sewa.
     */
    public function show(
        Request $request,
        string $roomCode,
        RoomAvailabilityService $availabilityService
    ): View {
        $room = Room::query()
            ->where('room_code', $roomCode)
            ->where('status', config('ekost.room_active_status', 'active'))
            ->whereHas('branchDorm', function ($query) {
                $query->where(
                    'status',
                    config('ekost.property_active_status', 'active')
                );
            })
            ->with([
                'images' => fn ($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('id'),
                'facilities' => fn ($query) => $query
                    ->where('facility.facility_type', 'room')
                    ->orderBy('facility.facility_name'),
                'branchDorm' => fn ($query) => $query->with([
                    'facilities' => fn ($facilityQuery) => $facilityQuery
                        ->where('facility.facility_type', 'branch')
                        ->orderBy('facility.facility_name'),
                ]),
            ])
            ->firstOrFail();

        $selectedStartDate = null;
        $selectedDurationMonth = null;
        $availabilityEndDate = null;
        $availabilityCheck = null;

        if ($request->filled('start_date') || $request->filled('duration_month')) {
            $validated = $request->validate([
                'start_date' => ['required', 'date', 'after_or_equal:today'],
                'duration_month' => ['required', 'integer', 'in:1,3,6'],
            ]);

            $startDate = CarbonImmutable::parse($validated['start_date'])->startOfDay();
            $duration = (int) $validated['duration_month'];
            $endDate = $startDate->addMonthsNoOverflow($duration)->subDay();

            $selectedStartDate = $startDate->toDateString();
            $selectedDurationMonth = $duration;
            $availabilityEndDate = $endDate->toDateString();

            $availabilityCheck = $availabilityService->checkPeriod(
                $room,
                $startDate,
                $endDate
            );
        }

        return view('pages.guest.rooms.show', compact(
            'room',
            'selectedStartDate',
            'selectedDurationMonth',
            'availabilityEndDate',
            'availabilityCheck'
        ));
    }
}
