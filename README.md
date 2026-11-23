# Panduan Demo Aplikasi nesaKost

Dokumen ini berisi panduan menyiapkan aplikasi dari awal sampai menjalankan skenario demo untuk pengunjung, owner, dan tenant. Panduan disusun berdasarkan struktur proyek Laravel, `routes/web.php`, serta `DatabaseSeeder.php` versi demo yang disiapkan.

> **Catatan:** jalankan seluruh langkah pada lingkungan lokal/development. Perintah `migrate:fresh` akan menghapus seluruh tabel dan data pada database yang sedang digunakan.

---

## 1. Gambaran aplikasi

nesaKost merupakan aplikasi pengelolaan kost dengan dua alur utama:

1. **Pengunjung (guest):** melihat informasi kost, daftar cabang, dan detail kamar sebelum melakukan pendaftaran atau login.
2. **Pengelola (owner):** mengelola cabang, kamar, fasilitas, ART, penyewa, transaksi pembayaran, masa sewa, dan notifikasi.
3. **Penyewa (tenant):** melihat kamar yang tersedia, melakukan booking, memilih metode pembayaran simulasi, melihat masa sewa, mengajukan perpanjangan, dan mengelola profil.

Aplikasi juga menyediakan alur verifikasi email menggunakan OTP, pengingat masa sewa, riwayat pembayaran, dan pusat notifikasi.

**Penting mengenai manager:** akun manager disiapkan oleh seeder, tetapi grup route `/manager` pada `routes/web.php` masih dikomentari. Karena itu, jangan menjadikan dashboard manager sebagai bagian demo utama sebelum route dan hak akses manager diaktifkan.

---

## 2. Persiapan perangkat dan source code

Pastikan perangkat sudah memiliki:

- PHP sesuai kebutuhan proyek (proyek menggunakan PHP `^8.3`).
- Composer.
- Node.js dan NPM.
- MySQL/MariaDB, misalnya melalui Laragon.
- Web browser.
- VS Code atau editor lain.

### 2.1 Ekstrak source code

1. Ekstrak ZIP proyek `nesaKost2`.
2. Buka folder proyek yang berisi `artisan`, `composer.json`, dan `package.json` di VS Code.
3. Buka terminal pada folder tersebut.

Pastikan terminal berada di direktori root Laravel, bukan di folder induk atau subfolder `public`.

### 2.2 Install dependency

Jalankan:

```bash
composer install
npm install
```

Jika dependency frontend sudah tersedia dan tidak ada perubahan, proses `npm install` tetap dapat dijalankan untuk memastikan package sesuai dengan `package-lock.json`.

---

## 3. Konfigurasi database

### 3.1 Buat database MySQL

1. Jalankan MySQL dari Laragon.
2. Buka phpMyAdmin atau database client.
3. Buat database baru, misalnya:

```sql
CREATE DATABASE nesakost_demo;
```

### 3.2 Atur file `.env`

Jika `.env` belum tersedia, salin `.env.example` menjadi `.env`.

Sesuaikan konfigurasi database. Contoh konfigurasi lokal Laragon:

```env
APP_NAME=nesaKost
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nesakost_demo
DB_USERNAME=root
DB_PASSWORD=
```

Sesuaikan `DB_USERNAME` dan `DB_PASSWORD` dengan konfigurasi MySQL pada perangkat masing-masing. Jangan menyalin contoh password secara membabi buta jika MySQL lokal menggunakan password.

### 3.3 Generate application key

```bash
php artisan key:generate
```

Pastikan `APP_KEY` sudah terisi di `.env`.

### 3.4 Bersihkan cache konfigurasi

```bash
php artisan optimize:clear
```

---

## 4. Siapkan database demo

Pastikan file `database/seeders/DatabaseSeeder.php` sudah diganti dengan versi seeder demo terbaru.

Kemudian jalankan:

```bash
php artisan migrate:fresh --seed
```

Perintah tersebut akan:

1. Menghapus tabel yang ada pada database terpilih.
2. Menjalankan seluruh migration.
3. Menjalankan `DatabaseSeeder`.
4. Membuat akun demo, data cabang, kamar, fasilitas, ART, booking, pembayaran, rent, dan notifikasi.

Jika perintah gagal:

- Baca pesan error pertama yang muncul, bukan hanya baris paling akhir.
- Pastikan database pada `.env` benar.
- Pastikan MySQL sedang berjalan.
- Pastikan seluruh migration berhasil dijalankan.
- Pastikan seeder yang digunakan adalah versi terbaru.
- Jangan menghapus migration hanya untuk menghilangkan error tanpa memeriksa penyebabnya.

