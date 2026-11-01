<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BranchDorm;
use App\Models\Facility;
use App\Models\Owner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BranchDormController extends Controller
{
    /**
     * Resolve the owner profile belonging to the authenticated account.
     * This prevents an owner from reading or editing another owner's branches.
     */
    private function currentOwner(): Owner
    {
        return Owner::where('user_id', Auth::id())->firstOrFail();
    }

    private function ownedBranchQuery()
    {
        return BranchDorm::query()
            ->where('owner_id', $this->currentOwner()->id);
    }

    public function index(Request $request): View
    {
        $owner = $this->currentOwner();
        $search = trim((string) $request->query('q', ''));

        $branchesQuery = BranchDorm::query()
            ->where('owner_id', $owner->id)
            ->with(['facilities', 'rooms'])
            ->withCount('rooms')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT('CBG-', LPAD(id, 3, '0')) LIKE ?", ["%{$search}%"]);
                });
            })
            ->latest('id');

        $branches = $branchesQuery->paginate(8)->withQueryString();

        $branchIds = BranchDorm::where('owner_id', $owner->id)->pluck('id');
        $totalBranches = $branchIds->count();
        $totalRooms = DB::table('room')->whereIn('branch_dorm_id', $branchIds)->count();
        $occupiedRooms = DB::table('room')
            ->whereIn('branch_dorm_id', $branchIds)
            ->whereIn('status', ['occupied', 'terisi'])
            ->count();
        $facilityCount = DB::table('branch_facility')
            ->whereIn('branch_dorm_id', $branchIds)
            ->count();

        $occupancy = $totalRooms > 0 ? (int) round(($occupiedRooms / $totalRooms) * 100) : 0;

        return view('pages.owner.branches.index', compact(
            'branches',
            'search',
            'totalBranches',
            'totalRooms',
            'occupiedRooms',
            'facilityCount',
            'occupancy'
        ));
    }

    public function show(BranchDorm $branch): View
    {
        $this->authorizeBranch($branch);

        $branch->load(['owner.user', 'facilities', 'rooms']);
        $totalRooms = $branch->rooms->count();
        $occupiedRooms = $branch->rooms->whereIn('status', ['occupied', 'terisi'])->count();
        $availableRooms = $branch->rooms->whereIn('status', ['available', 'kosong'])->count();

        return view('pages.owner.branches.show', compact(
            'branch',
            'totalRooms',
            'occupiedRooms',
            'availableRooms'
        ));
    }

    public function create(): View
    {
        $owner = $this->currentOwner();
        $facilities = Facility::orderBy('facility_name')->get();

        return view('pages.owner.branches.create', compact('owner', 'facilities'));
    }

        private function branchRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:5000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'gender_type' => ['required', Rule::in(['putra', 'putri', 'campuran'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['integer', 'distinct', 'exists:facility,id'],
        ];
    }

    private function branchMessages(): array
    {
        return [
            'latitude.required' => 'Tentukan titik lokasi kost pada peta.',
            'longitude.required' => 'Tentukan titik lokasi kost pada peta.',
            'gender_type.required' => 'Pilih kategori kost.',
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->branchRules(), $this->branchMessages());
        $owner = $this->currentOwner();


        $branch = DB::transaction(function () use ($validated, $owner) {
            $branch = BranchDorm::create([
                'owner_id' => $owner->id,
                'name' => $validated['name'],
                'address' => $validated['address'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'gender_type' => $validated['gender_type'],
                'status' => $validated['status'],
            ]);

            $branch->facilities()->sync($validated['facilities'] ?? []);

            return $branch;
        });

        return redirect()
            ->route('owner.branches.show', $branch)
            ->with('success', 'Cabang kos berhasil ditambahkan.');
    }

    public function edit(BranchDorm $branch): View
    {
        $this->authorizeBranch($branch);

        $branch->load(['owner.user', 'facilities', 'rooms']);
        $facilities = Facility::orderBy('facility_name')->get();
        $selectedFacilities = $branch->facilities->pluck('id')->all();
        $totalRooms = $branch->rooms->count();
        $occupiedRooms = $branch->rooms->whereIn('status', ['occupied', 'terisi'])->count();

        return view('pages.owner.branches.edit', compact(
            'branch',
            'facilities',
            'selectedFacilities',
            'totalRooms',
            'occupiedRooms'
        ));
    }

    public function update(Request $request, BranchDorm $branch): RedirectResponse
    {
        $this->authorizeBranch($branch);

        $validated = $request->validate($this->branchRules(), $this->branchMessages());

        DB::transaction(function () use ($validated, $branch) {
            $branch->update([
                'name' => $validated['name'],
                'address' => $validated['address'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'gender_type' => $validated['gender_type'],
                'status' => $validated['status'],
            ]);

            $branch->facilities()->sync($validated['facilities'] ?? []);
        });

        return redirect()
            ->route('owner.branches.show', $branch)
            ->with('success', 'Perubahan cabang kos berhasil disimpan.');
    }

     
    /**
     * Dynamic geocoding with automatic fallback.
     */
    
    public function geocode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $queries = $this->buildGeocodingQueries($validated['q']);

        try {
            foreach ($queries as $index => $query) {

                // Jeda antarpermintaan ke Nominatim.
                if ($index > 0) {
                    usleep(1_100_000);
                }

                $results = $this->searchNominatim($query);

                if (!empty($results)) {
                    return response()->json([
                        'results' => $results,
                        'matched_query' => $query,
                        'fallback_used' => $index > 0,
                        'search_attempt' => $index + 1,
                    ]);
                }
            }

            return response()->json([
               'results' => [],
                'matched_query' => null,
                'fallback_used' => true,
                'search_attempt' => count($queries),
                'attempted_queries' => $queries,
                'message' => 'Alamat tidak ditemukan. Silakan tentukan lokasi secara manual melalui peta.',
            ]);

        } catch (ConnectionException $e) {
            Log::warning('Nominatim connection failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Layanan pencarian lokasi tidak dapat dihubungi.',
            ], 503);

        } catch (\RuntimeException $e) {
            Log::warning('Nominatim geocoding failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi gangguan pada layanan pencarian lokasi.',
            ], 502);
        }
    }


    /**
     * Generate original and simplified address queries.
     */
    
    private function buildGeocodingQueries(string $address): array
    {
        // Tahap 1: alamat asli.
        $original = preg_replace(
            '/\s+/u',
            ' ',
            trim($address)
        );

        // Normalisasi singkatan jalan.
        $original = preg_replace(
            '/\b(?:Jl\.?|Jln\.?)\s*/iu',
            'Jalan ',
            $original
        );

        // Tahap 2: hilangkan nomor bangunan.
        $withoutNumber = preg_replace(
            '/\b(?:no\.?|nomor)\s*[:.]?\s*[A-Z0-9][A-Z0-9\/.-]*/iu',
            '',
            $original
        );

        $withoutNumber = preg_replace(
            '/#\s*[A-Z0-9][A-Z0-9\/.-]*/u',
            '',
            $withoutNumber
        );

        // Tahap 3: sederhanakan alamat administratif.
        $simplified = preg_replace(
            '/\b(?:kelurahan|kel\.?|desa|kecamatan|kec\.?)\s+/iu',
            '',
            $withoutNumber
        );

        // Hilangkan kode pos lima digit.
        $simplified = preg_replace(
            '/\b\d{5}\b/u',
            '',
            $simplified
        );

        // Rapikan tanda baca dan spasi.
        $simplified = preg_replace(
            '/\s*,\s*/u',
            ', ',
            $simplified
        );

        $simplified = preg_replace(
            '/,{2,}/u',
            ',',
            $simplified
        );

        $simplified = trim(
            $simplified,
            " \t\n\r\0\x0B,"
        );

        $simplified = preg_replace(
            '/,\s*Indonesia\b/iu',
            '',
            $simplified
        );

        // Tambahkan provinsi sebelum negara.
        if (!preg_match('/\bJawa Timur\b/iu', $simplified)) {
            $simplified .= ', Jawa Timur';
        }

        // Tambahkan negara di akhir.
        $simplified .= ', Indonesia';


        return array_values(array_unique([
            $original,
            trim($withoutNumber, " \t\n\r\0\x0B,"),
            $simplified,
        ]));
    }



