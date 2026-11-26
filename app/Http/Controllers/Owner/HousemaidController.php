<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BranchDorm;
use App\Models\Housemaid;
use App\Models\Owner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HousemaidController extends Controller
{
    private function currentOwner(Request $request): Owner
    {
        return Owner::query()->where('user_id', $request->user()->id)->firstOrFail();
    }

    private function ownedHousemaid(Owner $owner, string $publicId): Housemaid
    {
        return Housemaid::query()->where('owner_id', $owner->id)->where('public_id', $publicId)->firstOrFail();
    }

    private function activeBranches(Owner $owner)
    {
        return BranchDorm::query()
            ->where('owner_id', $owner->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function index(Request $request): View
    {
        $owner = $this->currentOwner($request);
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        abort_unless(in_array($status, ['all', 'active', 'inactive'], true), 404);

        $query = Housemaid::query()
            ->where('owner_id', $owner->id)
            ->with(['branches:id,name'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('fname', 'like', "%{$search}%")
                        ->orWhere('lname', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(COALESCE(fname, ''), ' ', COALESCE(lname, '')) LIKE ?", ["%{$search}%"])
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            });

        $summaryQuery = Housemaid::query()->where('owner_id', $owner->id);
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'active' => (clone $summaryQuery)->where('status', 'active')->count(),
            'inactive' => (clone $summaryQuery)->where('status', 'inactive')->count(),
        ];

        $housemaids = $query->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('fname')->orderBy('lname')
            ->paginate(10)->withQueryString();

        return view('pages.owner.housemaids.index', compact('housemaids', 'summary', 'search', 'status'));
    }

    public function create(Request $request): View
    {
        $owner = $this->currentOwner($request);
        $branches = $this->activeBranches($owner);
        return view('pages.owner.housemaids.create', compact('branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $owner = $this->currentOwner($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:201'],
            'phone_number' => ['required', 'string', 'max:20', Rule::unique('housemaid', 'phone_number')->where('owner_id', $owner->id)],
            'salary' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['required', 'integer', 'distinct', Rule::exists('branch_dorm', 'id')->where(fn ($q) => $q->where('owner_id', $owner->id)->where('status', 'active'))],
        ], [
            'branch_ids.required' => 'Pilih minimal satu cabang penempatan.',
            'branch_ids.min' => 'Pilih minimal satu cabang penempatan.',
            'phone_number.unique' => 'Nomor telepon tersebut sudah digunakan oleh ART lain.',
        ]);

        [$fname, $lname] = $this->splitName($validated['name']);

        $housemaid = DB::transaction(function () use ($owner, $validated, $fname, $lname) {
            $housemaid = Housemaid::create([
                'owner_id' => $owner->id,
                'fname' => $fname,
                'lname' => $lname,
                'salary' => $validated['salary'],
                'phone_number' => trim($validated['phone_number']),
                'status' => 'active',
            ]);
            $housemaid->branches()->sync($validated['branch_ids']);
            return $housemaid;
        });

        return redirect()->route('owner.housemaids.show', $housemaid)->with('success', 'Data ART berhasil ditambahkan.');
    }

    public function show(Request $request, string $housemaid): View
    {
        $owner = $this->currentOwner($request);
        $housemaid = $this->ownedHousemaid($owner, $housemaid)->load('branches');
        return view('pages.owner.housemaids.show', compact('housemaid'));
    }

    public function edit(Request $request, string $housemaid): View
    {
        $owner = $this->currentOwner($request);
        $housemaid = $this->ownedHousemaid($owner, $housemaid)->load('branches');
        $branches = $this->activeBranches($owner);
        $selectedBranchIds = $housemaid->branches->pluck('id')->all();
        return view('pages.owner.housemaids.edit', compact('housemaid', 'branches', 'selectedBranchIds'));
    }

    public function update(Request $request, string $housemaid): RedirectResponse
    {
        $owner = $this->currentOwner($request);
        $housemaid = $this->ownedHousemaid($owner, $housemaid);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:201'],
            'phone_number' => ['required', 'string', 'max:20', Rule::unique('housemaid', 'phone_number')->where('owner_id', $owner->id)->ignore($housemaid->id)],
            'salary' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['required', 'integer', 'distinct', Rule::exists('branch_dorm', 'id')->where(fn ($q) => $q->where('owner_id', $owner->id)->where('status', 'active'))],
        ], [
            'branch_ids.required' => 'Pilih minimal satu cabang penempatan.',
            'branch_ids.min' => 'Pilih minimal satu cabang penempatan.',
            'phone_number.unique' => 'Nomor telepon tersebut sudah digunakan oleh ART lain.',
        ]);

        [$fname, $lname] = $this->splitName($validated['name']);

        DB::transaction(function () use ($housemaid, $validated, $fname, $lname) {
            $housemaid->update([
                'fname' => $fname,
                'lname' => $lname,
                'phone_number' => trim($validated['phone_number']),
                'salary' => $validated['salary'],
            ]);
            // Pengeditan mewajibkan penempatan. Jika ART sebelumnya nonaktif,
            // penempatan baru berarti ART diaktifkan kembali.
            $housemaid->branches()->sync($validated['branch_ids']);
            if ($housemaid->status === 'inactive') {
                $housemaid->update(['status' => 'active']);
            }
        });

        return redirect()->route('owner.housemaids.show', $housemaid)->with('success', 'Data ART berhasil diperbarui.');
    }

    public function updateStatus(Request $request, string $housemaid): RedirectResponse
    {
        $owner = $this->currentOwner($request);
        $housemaid = $this->ownedHousemaid($owner, $housemaid);
        $validated = $request->validate(['status' => ['required', Rule::in(['active', 'inactive'])]]);

        if ($validated['status'] === 'active' && !$housemaid->branches()->where('branch_dorm.status', 'active')->exists()) {
            return back()->with('error', 'ART tidak dapat diaktifkan karena belum memiliki penempatan di cabang aktif. Edit data ART dan pilih minimal satu cabang terlebih dahulu.');
        }

        DB::transaction(function () use ($housemaid, $validated) {
            $housemaid->update(['status' => $validated['status']]);
            if ($validated['status'] === 'inactive') {
                $housemaid->branches()->detach();
            }
        });

        return back()->with('success', $validated['status'] === 'inactive'
            ? 'ART dinonaktifkan dan seluruh penempatannya dilepaskan.'
            : 'ART berhasil diaktifkan.');
    }

    public function destroy(Request $request, string $housemaid): RedirectResponse
    {
        $owner = $this->currentOwner($request);
        $housemaid = $this->ownedHousemaid($owner, $housemaid);
        if ($housemaid->status !== 'inactive') {
            return back()->with('error', 'ART aktif tidak dapat dihapus. Nonaktifkan ART terlebih dahulu.');
        }

        if ($housemaid->branches()->exists()) {
            return back()->with('error', 'ART tidak dapat dihapus karena masih memiliki penempatan cabang.');
        }

        DB::transaction(fn () => $housemaid->delete());
        return redirect()->route('owner.housemaids.index')->with('success', 'Data ART berhasil dihapus permanen.');
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/u', trim($name), 2);
        return [$parts[0] ?? '', $parts[1] ?? null];
    }
}