> Jangan menjalankan `migrate:fresh --seed` pada database produksi atau database yang berisi data penting.

### 4.1 Buat symbolic link storage

Jika aplikasi menggunakan file upload untuk gambar, jalankan:

```bash
php artisan storage:link
```

Seeder demo tidak menjamin file gambar asli tersedia. Jika gambar cabang/kamar belum ada, unggah gambar melalui fitur aplikasi atau siapkan aset demo terlebih dahulu.

---

## 5. Jalankan aplikasi

Buka dua terminal pada root proyek.

**Terminal 1 — Laravel**

```bash
php artisan serve
```

Secara default aplikasi dapat diakses melalui:

```text
http://127.0.0.1:8000
```

**Terminal 2 — Vite**

```bash
npm run dev
```

Biarkan kedua terminal tetap berjalan selama demo. Jika ingin menjalankan aset frontend hasil build, gunakan:

```bash
npm run build
```

Lalu akses alamat aplikasi melalui browser.

---

## 6. Akun demo

Semua akun yang dibuat oleh seeder menggunakan password:

```text
password123
```

### 6.1 Owner

| Role | Email | Password |
|---|---|---|
| Owner | `owner@ekost.test` | `password123` |

Gunakan akun ini untuk mendemonstrasikan pengelolaan seluruh cabang milik owner.

### 6.2 Tenant

| Akun | Email | Skenario utama |
|---|---|---|
| Tenant 01 | `tenant01@ekost.test` | Riwayat sewa selesai |
| Tenant 02 | `tenant02@ekost.test` | Akun tenant tambahan |
| Tenant 03 | `tenant03@ekost.test` | Sewa aktif dan pengajuan renewal menunggu pembayaran |
| Tenant 04 | `tenant04@ekost.test` | Pengingat masa sewa H-30 |
| Tenant 05 | `tenant05@ekost.test` | Pengingat masa sewa H-7 |
| Tenant 06 | `tenant06@ekost.test` | Pengingat masa sewa hari H |
| Tenant 07 | `tenant07@ekost.test` | Sewa terjadwal |
| Tenant 08 | `tenant08@ekost.test` | Riwayat sewa selesai |
| Tenant 09 | `tenant09@ekost.test` | Riwayat rent berstatus cancelled |
| Tenant 10 | `tenant10@ekost.test` | Booking menunggu pembayaran dan booking expired |
| Tenant 11 | `tenant11@ekost.test` | Instruksi pembayaran QRIS dan booking cancelled |
| Tenant 12 | `tenant12@ekost.test` | Percobaan pembayaran gagal |
| Tenant utama | `ade@gmail.com` | Sewa aktif dengan periode renewal terbuka |

Seluruh akun tenant menggunakan password `password123`.

Akun manager juga dibuat oleh seeder:

- `manager1@ekost.test`
- `manager2@ekost.test`

Namun, route manager pada source code saat ini masih dikomentari. Gunakan akun owner dan tenant untuk demo utama.

**Keamanan:** akun dan password di atas hanya untuk database demo lokal. Jangan gunakan password tersebut pada lingkungan publik/produksi.

---

## 7. Urutan demo yang disarankan

Urutan ini dibuat agar audiens memahami alur aplikasi dari sisi calon penyewa sampai pengelola.

### Sesi A — Pengunjung (guest)

Mulai dari halaman utama tanpa login.

1. Buka `http://127.0.0.1:8000`.
2. Tunjukkan halaman landing page dan informasi kost.
3. Buka daftar kost/cabang yang tersedia.
4. Pilih salah satu cabang.
5. Tunjukkan informasi cabang, fasilitas, dan daftar kamar.
6. Buka detail salah satu kamar.
7. Tunjukkan informasi harga, deskripsi, dan fasilitas kamar.
8. Jelaskan bahwa pengunjung perlu login sebagai tenant untuk melakukan booking.

**Tujuan sesi:** memperlihatkan bagaimana calon penyewa menemukan kost dan memeriksa informasi kamar sebelum memesan.

### Sesi B — Owner / pengelola

Logout jika masih menggunakan akun lain, lalu login dengan:

```text
Email    : owner@ekost.test
Password : password123
```

Setelah berhasil masuk, demonstrasikan modul berikut sesuai urutan menu yang tersedia.

