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

    public function store(Request $request): RedirectResponse
    {
        $owner = $this->currentOwner();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['integer', 'distinct', 'exists:facility,id'],
        ]);

        $branch = DB::transaction(function () use ($validated, $owner) {
            $branch = BranchDorm::create([
                'owner_id' => $owner->id,
                'name' => $validated['name'],
                'address' => $validated['address'],
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

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['integer', 'distinct', 'exists:facility,id'],
        ]);

        DB::transaction(function () use ($validated, $branch) {
            $branch->update([
                'name' => $validated['name'],
                'address' => $validated['address'],
                'status' => $validated['status'],
            ]);

            $branch->facilities()->sync($validated['facilities'] ?? []);
        });

        return redirect()
            ->route('owner.branches.show', $branch)
            ->with('success', 'Perubahan cabang kos berhasil disimpan.');
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
