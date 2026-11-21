@php
    $currentName = old('name', isset($housemaid) ? $housemaid->full_name : '');
    $selected = old('branch_ids', $selectedBranchIds ?? []);
@endphp
<div class="space-y-5">
    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama lengkap ART <span
                class="text-rose-600">*</span></label><input name="name" value="{{ $currentName }}" required
            maxlength="201" placeholder="Masukkan nama lengkap"
            class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
        <p class="mt-1 text-xs text-slate-500">Masukkan nama depan dan nama belakang jika ada.</p>
        @error('name')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Nomor telepon <span
                    class="text-rose-600">*</span></label><input name="phone_number"
                value="{{ old('phone_number', $housemaid->phone_number ?? '') }}" required maxlength="20"
                placeholder="08xxxxxxxxxx" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
            @error('phone_number')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Gaji bulanan (Rp) <span
                    class="text-rose-600">*</span></label><input type="number" name="salary"
                value="{{ old('salary', $housemaid->salary ?? '') }}" required min="0" step="1000"
                placeholder="Contoh: 1500000" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
            @error('salary')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div>
        <div class="mb-2"><label class="block text-sm font-semibold text-slate-700">Penempatan cabang <span
                    class="text-rose-600">*</span></label>
            <p class="mt-1 text-xs text-slate-500">Pilih minimal satu cabang aktif. ART dapat ditempatkan di beberapa
                cabang. Jika ART berstatus nonaktif, menyimpan penempatan baru akan mengaktifkannya kembali.</p>
        </div>
        @if ($branches->isEmpty())
            <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Belum ada cabang aktif. Aktifkan atau
                tambahkan cabang terlebih dahulu.</div>
        @else
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($branches as $branch)
                    <label
                        class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50"><input
                            type="checkbox" name="branch_ids[]" value="{{ $branch->id }}" @checked(in_array($branch->id, $selected))
                            class="rounded border-slate-300 text-[#1D4E80] focus:ring-[#1D4E80]"><span
                            class="text-sm text-slate-700">{{ $branch->name }}</span></label>
                @endforeach
            </div>
        @endif
        @error('branch_ids')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror @error('branch_ids.*')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>