#### 1. Dashboard owner

Tunjukkan:

- Ringkasan jumlah cabang.
- Inventaris kamar berdasarkan status.
- Data penyewa.
- Ringkasan booking.
- Ringkasan rent.
- Pendapatan dan transaksi pembayaran.
- Grafik pendapatan.
- Aktivitas terbaru.
- Informasi masa sewa yang akan berakhir.

Gunakan dashboard untuk menjelaskan bahwa owner dapat memantau seluruh cabang yang dimilikinya.

#### 2. Manajemen cabang

Buka menu cabang.

Demonstrasikan:

- Melihat daftar cabang.
- Melihat detail cabang.
- Menambah cabang jika diperlukan.
- Mengubah informasi cabang.
- Mengubah status cabang.
- Menentukan tipe kost putra, putri, atau campuran.
- Mengelola fasilitas cabang dan informasi lokasi.

Seeder menyiapkan beberapa cabang demo, termasuk cabang dengan status nonaktif untuk memperlihatkan variasi data.

#### 3. Manajemen fasilitas

Buka menu fasilitas.

Tunjukkan perbedaan fasilitas yang digunakan untuk:

- Fasilitas cabang.
- Fasilitas kamar.

Jelaskan bahwa fasilitas dapat digunakan kembali dan dihubungkan dengan cabang atau kamar sesuai jenisnya.

#### 4. Manajemen kamar

Buka menu kamar.

Demonstrasikan:

- Melihat daftar kamar.
- Menambahkan kamar.
- Mengubah informasi kamar.
- Mengatur harga sewa bulanan.
- Mengelola fasilitas kamar.
- Mengubah status kamar.

Data demo menyediakan variasi kamar aktif, nonaktif, dan maintenance.

#### 5. Manajemen ART

Buka menu ART/housemaid.

Tunjukkan:

- Daftar ART.
- Informasi ART.
- Status aktif/nonaktif.
- Penempatan ART ke cabang.

Pada rancangan terbaru, satu ART dapat ditempatkan pada lebih dari satu cabang yang dikelola owner.

#### 6. Data penyewa

Buka menu penyewa.

Tunjukkan daftar penyewa dan detail informasi penyewa yang memiliki transaksi atau masa sewa.

#### 7. Transaksi pembayaran dan masa sewa

Buka menu pembayaran dan rent.

Tunjukkan:

- Daftar pembayaran.
- Status pembayaran.
- Metode pembayaran.
- Nominal transaksi.
- Riwayat masa sewa.
- Status masa sewa.

Jelaskan bahwa pendapatan hanya dihitung dari pembayaran yang berstatus berhasil/paid.

#### 8. Notifikasi owner

Buka pusat notifikasi.

Tunjukkan contoh notifikasi:

- Booking baru.
- Pembayaran berhasil.
- Rent berhasil dibuat.
- Booking dibatalkan.
- Pengingat masa sewa.

Coba tandai satu notifikasi sebagai telah dibaca dan gunakan fitur tandai semua dibaca jika diperlukan.

### Sesi C — Tenant / penyewa

Logout dari owner, lalu login menggunakan akun tenant.

Untuk alur booking baru, gunakan:

```text
Email    : tenant10@ekost.test
Password : password123
```

Akun ini disiapkan dengan booking yang menunggu pembayaran dan booking expired. Untuk memperagakan booking baru dari awal, pilih kamar lain yang tersedia dan ikuti alur berikut.

#### 1. Dashboard tenant

Tunjukkan:

- Informasi masa sewa.
- Informasi kamar yang sedang atau akan ditempati.
- Ringkasan pembayaran.
- Riwayat pembayaran.
- Informasi pengajuan renewal jika tersedia.

#### 2. Melihat kamar dan melakukan booking

1. Kembali ke halaman publik atau daftar kamar.
2. Pilih kamar dengan status tersedia.
3. Buka detail kamar.
4. Tentukan durasi sewa dan tanggal mulai sesuai form.
5. Lanjutkan proses booking.
6. Tunjukkan detail booking yang berhasil dibuat.

Pastikan kamar yang dipilih memang tersedia dan belum memiliki benturan periode sewa.

#### 3. Memilih metode pembayaran

Pada halaman pembayaran, tunjukkan kelompok metode yang tersedia:

- Virtual Account: BCA, Mandiri, BNI, BRI, dan Permata.
- E-wallet: DANA, OVO, GoPay, dan ShopeePay.
- QRIS.

