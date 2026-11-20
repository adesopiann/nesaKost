# nesaKost - Halaman Untuk Pemilik Kost

Patch dibuat berdasarkan source project terbaru yang diunggah.

## File yang ditambahkan
- `resources/views/pages/guest/owner.blade.php`

## File yang diperbarui
- `routes/web.php` — menambahkan route GET `/untuk-pemilik` bernama `guest.owner`.
- `resources/views/components/layouts/guest/navbar.blade.php` — tautan Untuk Pemilik Kost desktop dan mobile diarahkan ke halaman baru.
- `resources/views/components/layouts/guest/footer.blade.php` — tautan Untuk Pemilik diarahkan ke halaman baru.
- `resources/views/pages/guest/index.blade.php` — CTA pada section pemilik kost diarahkan ke halaman detail baru.

## Isi halaman baru
1. Hero dengan CTA registrasi pemilik dan ilustrasi dashboard.
2. Ringkasan manfaat utama.
3. Enam fitur pengelolaan.
4. Empat langkah mulai bergabung.
5. FAQ.
6. CTA akhir menuju registrasi pemilik.

## Penerapan
Ekstrak ZIP ke root project Laravel dan izinkan file yang sama ditimpa.
Kemudian jalankan:

```bash
php artisan optimize:clear
```

Route pendaftaran yang digunakan adalah route yang sudah tersedia: `register.create` dengan parameter `role=owner`.