private function searchNominatim(string $query): array
{
    // Gunakan versi cache baru agar tidak mengambil cache kosong lama.
    $cacheKey = 'ekost:nominatim:v2:' . sha1(
        mb_strtolower(trim($query))
    );

    // Gunakan cache hanya jika sebelumnya ada hasil ditemukan.
    $cachedResults = Cache::get($cacheKey);

    if (is_array($cachedResults) && !empty($cachedResults)) {
        return $cachedResults;
    }

    $contact = config('ekost.geocoding.contact');

    $userAgent = config(
        'ekost.geocoding.user_agent',
        config('app.name', 'nesaKost')
            . ' (' . config('app.url', 'http://localhost') . ')'
    );

    if ($contact) {
        $userAgent .= '; contact: ' . $contact;
    }

    $response = Http::acceptJson()
        ->withHeaders([
            'User-Agent' => $userAgent,
        ])
        ->timeout(15)
        ->get(
            'https://nominatim.openstreetmap.org/search',
            [
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => 5,
                'countrycodes' => 'id',
                'addressdetails' => 1,
            ]
        );

    if (!$response->successful()) {
        throw new \RuntimeException(
            'Nominatim HTTP status: ' . $response->status()
        );
    }

    $results = collect($response->json())
        ->map(fn ($item) => [
            'display_name' => $item['display_name']
                ?? 'Lokasi ditemukan',
            'latitude' => $item['lat'] ?? null,
            'longitude' => $item['lon'] ?? null,
        ])
        ->filter(fn ($item) =>
            is_numeric($item['latitude'])
            && is_numeric($item['longitude'])
        )
        ->values()
        ->all();

    // Hanya cache hasil yang benar-benar ditemukan.
    if (!empty($results)) {
        Cache::put(
            $cacheKey,
            $results,
            now()->addDays(30)
        );
    }

    return $results;
}



    public function destroy(BranchDorm $branch): RedirectResponse
    {
        $this->authorizeBranch($branch);

        if ($branch->rooms()->exists()) {
            return back()->with('error', 'Cabang tidak dapat dihapus karena masih memiliki data kamar. Nonaktifkan cabang jika sudah tidak beroperasi.');
        }

        if (DB::table('booking')->whereIn(
            'room_id',
            DB::table('room')->select('id')->where('branch_dorm_id', $branch->id)
        )->exists()) {
            return back()->with('error', 'Cabang memiliki riwayat transaksi dan tidak dapat dihapus.');
        }

        DB::transaction(function () use ($branch) {
            $branch->facilities()->detach();
            DB::table('manager_branch')->where('branch_dorm_id', $branch->id)->delete();
            $branch->delete();
        });

        return redirect()
            ->route('owner.branches.index')
            ->with('success', 'Cabang kos berhasil dihapus.');
    }

    private function authorizeBranch(BranchDorm $branch): void
    {
        abort_unless(
            (int) $branch->owner_id === (int) $this->currentOwner()->id,
            404
        );
    }
}
