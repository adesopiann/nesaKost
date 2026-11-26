<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\BranchDorm;
use App\Models\Room;
use App\Services\RoomAvailabilityService;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    /**
     * Data rentang tanggal tidak tersedia untuk kalender detail kamar.
     */
    public function availability(
        Request $request,
        string $roomCode,
        RoomAvailabilityService $availabilityService
    ) {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $room = Room::query()
            ->where('status', config('ekost.room_active_status', 'active'))
            ->whereHas('branchDorm', fn ($query) => $query->where(
                'status',
                config('ekost.property_active_status', 'active')
            ))
            ->where('room_code', $roomCode)
            ->firstOrFail();

        return response()->json(
            $availabilityService->getUnavailableRanges(
                $room,
                $validated['from'],
                $validated['to']
            )
        );
    }

    public function show(string $property, RoomAvailabilityService $availabilityService)
    {
        $property = BranchDorm::query()
            ->where('status', config('ekost.property_active_status', 'active'))
            ->with([
                'facilities',
                'rooms' => fn ($query) => $query
                    ->where('status', config('ekost.room_active_status', 'active')),
            ])
            ->where('slug', $property)
            ->firstOrFail();

        foreach ($property->rooms as $room) {
            $room->availability = $availabilityService->getCurrentStatus($room);
        }

        return view('pages.guest.properties.detail', compact('property'));
    }
}
