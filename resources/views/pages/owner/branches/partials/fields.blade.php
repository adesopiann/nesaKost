<div class="space-y-6">

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <label for="branch_code" class="mb-2 block text-sm font-semibold">Kode Cabang</label>
        <input id="branch_code" value="{{ isset($branch) ? $branch->branch_code : 'Dibuat otomatis setelah disimpan' }}" disabled class="w-full rounded-lg border-0 bg-indigo-50 px-4 py-3 text-sm text-slate-600">
        <p class="mt-2 text-xs text-slate-500">Kode tampilan dibentuk otomatis berdasarkan ID cabang.</p>
    </div>
    <div>
        <label for="status" class="mb-2 block text-sm font-semibold">Status Cabang <span class="text-red-500">*</span></label>
        <select id="status" name="status" required class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
            <option value="active" @selected(old('status', $branch->status ?? 'active') === 'active')>Aktif</option>
            <option value="inactive" @selected(old('status', $branch->status ?? 'active') === 'inactive')>Nonaktif</option>
        </select>
        @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label for="name" class="mb-2 block text-sm font-semibold">Nama Cabang Kos <span class="text-red-500">*</span></label>
    <input id="name" name="name" value="{{ old('name', $branch->name ?? '') }}" required maxlength="150" placeholder="Contoh: Melati Residence" class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="mb-2 block text-sm font-semibold">Pemilik Properti</label>
    <input value="{{ trim(($owner->user->fname ?? $branch->owner->user->fname ?? '') . ' ' . ($owner->user->lname ?? $branch->owner->user->lname ?? '')) }}" disabled class="w-full rounded-lg border-0 bg-indigo-50 px-4 py-3 text-sm text-slate-700">
    <p class="mt-2 text-xs text-slate-500">Cabang akan terhubung dengan akun pemilik yang sedang login.</p>
</div>

<div>
    <label class="mb-2 block text-sm font-semibold">Fasilitas Umum</label>
    <p class="mb-3 text-xs text-slate-500">Pilih fasilitas yang tersedia dan dapat digunakan penghuni cabang ini.</p>
    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($facilities as $facility)
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 hover:bg-indigo-50">
                <input type="checkbox" name="facilities[]" value="{{ $facility->id }}" @checked(in_array($facility->id, old('facilities', $selectedFacilities ?? []))) class="mt-1 rounded border-slate-300 text-blue-800 focus:ring-blue-700">
                <span><span class="block text-sm font-medium">{{ $facility->facility_name }}</span>@if($facility->description)<span class="mt-1 block text-xs text-slate-500">{{ $facility->description }}</span>@endif</span>
            </label>
        @empty
            <p class="text-sm text-slate-500">Master fasilitas belum tersedia. Tambahkan fasilitas terlebih dahulu.</p>
        @endforelse
    </div>
    @error('facilities')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    @error('facilities.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label for="address" class="mb-2 block text-sm font-semibold">Alamat Lengkap Cabang <span class="text-red-500">*</span></label>
    <textarea id="address" name="address" rows="4" required maxlength="5000" placeholder="Nama jalan, RT/RW, kelurahan, kecamatan, kabupaten/kota, provinsi, dan kode pos" class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">{{ old('address', $branch->address ?? '') }}</textarea>
    <p class="mt-2 text-xs text-slate-500">Masukkan alamat lengkap agar lokasi properti mudah diverifikasi.</p>
    @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

</div>
