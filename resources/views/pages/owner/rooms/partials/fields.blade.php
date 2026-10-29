<div class="space-y-6">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="room_code" class="mb-2 block text-sm font-semibold">Kode Kamar <span class="text-red-500">*</span></label>
            <input id="room_code" name="room_code" value="{{ old('room_code', $room->room_code ?? '') }}" required maxlength="20" placeholder="Contoh: KMR-A101" class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
            @error('room_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            <p class="mt-2 text-xs text-slate-500">Kode kamar diisi manual dan harus unik.</p>
        </div>
        <div>
            <label for="status" class="mb-2 block text-sm font-semibold">Status Kamar <span class="text-red-500">*</span></label>
            @php($selectedStatus = old('status', $room->status ?? 'available'))
            <select id="status" name="status" required class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                <option value="available" @selected($selectedStatus === 'available')>Tersedia</option>
                <option value="occupied" @selected($selectedStatus === 'occupied')>Terisi</option>
                <option value="maintenance" @selected($selectedStatus === 'maintenance')>Dalam perawatan</option>
                <option value="inactive" @selected($selectedStatus === 'inactive')>Nonaktif</option>
            </select>
            @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="branch_dorm_id" class="mb-2 block text-sm font-semibold">Cabang Kos <span class="text-red-500">*</span></label>
        <select id="branch_dorm_id" name="branch_dorm_id" required class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
            <option value="">Pilih cabang kos</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) old('branch_dorm_id', $room->branch_dorm_id ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        @error('branch_dorm_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        @if($branches->isEmpty())<p class="mt-2 text-xs text-amber-700">Belum ada cabang yang dapat dipilih. Tambahkan cabang terlebih dahulu.</p>@endif
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="type" class="mb-2 block text-sm font-semibold">Tipe Kamar <span class="text-red-500">*</span></label>
            <input id="type" name="type" value="{{ old('type', $room->type ?? '') }}" required maxlength="50" placeholder="Contoh: Standard, Deluxe, Single" class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
            @error('type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="monthly_price" class="mb-2 block text-sm font-semibold">Harga Sewa per Bulan (Rp) <span class="text-red-500">*</span></label>
            <input id="monthly_price" name="monthly_price" type="number" min="0" step="0.01" value="{{ old('monthly_price', $room->monthly_price ?? '') }}" required placeholder="Contoh: 850000" class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
            @error('monthly_price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="images" class="mb-2 block text-sm font-semibold">Galeri Foto Kamar</label>
        <input id="images" name="images[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm">
        <p class="mt-2 text-xs text-slate-500">Pilih beberapa foto sekaligus. Format JPG, PNG, atau WebP; maksimal 10 foto per unggahan dan 5 MB per foto.</p>
        @error('images')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        @error('images.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror

        @if(isset($room) && $room && $room->images->isNotEmpty())
            <div class="mt-5">
                <p class="mb-3 text-sm font-semibold">Foto yang sudah tersimpan</p>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($room->images as $image)
                        <label class="overflow-hidden rounded-xl border border-slate-200 p-2">
                            <img src="{{ asset('storage/'.$image->image_path) }}" alt="Foto kamar {{ $room->room_code }}" class="h-32 w-full rounded-lg object-cover">
                            <span class="mt-2 flex items-center gap-2 text-xs">
                                <input type="checkbox" name="delete_images[]" value="{{ $image->id }}" class="rounded border-slate-300 text-red-600">
                                Hapus foto ini
                            </span>
                            @if($image->is_primary)
                                <span class="mt-2 inline-block rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800">Foto utama</span>
                            @endif
                        </label>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-slate-500">Centang foto yang ingin dihapus, lalu simpan perubahan.</p>
            </div>
        @elseif(isset($room) && $room && $room->image_path)
            <div class="mt-3">
                <p class="mb-2 text-xs font-semibold text-slate-500">Foto lama (belum dipindahkan ke galeri)</p>
                <img src="{{ asset('storage/'.$room->image_path) }}" alt="Foto kamar {{ $room->room_code }}" class="h-40 w-56 rounded-lg object-cover">
            </div>
        @endif
    </div>

    <div>
        <label for="description" class="mb-2 block text-sm font-semibold">Deskripsi Kamar</label>
        <textarea id="description" name="description" rows="4" placeholder="Jelaskan fasilitas atau kondisi kamar..." class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">{{ old('description', $room->description ?? '') }}</textarea>
        @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
