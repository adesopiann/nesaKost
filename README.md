# eKost — Riwayat Pembayaran (tabel konsisten)

Patch ini menyelaraskan daftar transaksi pembayaran pada Dashboard dan Riwayat Pembayaran menggunakan satu partial Blade yang sama.

## File dalam patch

- `app/Http/Controllers/Tenant/PaymentHistoryController.php`
- `resources/views/components/tenant/payment-table.blade.php` — partial tabel bersama
- `resources/views/pages/tenant/dashboard/index.blade.php` — Dashboard menggunakan partial tabel
- `resources/views/pages/tenant/riwayat-pembayaran/index.blade.php` — Riwayat Pembayaran menggunakan partial tabel
- `resources/views/pages/tenant/riwayat-pembayaran/show.blade.php`
- `routes/tenant-payment-history-snippet.txt` — potongan route untuk ditambahkan jika belum ada

## Cara menerapkan

1. Ekstrak folder `nesaKost2` dari ZIP.
2. Salin file/folder di dalamnya ke root project Laravel, dengan mempertahankan struktur folder.
3. Pastikan import dan dua route pada `routes/tenant-payment-history-snippet.txt` sudah ditambahkan ke `routes/web.php` di dalam grup route tenant. Jangan menambahkan duplikat jika route sudah ada.
4. Jalankan:
   - `php artisan view:clear`
   - `php artisan route:list --name=tenant.riwayat-pembayaran`
5. Buka Dashboard dan Riwayat Pembayaran untuk memeriksa kedua tabel.

## Perubahan UI

- Kedua halaman memakai kolom dan gaya tabel yang sama: Kode Pembayaran, Tanggal, Metode, Jumlah, Status, dan Aksi.
- Aksi Detail membuka halaman detail pembayaran.
- Dashboard tetap membatasi daftar menjadi lima transaksi terbaru.
- Riwayat Pembayaran tetap mempertahankan filter, ringkasan, dan pagination.
- Tabel dapat digulir horizontal pada layar sempit.

Patch ini tidak mengubah skema database maupun alur pembayaran. Belum dilakukan pengujian runtime pada instalasi Laravel milik pengguna.
