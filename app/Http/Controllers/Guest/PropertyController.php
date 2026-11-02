<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\BranchDorm;
use App\Services\RoomAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function show(
        Request $request,
        int $id,
        RoomAvailabilityService $availabilityService
    ) {
        $property = BranchDorm::query()
            ->where(
                'status',
                config('ekost.property_active_status', 'active')
            )
            ->with([
                'facilities',
                'rooms' => fn ($query) => $query
                    ->where(
                        'status',
                        config('ekost.room_active_status', 'active')
                    ),
            ])
            ->findOrFail($id);

        // Status kamar berdasarkan kondisi hari ini.
        foreach ($property->rooms as $room) {
            $room->availability = $availabilityService
                ->getCurrentStatus($room);
        }

        // Nilai awal pengecekan periode.
        $selectedRoomId = null;
        $selectedStartDate = null;
        $selectedDurationMonth = null;
        $availabilityEndDate = null;
        $availabilityCheck = null;

        // Jalankan pengecekan jika pengunjung memilih kamar.
        if ($request->filled('room_id')) {
            $validated = $request->validate([
                'room_id' => ['required', 'integer'],
                'start_date' => [
                    'required',
                    'date',
                    'after_or_equal:today',
                ],
                'duration_month' => [
                    'required',
                    'integer',
                    'in:1,3,6',
                ],
            ]);

            $selectedRoom = $property->rooms->firstWhere(
                'id',
                (int) $validated['room_id']
            );

            abort_if(!$selectedRoom, 404);

            $selectedRoomId = $selectedRoom->id;
            $selectedStartDate = $validated['start_date'];
            $selectedDurationMonth = (int) $validated['duration_month'];

            // Hitung tanggal akhir berdasarkan durasi.
            $startDate = CarbonImmutable::parse($selectedStartDate);

            $endDate = $startDate
                ->addMonthsNoOverflow($selectedDurationMonth)
                ->subDay();

            $availabilityEndDate = $endDate->toDateString();

            // Periksa ketersediaan periode melalui Availability Engine.
            $availabilityCheck = $availabilityService->checkPeriod(
                $selectedRoom,
                $startDate,
                $endDate
            );
        }

        return view(
            'pages.guest.properties.detail',
            compact(
                'property',
                'selectedRoomId',
                'selectedStartDate',
                'selectedDurationMonth',
                'availabilityEndDate',
                'availabilityCheck'
            )
        );
    }
}
