<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BranchDorm;
use App\Models\Room;
use App\Models\RoomImage;
use App\Services\RoomAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoomController extends Controller
{
    /**
     * Cabang yang dimiliki owner yang sedang login.
     */
    private function ownerBranches(Request $request)
    {
        return BranchDorm::query()
            ->whereHas('owner', fn ($query) =>
                $query->where('user_id', $request->user()->id)
            );
    }

    /**
     * Mengambil kamar yang berada di cabang milik owner.
     */
    private function ownedRoom(Request $request, int $id): Room
    {
        return Room::query()
            ->whereHas('branch', function ($query) use ($request) {
                $query->whereIn(
                    'id',
                    $this->ownerBranches($request)->select('id')
                );
            })
            ->with(['branch', 'images', 'firstImage'])
            ->findOrFail($id);
    }

    /**
     * Daftar kamar dan statistik ketersediaan.
     */
    public function index(
        Request $request,
        RoomAvailabilityService $availabilityService
    ): View {
        $search = trim((string) $request->query('q', ''));
        $branchId = $request->query('branch');

        $branches = $this->ownerBranches($request)
            ->orderBy('name')
            ->get();

        $ownedBranchIds = $branches->pluck('id');

        // Pastikan filter cabang benar-benar milik owner.
        if ($branchId !== null && $branchId !== '') {
            abort_unless(
                $branches->contains('id', (int) $branchId),
                404
            );
        }

        $roomsQuery = Room::query()
            ->with(['branch', 'images', 'firstImage'])
            ->whereIn('branch_dorm_id', $ownedBranchIds);

        if ($search !== '') {
            $roomsQuery->where(function ($query) use ($search) {
                $query->where('room_code', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('branch', fn ($branchQuery) =>
                        $branchQuery->where('name', 'like', "%{$search}%")
                    );
            });
        }

        if ($branchId !== null && $branchId !== '') {
            $roomsQuery->where('branch_dorm_id', (int) $branchId);
        }

        $rooms = $roomsQuery
            ->latest('room.id')
            ->paginate(10)
            ->withQueryString();

        // Hitung ketersediaan kamar pada halaman yang sedang ditampilkan.
        foreach ($rooms as $room) {
            $room->setAttribute(
                'availability',
                $availabilityService->getCurrentStatus($room)
            );
        }

        // Statistik dihitung untuk seluruh kamar milik owner,
        // bukan hanya kamar pada halaman pagination atau filter.
        $allRooms = Room::query()
            ->whereIn('branch_dorm_id', $ownedBranchIds)
            ->get();

        $totalRooms = $allRooms->count();
        $availableRooms = 0;
        $occupiedRooms = 0;

        foreach ($allRooms as $room) {
            $availability = $availabilityService->getCurrentStatus($room);

            if ($availability['status'] === 'available') {
                $availableRooms++;
            }

            if ($availability['status'] === 'occupied') {
                $occupiedRooms++;
            }
        }

        return view('pages.owner.rooms.index', compact(
            'rooms',
            'branches',
            'search',
            'branchId',
            'totalRooms',
            'availableRooms',
            'occupiedRooms'
        ));
    }

    /**
     * Form tambah kamar.
     */
    public function create(Request $request): View
    {
        $branches = $this->ownerBranches($request)
            ->orderBy('name')
            ->get();

        return view('pages.owner.rooms.create', compact('branches'));
    }

    /**
     * Simpan kamar baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'room_code' => [
                'required',
                'string',
                'max:20',
                'unique:room,room_code',
            ],
            'branch_dorm_id' => [
                'required',
                'integer',
                Rule::exists('branch_dorm', 'id')
                    ->where(fn ($query) =>
                        $query->whereIn(
                            'id',
                            $this->ownerBranches($request)->select('id')
                        )
                    ),
            ],
            'type' => ['required', 'string', 'max:50'],
            'monthly_price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'description' => ['nullable', 'string'],
            'status' => [
                'required',
                Rule::in(['active', 'inactive', 'maintenance']),
            ],
        ], [
            'room_code.unique' => 'Kode kamar sudah digunakan.',
            'branch_dorm_id.exists' => 'Cabang tidak valid atau bukan milik akun Anda.',
            'images.max' => 'Maksimal 10 foto untuk satu kali unggah.',
            'images.*.max' => 'Setiap foto maksimal 5 MB.',
            'status.in' => 'Status kamar harus aktif, nonaktif, atau perawatan.',
        ]);

        $uploadedImages = $request->file('images', []);
        unset($validated['images']);

        DB::transaction(function () use ($validated, $uploadedImages) {
            $room = Room::create($validated);

            foreach ($uploadedImages as $index => $image) {
                $path = $image->store('rooms', 'public');

                RoomImage::create([
                    'room_id' => $room->id,
                    'image_path' => $path,
                    'is_primary' => $index === 0,
                ]);

                // image_path tetap dipertahankan untuk kompatibilitas.
                if ($index === 0) {
                    $room->update(['image_path' => $path]);
                }
            }
        });

        return redirect()
            ->route('owner.rooms.index')
            ->with('success', 'Data kamar berhasil ditambahkan.');
    }

    /**
     * Detail kamar.
     */
    public function show(Request $request, int $room): View
    {
        $room = $this->ownedRoom($request, $room);

        return view('pages.owner.rooms.show', compact('room'));
    }

    /**
     * Form edit kamar.
     */
    public function edit(Request $request, int $room): View
    {
        $room = $this->ownedRoom($request, $room);

        $branches = $this->ownerBranches($request)
            ->orderBy('name')
            ->get();

        return view('pages.owner.rooms.edit', compact('room', 'branches'));
    }

    /**
     * Perbarui kamar.
     */
    public function update(Request $request, int $room): RedirectResponse
    {
        $room = $this->ownedRoom($request, $room);

        $validated = $request->validate([
            'room_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('room', 'room_code')->ignore($room->id),
            ],
            'branch_dorm_id' => [
                'required',
                'integer',
                Rule::exists('branch_dorm', 'id')
                    ->where(fn ($query) =>
                        $query->whereIn(
                            'id',
                            $this->ownerBranches($request)->select('id')
                        )
                    ),
            ],
            'type' => ['required', 'string', 'max:50'],
            'monthly_price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'description' => ['nullable', 'string'],
            'status' => [
                'required',
                Rule::in(['active', 'inactive', 'maintenance']),
            ],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => ['integer'],
        ], [
            'room_code.unique' => 'Kode kamar sudah digunakan.',
            'branch_dorm_id.exists' => 'Cabang tidak valid atau bukan milik akun Anda.',
            'images.max' => 'Maksimal 10 foto untuk satu kali unggah.',
            'images.*.max' => 'Setiap foto maksimal 5 MB.',
            'status.in' => 'Status kamar harus aktif, nonaktif, atau perawatan.',
        ]);

        $uploadedImages = $request->file('images', []);

        $deleteImageIds = collect($validated['delete_images'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->all();

        unset($validated['images'], $validated['delete_images']);

        DB::transaction(function () use (
            $room,
            $validated,
            $uploadedImages,
            $deleteImageIds
        ) {
            $room->update($validated);

            // Hapus gambar yang dipilih.
            if (!empty($deleteImageIds)) {
                $imagesToDelete = $room->images()
                    ->whereIn('id', $deleteImageIds)
                    ->get();

                foreach ($imagesToDelete as $image) {
                    Storage::disk('public')->delete($image->image_path);
                    $image->delete();
                }
            }

            // Tambahkan gambar baru.
            foreach ($uploadedImages as $image) {
                RoomImage::create([
                    'room_id' => $room->id,
                    'image_path' => $image->store('rooms', 'public'),
                    'is_primary' => false,
                ]);
            }

            // Pastikan hanya satu gambar utama.
            $images = $room->images()
                ->orderBy('id')
                ->get();

            $primary = $images->firstWhere('is_primary', true)
                ?? $images->first();

            $room->images()->update(['is_primary' => false]);

            if ($primary) {
                $primary->update(['is_primary' => true]);

                $room->update([
                    'image_path' => $primary->image_path,
                ]);
            } else {
                $room->update(['image_path' => null]);
            }
        });

        return redirect()
            ->route('owner.rooms.show', $room->id)
            ->with('success', 'Data kamar berhasil diperbarui.');
    }

    /**
     * Hapus kamar jika belum memiliki riwayat transaksi.
     */
    public function destroy(Request $request, int $room): RedirectResponse
    {
        $room = $this->ownedRoom($request, $room);

        $hasBooking = DB::table('booking')
            ->where('room_id', $room->id)
            ->exists();

        $hasRent = DB::table('rent')
            ->where('room_id', $room->id)
            ->exists();

        if ($hasBooking || $hasRent) {
            return redirect()
                ->route('owner.rooms.index')
                ->with(
                    'error',
                    'Kamar tidak dapat dihapus karena sudah terhubung dengan booking atau penyewaan. Ubah statusnya menjadi nonaktif jika tidak digunakan.'
                );
        }

        // Hapus setiap path satu kali, termasuk fallback image_path.
        $imagePaths = $room->images
            ->pluck('image_path')
            ->push($room->image_path)
            ->filter()
            ->unique();

        foreach ($imagePaths as $path) {
            Storage::disk('public')->delete($path);
        }

        $room->delete();

        return redirect()
            ->route('owner.rooms.index')
            ->with('success', 'Data kamar berhasil dihapus.');
    }
}
