# Standar Ikon Project eKost — Feather Icons

Mulai revisi 18 November 2026, seluruh ikon antarmuka eKost menggunakan Feather Icons.

## Aturan implementasi
- Gunakan elemen `<i data-feather="nama-ikon" class="..." aria-hidden="true"></i>`.
- Jangan menambahkan inline SVG atau karakter Unicode sebagai ikon antarmuka.
- Logo, foto, ilustrasi, dan grafik dekoratif bukan ikon UI; aset tersebut tidak perlu diganti dengan Feather.
- Gunakan Feather Icons versi 4.29.2 dari CDN yang dimuat oleh layout.
- Setiap layout yang menggunakan `data-feather` harus memuat library dan menjalankan `feather.replace()` setelah DOM siap.
- Untuk ikon yang dibuat atau diubah melalui JavaScript, panggil `feather.replace()` setelah HTML ikon dimasukkan.

## Layout yang memuat Feather
- `resources/views/components/layouts/guest/container.blade.php`
- `resources/views/components/layouts/tenant/container.blade.php`
- `resources/views/components/layouts/owner/container.blade.php`
- `resources/views/components/layouts/guest.blade.php` (autentikasi)
- `resources/views/welcome.blade.php` (halaman welcome Laravel)

## Area yang diseragamkan
- Navbar guest, tenant, owner, dan sidebar owner.
- Halaman autentikasi.
- Dashboard tenant dan owner.
- Halaman notifikasi tenant.
- Halaman katalog/detail properti.
- Halaman pemilihan metode dan instruksi pembayaran.
- Ikon aksi seperti kembali, refresh, salin, peringatan, centang, menu, dan tautan eksternal.

## Catatan
Ikon fasilitas pada halaman detail properti menggunakan ikon `sliders` sebagai ikon generik fasilitas, karena sumber data fasilitas saat ini tidak menyediakan pemetaan ikon per fasilitas.