Pilih salah satu metode untuk melanjutkan.

#### 4. Instruksi dan simulasi pembayaran

1. Tunjukkan instruksi pembayaran sesuai metode yang dipilih.
2. Jelaskan bahwa pembayaran pada aplikasi merupakan simulasi.
3. Gunakan tombol/aksi simulasi pembayaran yang tersedia pada halaman.
4. Tunjukkan perubahan status pembayaran setelah simulasi berhasil.
5. Perlihatkan booking yang telah dikonfirmasi dan masa sewa yang terbentuk.

Tidak ada uang sungguhan yang ditransfer melalui alur simulasi ini.

#### 5. Membatalkan booking

Jika ingin menunjukkan fitur pembatalan:

1. Buka detail booking yang masih memenuhi syarat pembatalan.
2. Gunakan aksi pembatalan.
3. Isi alasan jika form memintanya.
4. Tunjukkan status booking setelah dibatalkan.

Gunakan booking demo yang memang masih dapat dibatalkan. Jangan menggunakan booking yang sudah dibayar dan dikonfirmasi jika aturan aplikasi tidak mengizinkannya.

#### 6. Informasi sewa dan kamar saya

Buka menu kamar saya dan informasi sewa.

Tunjukkan:

- Kamar yang sedang ditempati atau akan ditempati.
- Cabang kost.
- Periode sewa.
- Status sewa.
- Informasi akhir masa sewa.

#### 7. Riwayat pembayaran

Buka menu riwayat pembayaran.

Tunjukkan daftar transaksi, status, nominal, dan detail pembayaran.

#### 8. Renewal / perpanjangan sewa

Untuk demo renewal, logout lalu login menggunakan:

```text
Email    : ade@gmail.com
Password : password123
```

Akun ini memiliki masa sewa aktif yang berada dalam rentang pengajuan perpanjangan.

Langkah demo:

1. Buka dashboard tenant.
2. Tunjukkan informasi masa sewa yang sedang berjalan.
3. Tunjukkan bahwa periode pengajuan renewal sudah terbuka.
4. Buka halaman pengajuan perpanjangan.
5. Tunjukkan form pengajuan renewal.
6. Ajukan renewal jika ingin memperlihatkan proses dari awal.

Untuk memperlihatkan renewal yang sedang menunggu pembayaran, gunakan akun:

```text
Email    : tenant03@ekost.test
Password : password123
```

Akun tersebut disiapkan dengan pengajuan renewal berstatus menunggu pembayaran. Tunjukkan detail booking renewal dan instruksi pembayaran yang tersedia.

#### 9. Notifikasi tenant

Gunakan akun reminder untuk menunjukkan variasi notifikasi masa sewa:

| Jenis pengingat | Akun |
|---|---|
| H-30 | `tenant04@ekost.test` |
| H-7 | `tenant05@ekost.test` |
| Hari H | `tenant06@ekost.test` |

Password seluruh akun: `password123`.

Buka menu notifikasi, tunjukkan judul, pesan, tanggal, dan aksi untuk membuka detail notifikasi. Perlihatkan juga fitur tandai dibaca.

#### 10. Profil tenant

Buka menu profil.

Tunjukkan fitur yang tersedia pada halaman tersebut, seperti:

- Melihat data profil.
- Mengubah informasi profil.
- Mengubah email melalui proses OTP.
- Mengubah password.

Fitur perubahan email dan verifikasi OTP memerlukan konfigurasi pengiriman email yang sesuai. Jangan mengandalkan email OTP benar-benar terkirim sebelum konfigurasi mail server diuji.

---

## 8. Skenario demo singkat (10–15 menit)

Jika waktu presentasi terbatas, gunakan alur berikut.

| Durasi | Aktivitas | Akun |
|---:|---|---|
| 1 menit | Landing page dan daftar kamar | Guest |
| 2 menit | Dashboard dan manajemen cabang/kamar | Owner |
| 2 menit | Booking kamar | Tenant 10 atau akun tenant lain |
| 2 menit | Pilih metode dan simulasi pembayaran | Tenant |
| 2 menit | Dashboard owner, pembayaran, dan rent | Owner |
| 2 menit | Dashboard dan informasi sewa | Ade |
| 2 menit | Renewal dan notifikasi H-30/H-7/H | Ade / Tenant 04–06 |

