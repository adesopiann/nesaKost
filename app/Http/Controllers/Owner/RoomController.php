<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BranchDorm;
use App\Models\Room;
use App\Models\RoomImage;
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
     * Pola relasi mengikuti modul cabang: branch->owner->user.
     */
    private function ownerBranches(Request $request)
    {
        $user = $request->user();

        return BranchDorm::query()
            ->whereHas('owner', fn ($q) => $q->where('user_id', $user->id));
    }

    private function ownedRoom(Request $request, int $id): Room
    {
        return Room::query()
            ->whereHas('branch', function ($q) use ($request) {
                $q->whereIn('id', $this->ownerBranches($request)->select('id'));
            })
            ->with(['branch', 'images'])
            ->findOrFail($id);
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $branchId = $request->query('branch');

        $branches = $this->ownerBranches($request)->orderBy('name')->get();

        $roomsQuery = Room::query()
            ->with(['branch', 'images'])
            ->whereIn('branch_dorm_id', $branches->pluck('id'));

        if ($search !== '') {
            $roomsQuery->where(function ($q) use ($search) {
                $q->where('room_code', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($branchId !== null && $branchId !== '') {
            $roomsQuery->where('branch_dorm_id', $branchId);
        }

        $rooms = $roomsQuery->latest('room.id')->paginate(10)->withQueryString();
        $totalRooms = Room::whereIn('branch_dorm_id', $branches->pluck('id'))->count();
        $availableRooms = Room::whereIn('branch_dorm_id', $branches->pluck('id'))->where('status', 'available')->count();
        $occupiedRooms = Room::whereIn('branch_dorm_id', $branches->pluck('id'))->where('status', 'occupied')->count();

        return view('pages.owner.rooms.index', compact(
            'rooms', 'branches', 'search', 'branchId', 'totalRooms', 'availableRooms', 'occupiedRooms'
        ));
    }

    public function create(Request $request): View
    {
        $branches = $this->ownerBranches($request)->orderBy('name')->get();

        return view('pages.owner.rooms.create', compact('branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'room_code' => ['required', 'string', 'max:20', 'unique:room,room_code'],
            'branch_dorm_id' => [
                'required', 'integer',
                Rule::exists('branch_dorm', 'id')->where(fn ($q) => $q->whereIn('id', $this->ownerBranches($request)->select('id'))),
            ],
            'type' => ['required', 'string', 'max:50'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['available', 'occupied', 'maintenance', 'inactive'])],
        ], [
            'room_code.unique' => 'Kode kamar sudah digunakan.',
            'branch_dorm_id.exists' => 'Cabang tidak valid atau bukan milik akun Anda.',
            'images.max' => 'Maksimal 10 foto untuk satu kali unggah.',
            'images.*.max' => 'Setiap foto maksimal 5 MB.',
        ]);

        $uploadedImages = $request->file('images', []);
        unset($validated['images']);

        $room = DB::transaction(function () use ($validated, $uploadedImages) {
            $room = Room::create($validated);

            foreach ($uploadedImages as $index => $image) {
                $path = $image->store('rooms', 'public');

                RoomImage::create([
                    'room_id' => $room->id,
                    'image_path' => $path,
                    'is_primary' => $index === 0,
                ]);

                // Pertahankan image_path lama sebagai fallback untuk bagian aplikasi yang masih memakainya.
                if ($index === 0) {
                    $room->update(['image_path' => $path]);
                }
            }

            return $room;
        });

        return redirect()->route('owner.rooms.index')->with('success', 'Data kamar berhasil ditambahkan.');
    }

    public function show(Request $request, int $room): View
    {
        $room = $this->ownedRoom($request, $room);

        return view('pages.owner.rooms.show', compact('room'));
    }

    public function edit(Request $request, int $room): View
    {
        $room = $this->ownedRoom($request, $room);
        $branches = $this->ownerBranches($request)->orderBy('name')->get();

        return view('pages.owner.rooms.edit', compact('room', 'branches'));
    }

    public function update(Request $request, int $room): RedirectResponse
    {
        $room = $this->ownedRoom($request, $room);

        $validated = $request->validate([
            'room_code' => ['required', 'string', 'max:20', Rule::unique('room', 'room_code')->ignore($room->id)],
            'branch_dorm_id' => [
                'required', 'integer',
                Rule::exists('branch_dorm', 'id')->where(fn ($q) => $q->whereIn('id', $this->ownerBranches($request)->select('id'))),
            ],
            'type' => ['required', 'string', 'max:50'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['available', 'occupied', 'maintenance', 'inactive'])],
        ], [
            'room_code.unique' => 'Kode kamar sudah digunakan.',
            'branch_dorm_id.exists' => 'Cabang tidak valid atau bukan milik akun Anda.',
            'images.max' => 'Maksimal 10 foto untuk satu kali unggah.',
            'images.*.max' => 'Setiap foto maksimal 5 MB.',
        ]);

        $uploadedImages = $request->file('images', []);
        $deleteImageIds = collect($request->input('delete_images', []))
            ->map(fn ($id) => (int) $id)
            ->all();

        unset($validated['images']);

        DB::transaction(function () use ($room, $validated, $uploadedImages, $deleteImageIds) {
            $room->update($validated);

            if (!empty($deleteImageIds)) {
                $imagesToDelete = $room->images()->whereIn('id', $deleteImageIds)->get();

                foreach ($imagesToDelete as $image) {
                    Storage::disk('public')->delete($image->image_path);
                    $image->delete();
                }
            }

            foreach ($uploadedImages as $image) {
                RoomImage::create([
                    'room_id' => $room->id,
                    'image_path' => $image->store('rooms', 'public'),
                    'is_primary' => false,
                ]);
            }

            $images = $room->images()->orderBy('id')->get();
            $primary = $images->firstWhere('is_primary', true) ?? $images->first();

            if ($primary) {
                $room->images()->update(['is_primary' => false]);
                $primary->update(['is_primary' => true]);
                $room->update(['image_path' => $primary->image_path]);
            } else {
                $room->update(['image_path' => null]);
            }
        });

        return redirect()->route('owner.rooms.show', $room->id)->with('success', 'Data kamar berhasil diperbarui.');
    }

    public function destroy(Request $request, int $room): RedirectResponse
    {
        $room = $this->ownedRoom($request, $room);

        // Hindari menghapus kamar yang telah direferensikan transaksi.
        $hasBooking = DB::table('booking')->where('room_id', $room->id)->exists();
        $hasRent = DB::table('rent')->where('room_id', $room->id)->exists();

        if ($hasBooking || $hasRent) {
            return redirect()->route('owner.rooms.index')
                ->with('error', 'Kamar tidak dapat dihapus karena sudah terhubung dengan booking atau penyewaan. Ubah statusnya menjadi nonaktif jika tidak digunakan.');
        }

        foreach ($room->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }
        if ($room->image_path) {
            Storage::disk('public')->delete($room->image_path);
        }

        $room->delete();

        return redirect()->route('owner.rooms.index')->with('success', 'Data kamar berhasil dihapus.');
    }
}
