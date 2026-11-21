<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FacilityController extends Controller
{
    private function owner(): Owner
    {
        return Owner::where('user_id', Auth::id())->firstOrFail();
    }

    public function index(Request $request): View
    {
        $owner = $this->owner();
        $search = trim((string) $request->query('q', ''));
        $facilities = Facility::where('owner_id', $owner->id)
            ->withCount(['branches', 'rooms'])
            ->when(in_array($request->query('type'), ['branch', 'room'], true), fn ($q) => $q->where('facility_type', $request->query('type')))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('facility_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->orderBy('facility_name')->paginate(10)->withQueryString();

        return view('pages.owner.facilities.index', compact('facilities', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $owner = $this->owner();
        $data = $request->validate([
            'facility_name' => ['required', 'string', 'max:100', Rule::unique('facility')->where(fn ($q) => $q->where('owner_id', $owner->id)->where('facility_type', $request->input('facility_type')))],
            'facility_type' => ['required', Rule::in(['branch', 'room'])],
            'description' => ['nullable', 'string', 'max:2000'],
        ], ['facility_name.unique' => 'Nama fasilitas dengan jenis tersebut sudah tersedia di daftar fasilitas Anda.']);
        Facility::create($data + ['owner_id' => $owner->id]);
        return back()->with('success', 'Fasilitas berhasil ditambahkan.');
    }

    public function update(Request $request, Facility $facility): RedirectResponse
    {
        $this->authorizeFacility($facility);
        $data = $request->validate([
            'facility_name' => ['required', 'string', 'max:100', Rule::unique('facility')->where(fn ($q) => $q->where('owner_id', $facility->owner_id)->where('facility_type', $request->input('facility_type')))->ignore($facility->id)],
            'facility_type' => ['required', Rule::in(['branch', 'room'])],
            'description' => ['nullable', 'string', 'max:2000'],
        ], ['facility_name.unique' => 'Nama fasilitas dengan jenis tersebut sudah tersedia di daftar fasilitas Anda.']);
        if ($data['facility_type'] !== $facility->facility_type && ($facility->branches()->exists() || $facility->rooms()->exists())) {
            return back()->withInput()->with('error', 'Jenis fasilitas tidak dapat diubah selama fasilitas masih digunakan. Lepaskan relasinya terlebih dahulu.');
        }
        $facility->update($data);
        return back()->with('success', 'Perubahan fasilitas berhasil disimpan.');
    }

    public function destroy(Facility $facility): RedirectResponse
    {
        $this->authorizeFacility($facility);
        DB::transaction(function () use ($facility) {
            // Foreign key pada branch_facility dan room_facility menggunakan ON DELETE CASCADE,
            // sehingga hanya relasi fasilitas yang terhapus; data cabang dan kamar tetap aman.
            $facility->delete();
        });

        return back()->with('success', 'Fasilitas dan seluruh relasinya berhasil dihapus. Data cabang dan kamar tetap tersimpan.');
    }

    private function authorizeFacility(Facility $facility): void
    {
        abort_unless((int) $facility->owner_id === (int) $this->owner()->id, 404);
    }
}