Untuk demo yang lebih lengkap, lanjutkan dengan fasilitas, ART, penyewa, riwayat pembayaran, pembatalan booking, profil, dan pengelolaan notifikasi.

---

## 9. Checklist sebelum presentasi

### Persiapan teknis

- [ ] MySQL berjalan.
- [ ] Database demo sudah dibuat.
- [ ] `.env` sudah mengarah ke database yang benar.
- [ ] `php artisan migrate:fresh --seed` berhasil.
- [ ] `php artisan storage:link` sudah dijalankan jika diperlukan.
- [ ] `php artisan serve` berjalan.
- [ ] `npm run dev` berjalan atau aset sudah di-build.
- [ ] Halaman landing page dapat dibuka.
- [ ] Login owner berhasil.
- [ ] Login tenant berhasil.
- [ ] Tidak ada error penting pada terminal atau browser.

### Persiapan data

- [ ] Cabang dan kamar tampil.
- [ ] Fasilitas cabang dan kamar tampil.
- [ ] Data ART tampil.
- [ ] Dashboard owner menampilkan ringkasan data.
- [ ] Booking demo tampil.
- [ ] Pembayaran demo tampil.
- [ ] Data rent tampil.
- [ ] Notifikasi owner dan tenant tampil.
- [ ] Akun renewal dapat digunakan.
- [ ] Akun reminder H-30, H-7, dan H dapat digunakan.

### Persiapan presentasi

- [ ] Siapkan urutan login owner dan tenant.
- [ ] Siapkan akun dan password demo.
- [ ] Tentukan kamar yang akan digunakan untuk booking langsung.
- [ ] Hindari menghapus atau mengubah data penting sebelum skenario selesai.
- [ ] Siapkan screenshot cadangan jika koneksi atau server bermasalah.

---

## 10. Troubleshooting singkat

### Database connection error

Periksa `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` pada `.env`. Pastikan MySQL berjalan.

Setelah mengubah `.env`, jalankan:

```bash
php artisan optimize:clear
```

### Vite manifest not found

Jika aplikasi menampilkan error manifest Vite, jalankan:

```bash
npm install
npm run build
```

Atau untuk mode development, jalankan `npm run dev` dan biarkan terminal tetap aktif.

### Seeder gagal

Jalankan migration dan seeder secara terpisah untuk mengetahui sumber masalah:

```bash
php artisan migrate:fresh
php artisan db:seed
```

Periksa pesan error dan nama migration/tabel/kolom yang disebutkan. Pastikan file seeder yang digunakan sesuai dengan versi migration proyek.

### Gambar tidak tampil

Periksa apakah file gambar memang tersedia. Jalankan:

```bash
php artisan storage:link
```

Jika file sumber belum ada, unggah gambar baru melalui aplikasi atau siapkan file aset demo.

### Login manager tidak dapat diarahkan ke dashboard

Route manager pada `routes/web.php` masih dikomentari. Untuk demo saat ini, gunakan akun owner atau tenant.

---

## 11. Catatan penting untuk demo

1. Gunakan database lokal khusus demo.
2. Jangan menjalankan `migrate:fresh` pada database produksi.
3. Seluruh pembayaran pada demo adalah simulasi, bukan transaksi uang sungguhan.
4. Akun demo sudah disiapkan sebagai akun terverifikasi agar dapat langsung digunakan untuk login.
5. Fitur OTP email memerlukan konfigurasi mail yang benar.
6. Tanggal skenario masa sewa dan pengingat dibuat relatif terhadap tanggal seeder dijalankan. Jika database sudah lama tidak di-seed ulang, tanggal skenario bisa tidak lagi sesuai.
7. Route manager belum aktif pada source code yang diperiksa.
8. Sebelum presentasi, lakukan satu kali simulasi penuh dari login sampai logout untuk memastikan seluruh alur berjalan pada perangkat yang akan digunakan.

---

## 12. Penutup

Alur utama yang disarankan untuk memperkenalkan nesaKost adalah:

**Guest melihat kost → Tenant melakukan booking → Tenant menyelesaikan simulasi pembayaran → Sistem membentuk masa sewa → Owner memantau transaksi dan penyewa → Tenant mengelola informasi sewa dan mengajukan renewal → Sistem menampilkan notifikasi dan pengingat masa sewa.**

Dengan alur tersebut, demo tidak hanya memperlihatkan halaman aplikasi, tetapi juga menjelaskan hubungan antarfitur dan proses bisnis utama nesaKost.
