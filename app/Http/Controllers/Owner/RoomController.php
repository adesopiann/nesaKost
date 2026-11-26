<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BranchDorm;
use App\Models\Facility;
use App\Models\Room;
use App\Models\RoomImage;
use App\Services\RoomAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
    private function ownedRoom(Request $request, string $roomCode): Room
    {
        return Room::query()
            ->whereHas('branch', function ($query) use ($request) {
                $query->whereIn(
                    'id',
                    $this->ownerBranches($request)->select('id')
                );
            })
            ->with([
                'branch',
                'images',
                'firstImage',
                'facilities' => fn ($query) => $query->where('facility.facility_type', 'room')->orderBy('facility_name'),
            ])
            ->where('room_code', $roomCode)
            ->firstOrFail();
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
            ->withCount(['bookings', 'rents'])
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

        $facilities = Facility::whereHas('owner', fn ($q) => $q->where('user_id', $request->user()->id))->where('facility_type', 'room')->orderBy('facility_name')->get();
        return view('pages.owner.rooms.create', compact('branches', 'facilities'));
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
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['integer', 'distinct', Rule::exists('facility', 'id')->where(fn ($query) => $query->where('owner_id', DB::table('owners')->where('user_id', $request->user()->id)->value('id'))->where('facility_type', 'room'))],
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
        $facilityIds = $validated['facilities'] ?? [];
        unset($validated['facilities']);

        DB::transaction(function () use ($validated, $uploadedImages, $facilityIds) {
            $room = Room::create($validated);
            $room->facilities()->sync($facilityIds);

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
    public function show(Request $request, string $room): View
    {
        $room = $this->ownedRoom($request, $room);
        [$currentTransactions, $transactionHistory] = $this->roomDetailTransactions($room);

        return view('pages.owner.rooms.show', compact(
            'room',
            'currentTransactions',
            'transactionHistory'
        ));
    }

    /**
     * Form edit kamar.
     */
    public function edit(Request $request, string $room): View
    {
        $room = $this->ownedRoom($request, $room);

        $branches = $this->ownerBranches($request)
            ->orderBy('name')
            ->get();

        $facilities = Facility::whereHas('owner', fn ($q) => $q->where('user_id', $request->user()->id))->where('facility_type', 'room')->orderBy('facility_name')->get();
        $selectedFacilities = $room->facilities->pluck('id')->all();
        return view('pages.owner.rooms.edit', compact('room', 'branches', 'facilities', 'selectedFacilities'));
    }

    /**
     * Ambil hanya transaksi yang masih menghalangi perubahan status:
     * booking confirmed atau pending_payment yang belum kedaluwarsa,
     * serta rent active atau scheduled.
     */
    private function roomBookingAndRentDetails(Room $room): array
    {
        $bookings = DB::table('booking as bk')
            ->leftJoin('tenants as t', 't.id', '=', 'bk.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->where('bk.room_id', $room->id)
            ->whereDate('bk.end_date', '>=', today())
            ->where(function ($query) {
                $query->where('bk.status', 'confirmed')
                    ->orWhere(function ($pending) {
                        $pending->where('bk.status', 'pending_payment')
                            ->where(function ($expiry) {
                                $expiry->whereNull('bk.expires_at')
                                    ->orWhere('bk.expires_at', '>', now());
                            });
                    });
            })
            ->select(
                'bk.id',
                'bk.booking_code as code',
                'bk.start_date',
                'bk.end_date',
                'bk.status',
                'u.fname',
                'u.lname'
            )
            ->orderByDesc('bk.start_date')
            ->get()
            ->map(fn ($booking) => [
                'type' => 'Booking',
                'code' => $booking->code ?: 'BOOKING-' . $booking->id,
                'start_date' => $booking->start_date,
                'end_date' => $booking->end_date,
                'status' => $booking->status,
                'tenant_name' => trim(($booking->fname ?? '') . ' ' . ($booking->lname ?? '')) ?: 'Nama penyewa tidak tersedia',
            ]);

        $rents = DB::table('rent as rt')
            ->leftJoin('tenants as t', 't.id', '=', 'rt.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->where('rt.room_id', $room->id)
            ->whereIn('rt.status', ['active', 'scheduled'])
            ->whereDate('rt.end_date', '>=', today())
            ->select(
                'rt.id',
                'rt.rent_code as code',
                'rt.start_date',
                'rt.end_date',
                'rt.status',
                'u.fname',
                'u.lname'
            )
            ->orderByDesc('rt.start_date')
            ->get()
            ->map(fn ($rent) => [
                'type' => 'Sewa',
                'code' => $rent->code ?: 'RENT-' . $rent->id,
                'start_date' => $rent->start_date,
                'end_date' => $rent->end_date,
                'status' => $rent->status,
                'tenant_name' => trim(($rent->fname ?? '') . ' ' . ($rent->lname ?? '')) ?: 'Nama penyewa tidak tersedia',
            ]);

        return $bookings->concat($rents)
            ->sortByDesc('start_date')
            ->values()
            ->all();
    }

    /**
     * Ambil transaksi kamar untuk halaman detail.
     *
     * Transaksi berjalan:
     * - Booking pending_payment yang belum kedaluwarsa dan periodenya belum lewat.
     * - Rent active/scheduled yang periodenya belum berakhir.
     *
     * Booking confirmed tidak ditampilkan terpisah jika sudah dikonversi
     * menjadi rent, agar transaksi yang sama tidak muncul dua kali.
     *
     * Riwayat:
     * - Booking expired/cancelled.
     * - Rent completed/cancelled.
     * - Transaksi berstatus aktif/terkonfirmasi tetapi tanggal akhirnya lewat.
     */
    private function roomDetailTransactions(Room $room): array
    {
        $latestPayment = DB::table('payment')
            ->selectRaw('booking_id, MAX(id) as latest_payment_id')
            ->groupBy('booking_id');

        $pendingBookings = DB::table('booking as bk')
            ->leftJoin('tenants as t', 't.id', '=', 'bk.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->leftJoinSub($latestPayment, 'latest_payment', function ($join) {
                $join->on('latest_payment.booking_id', '=', 'bk.id');
            })
            ->leftJoin('payment as p', 'p.id', '=', 'latest_payment.latest_payment_id')
            ->where('bk.room_id', $room->id)
            ->where('bk.status', 'pending_payment')
            ->whereDate('bk.end_date', '>=', today())
            ->where(function ($query) {
                $query->whereNull('bk.expires_at')
                    ->orWhere('bk.expires_at', '>', now());
            })
            ->select(
                'bk.id',
                'bk.booking_code as code',
                'bk.start_date',
                'bk.end_date',
                'bk.status',
                'bk.expires_at',
                'u.fname',
                'u.lname',
                'p.payment_code',
                'p.status as payment_status',
                'p.payment_method',
                'p.amount as payment_amount'
            )
            ->get()
            ->map(fn ($booking) => [
                'type' => 'Booking',
                'code' => $booking->code ?: 'BOOKING-' . $booking->id,
                'start_date' => $booking->start_date,
                'end_date' => $booking->end_date,
                'status' => $booking->status,
                'tenant_name' => trim(($booking->fname ?? '') . ' ' . ($booking->lname ?? '')) ?: 'Nama penyewa tidak tersedia',
                'payment_code' => $booking->payment_code,
                'payment_status' => $booking->payment_status,
                'payment_method' => $booking->payment_method,
                'payment_amount' => $booking->payment_amount,
                'expires_at' => $booking->expires_at,
            ]);

        $activeRents = DB::table('rent as rt')
            ->join('booking as bk', 'bk.id', '=', 'rt.booking_id')
            ->leftJoin('tenants as t', 't.id', '=', 'rt.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->leftJoinSub($latestPayment, 'latest_payment', function ($join) {
                $join->on('latest_payment.booking_id', '=', 'bk.id');
            })
            ->leftJoin('payment as p', 'p.id', '=', 'latest_payment.latest_payment_id')
            ->where('rt.room_id', $room->id)
            ->whereIn('rt.status', ['active', 'scheduled'])
            ->whereDate('rt.end_date', '>=', today())
            ->select(
                'rt.id',
                'rt.rent_code as code',
                'rt.start_date',
                'rt.end_date',
                'rt.status',
                'bk.booking_code',
                'u.fname',
                'u.lname',
                'p.payment_code',
                'p.status as payment_status',
                'p.payment_method',
                'p.amount as payment_amount'
            )
            ->get()
            ->map(fn ($rent) => [
                'type' => 'Sewa',
                'code' => $rent->code ?: 'RENT-' . $rent->id,
                'start_date' => $rent->start_date,
                'end_date' => $rent->end_date,
                'status' => $rent->status,
                'tenant_name' => trim(($rent->fname ?? '') . ' ' . ($rent->lname ?? '')) ?: 'Nama penyewa tidak tersedia',
                'booking_code' => $rent->booking_code,
                'payment_code' => $rent->payment_code,
                'payment_status' => $rent->payment_status,
                'payment_method' => $rent->payment_method,
                'payment_amount' => $rent->payment_amount,
                'expires_at' => null,
            ]);

        $historyBookings = DB::table('booking as bk')
            ->leftJoin('tenants as t', 't.id', '=', 'bk.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->leftJoinSub($latestPayment, 'latest_payment', function ($join) {
                $join->on('latest_payment.booking_id', '=', 'bk.id');
            })
            ->leftJoin('payment as p', 'p.id', '=', 'latest_payment.latest_payment_id')
            ->where('bk.room_id', $room->id)
            ->where(function ($query) {
                $query->whereIn('bk.status', ['expired', 'cancelled'])
                    ->orWhere(function ($pastConfirmed) {
                        $pastConfirmed->where('bk.status', 'confirmed')
                            ->whereDate('bk.end_date', '<', today());
                    });
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rent as rt')
                    ->whereColumn('rt.booking_id', 'bk.id');
            })
            ->select(
                'bk.id',
                'bk.booking_code as code',
                'bk.start_date',
                'bk.end_date',
                'bk.status',
                'u.fname',
                'u.lname',
                'p.payment_code',
                'p.status as payment_status',
                'p.payment_method',
                'p.amount as payment_amount'
            )
            ->get()
            ->map(fn ($booking) => [
                'type' => 'Booking',
                'code' => $booking->code ?: 'BOOKING-' . $booking->id,
                'start_date' => $booking->start_date,
                'end_date' => $booking->end_date,
                'status' => $booking->status,
                'tenant_name' => trim(($booking->fname ?? '') . ' ' . ($booking->lname ?? '')) ?: 'Nama penyewa tidak tersedia',
                'payment_code' => $booking->payment_code,
                'payment_status' => $booking->payment_status,
                'payment_method' => $booking->payment_method,
                'payment_amount' => $booking->payment_amount,
                'expires_at' => null,
            ]);

        $historyRents = DB::table('rent as rt')
            ->leftJoin('booking as bk', 'bk.id', '=', 'rt.booking_id')
            ->leftJoin('tenants as t', 't.id', '=', 'rt.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->leftJoinSub($latestPayment, 'latest_payment', function ($join) {
                $join->on('latest_payment.booking_id', '=', 'bk.id');
            })
            ->leftJoin('payment as p', 'p.id', '=', 'latest_payment.latest_payment_id')
            ->where('rt.room_id', $room->id)
            ->where(function ($query) {
                $query->whereIn('rt.status', ['completed', 'cancelled'])
                    ->orWhereDate('rt.end_date', '<', today());
            })
            ->select(
                'rt.id',
                'rt.rent_code as code',
                'rt.start_date',
                'rt.end_date',
                'rt.status',
                'bk.booking_code',
                'u.fname',
                'u.lname',
                'p.payment_code',
                'p.status as payment_status',
                'p.payment_method',
                'p.amount as payment_amount'
            )
            ->get()
            ->map(fn ($rent) => [
                'type' => 'Sewa',
                'code' => $rent->code ?: 'RENT-' . $rent->id,
                'start_date' => $rent->start_date,
                'end_date' => $rent->end_date,
                'status' => $rent->status,
                'tenant_name' => trim(($rent->fname ?? '') . ' ' . ($rent->lname ?? '')) ?: 'Nama penyewa tidak tersedia',
                'booking_code' => $rent->booking_code,
                'payment_code' => $rent->payment_code,
                'payment_status' => $rent->payment_status,
                'payment_method' => $rent->payment_method,
                'payment_amount' => $rent->payment_amount,
                'expires_at' => null,
            ]);

        $currentTransactions = $pendingBookings->concat($activeRents)
            ->sortBy('start_date')
            ->values();

        $transactionHistory = $historyBookings->concat($historyRents)
            ->sortByDesc('start_date')
            ->values();

        return [$currentTransactions, $transactionHistory];
    }

    private function statusChangeBlockedResponse(Request $request, Room $room): ?RedirectResponse
    {
        $transactions = $this->roomBookingAndRentDetails($room);

        if (empty($transactions)) {
            return null;
        }

        $message = 'Status kamar ' . $room->room_code
            . ' tidak dapat diubah karena masih memiliki '
            . count($transactions)
            . ' data booking dan/atau penyewaan. Periksa detail transaksi berikut.';

        return back()
            ->withInput()
            ->withErrors(['status' => $message])
            ->with('roomStatusBlockers', $transactions);
    }

    /**
     * Perbarui kamar.
     */
    public function update(Request $request, string $room): RedirectResponse
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
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['integer', 'distinct', Rule::exists('facility', 'id')->where(fn ($query) => $query->where('owner_id', DB::table('owners')->where('user_id', $request->user()->id)->value('id'))->where('facility_type', 'room'))],
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
        $facilityIds = $validated['facilities'] ?? [];

        if ($validated['status'] !== $room->status) {
            $blockedResponse = $this->statusChangeBlockedResponse($request, $room);

            if ($blockedResponse) {
                return $blockedResponse;
            }
        }

        $deleteImageIds = collect($validated['delete_images'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->all();

        unset($validated['images'], $validated['delete_images'], $validated['facilities']);

        DB::transaction(function () use (
            $room,
            $validated,
            $uploadedImages,
            $deleteImageIds,
            $facilityIds
        ) {
            $room->update($validated);
            $room->facilities()->sync($facilityIds);

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
            ->route('owner.rooms.show', $room)
            ->with('success', 'Data kamar berhasil diperbarui.');
    }

    /**
     * Ubah status operasional kamar.
     *
     * Kamar yang masih memiliki alokasi rent/booking saat ini atau mendatang
     * tidak boleh dinonaktifkan/diubah ke maintenance.
     */
    public function updateStatus(Request $request, string $room): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive', 'maintenance'])],
        ]);

        $ownedRoom = $this->ownedRoom($request, $room);
        $newStatus = $validated['status'];

        if ($newStatus === $ownedRoom->status) {
            return back()->with('success', 'Status kamar tidak mengalami perubahan.');
        }

        $blockedResponse = $this->statusChangeBlockedResponse($request, $ownedRoom);

        if ($blockedResponse) {
            return $blockedResponse;
        }

        DB::transaction(function () use ($ownedRoom, $newStatus) {
            $lockedRoom = Room::query()
                ->whereKey($ownedRoom->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedRoom->update(['status' => $newStatus]);
        });

        $statusLabels = [
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
            'maintenance' => 'Perawatan',
        ];

        return back()->with(
            'success',
            'Status kamar berhasil diubah menjadi ' . $statusLabels[$newStatus] . '.'
        );
    }

    /**
     * Hapus kamar jika belum memiliki riwayat transaksi.
     */
    public function destroy(Request $request, string $room): RedirectResponse
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
