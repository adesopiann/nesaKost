<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BranchDorm;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'in:kampus_1,kampus_2'],
            'gender' => ['nullable', 'in:putra,putri,campuran'],
        ]);

        $campusKey = $validated['campus'] ?? 'kampus_1';
        $campus = config("ekost.campuses.{$campusKey}");

        $latitude = $campus['latitude'] ?? null;
        $longitude = $campus['longitude'] ?? null;
        $hasCoordinates = is_numeric($latitude) && is_numeric($longitude);

        if ($hasCoordinates) {
            $lat = (float) $latitude;
            $lng = (float) $longitude;
            $radius = (float) config('ekost.search_radius', 5);
            $activeStatus = config('ekost.property_active_status', 'active');
            $availableStatus = config('ekost.room_available_status', 'available');

            $distanceSql = <<<'SQL'
                (
                    6371 * ACOS(
                        LEAST(1, GREATEST(-1,
                            COS(RADIANS(?)) *
                            COS(RADIANS(latitude)) *
                            COS(RADIANS(longitude) - RADIANS(?)) +
                            SIN(RADIANS(?)) *
                            SIN(RADIANS(latitude))
                        ))
                    )
                )
            SQL;

            $properties = BranchDorm::query()
                ->select('branch_dorm.*')
                ->selectRaw("$distanceSql AS distance", [$lat, $lng, $lat])
                ->withMin([
                    'rooms as starting_price' => fn ($query) =>
                        $query->where('status', $availableStatus),
                ], 'monthly_price')
                ->withCount([
                    'rooms as available_rooms_count' => fn ($query) =>
                        $query->where('status', $availableStatus),
                ])
                ->where('branch_dorm.status', $activeStatus)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereHas('rooms', fn ($query) =>
                    $query->where('status', $availableStatus)
                )
                ->whereRaw("$distanceSql <= ?", [$lat, $lng, $lat, $radius])
                ->when(
                    !empty($validated['gender']),
                    fn ($query) => $query->where('gender_type', $validated['gender'])
                )
                ->orderBy('distance')
                ->paginate((int) config('ekost.per_page', 9))
                ->withQueryString();
        } else {
            // Tetap gunakan paginator agar Blade dapat memanggil total() dan links().
            $properties = new LengthAwarePaginator(
                new Collection(),
                0,
                (int) config('ekost.per_page', 9),
                LengthAwarePaginator::resolveCurrentPage(),
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );
        }

        return view('customer.home', [
            'properties' => $properties,
            'campus' => $campus,
            'campusKey' => $campusKey,
            'selectedGender' => $validated['gender'] ?? '',
            'hasCoordinates' => $hasCoordinates,
        ]);
    }
}
