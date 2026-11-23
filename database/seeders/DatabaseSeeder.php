<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\OwnerActivityNotification;
use App\Notifications\RentExpirationNotification;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder data demo eKost.
 *
 * Akun demo (password seluruh akun: password123):
 * - owner@ekost.test
 * - manager1@ekost.test, manager2@ekost.test
 * - tenant01@ekost.test s.d. tenant12@ekost.test
 *
 * Jalankan pada database demo/development:
 *   php artisan migrate:fresh --seed
 *
 * Seeder ini sengaja membangun ulang data domain demo. Jangan jalankan
 * migrate:fresh pada database produksi atau database yang berisi data penting.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $today = Carbon::today();
            $password = Hash::make('password123');

            /*
             * 1. AKUN
             * Semua akun demo ditandai terverifikasi agar alur demo dapat
             * langsung masuk ke dashboard. Alur OTP tetap dapat diuji dengan
             * akun baru yang didaftarkan melalui aplikasi.
             */
            $userDefinitions = [
                ['email' => 'owner@ekost.test', 'fname' => 'Budi', 'lname' => 'Santoso', 'phone' => '081200000001', 'address' => 'Ketintang, Surabaya', 'role' => 'owner'],
                ['email' => 'manager1@ekost.test', 'fname' => 'Rina', 'lname' => 'Lestari', 'phone' => '081200000002', 'address' => 'Surabaya', 'role' => 'manager'],
                ['email' => 'manager2@ekost.test', 'fname' => 'Dimas', 'lname' => 'Pratama', 'phone' => '081200000003', 'address' => 'Surabaya', 'role' => 'manager'],

                ['email' => 'tenant01@ekost.test', 'fname' => 'Andi', 'lname' => 'Saputra', 'phone' => '081300000001', 'address' => 'Surabaya', 'role' => 'tenant'],
                ['email' => 'tenant02@ekost.test', 'fname' => 'Siti', 'lname' => 'Rahma', 'phone' => '081300000002', 'address' => 'Gresik', 'role' => 'tenant'],
                ['email' => 'tenant03@ekost.test', 'fname' => 'Fajar', 'lname' => 'Maulana', 'phone' => '081300000003', 'address' => 'Lamongan', 'role' => 'tenant'],
                ['email' => 'tenant04@ekost.test', 'fname' => 'Nadia', 'lname' => 'Putri', 'phone' => '081300000004', 'address' => 'Malang', 'role' => 'tenant'],
                ['email' => 'tenant05@ekost.test', 'fname' => 'Rizky', 'lname' => 'Firmansyah', 'phone' => '081300000005', 'address' => 'Kediri', 'role' => 'tenant'],
                ['email' => 'tenant06@ekost.test', 'fname' => 'Dewi', 'lname' => 'Anggraini', 'phone' => '081300000006', 'address' => 'Madiun', 'role' => 'tenant'],
                ['email' => 'tenant07@ekost.test', 'fname' => 'Yoga', 'lname' => 'Prakoso', 'phone' => '081300000007', 'address' => 'Jombang', 'role' => 'tenant'],
                ['email' => 'tenant08@ekost.test', 'fname' => 'Intan', 'lname' => 'Permata', 'phone' => '081300000008', 'address' => 'Mojokerto', 'role' => 'tenant'],
                ['email' => 'tenant09@ekost.test', 'fname' => 'Rafi', 'lname' => 'Hidayat', 'phone' => '081300000009', 'address' => 'Sidoarjo', 'role' => 'tenant'],
                ['email' => 'tenant10@ekost.test', 'fname' => 'Maya', 'lname' => 'Safitri', 'phone' => '081300000010', 'address' => 'Pasuruan', 'role' => 'tenant'],
                ['email' => 'tenant11@ekost.test', 'fname' => 'Ilham', 'lname' => 'Akbar', 'phone' => '081300000011', 'address' => 'Bojonegoro', 'role' => 'tenant'],
                ['email' => 'tenant12@ekost.test', 'fname' => 'Putri', 'lname' => 'Aulia', 'phone' => '081300000012', 'address' => 'Mojokerto', 'role' => 'tenant'],
                ['email' => 'ade@gmail.com', 'fname' => 'Ade', 'lname' => 'Sopian', 'phone' => '081300000013', 'address' => 'Ketintang, Surabaya', 'role' => 'tenant'],
            ];

            $userIds = [];
            foreach ($userDefinitions as $definition) {
                $userIds[$definition['email']] = DB::table('users')->insertGetId([
                    'email' => $definition['email'],
                    'email_verified_at' => $now,
                    'fname' => $definition['fname'],
                    'lname' => $definition['lname'],
                    'phone_number' => $definition['phone'],
                    'address' => $definition['address'],
                    'password' => $password,
                    'role' => $definition['role'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $ownerId = DB::table('owners')->insertGetId([
                'user_id' => $userIds['owner@ekost.test'],
                'address' => 'Jl. Ketintang, Surabaya, Jawa Timur',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $managerIds = [];
            foreach (['manager1@ekost.test', 'manager2@ekost.test'] as $email) {
                $managerIds[$email] = DB::table('managers')->insertGetId([
                    'user_id' => $userIds[$email],
                    'owner_id' => $ownerId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $tenantProfiles = [
                'tenant01@ekost.test' => ['Mahasiswa Sistem Informasi', 'Agus Saputra', '082100000001'],
                'tenant02@ekost.test' => ['Mahasiswa Manajemen', 'Sri Rahmawati', '082100000002'],
                'tenant03@ekost.test' => ['Mahasiswa Teknik Informatika', 'Bambang Maulana', '082100000003'],
                'tenant04@ekost.test' => ['Mahasiswa Pendidikan', 'Dian Puspita', '082100000004'],
                'tenant05@ekost.test' => ['Mahasiswa Akuntansi', 'Rudi Firmansyah', '082100000005'],
                'tenant06@ekost.test' => ['Mahasiswa Psikologi', 'Wati Anggraini', '082100000006'],
                'tenant07@ekost.test' => ['Mahasiswa Ekonomi', 'Bambang Prakoso', '082100000007'],
                'tenant08@ekost.test' => ['Mahasiswa Sistem Informasi', 'Sari Permata', '082100000008'],
                'tenant09@ekost.test' => ['Karyawan swasta', 'Dimas Haryanto', '082100000009'],
                'tenant10@ekost.test' => ['Mahasiswa Teknik Sipil', 'Rina Amelia', '082100000010'],
                'tenant11@ekost.test' => ['Mahasiswa Hukum', 'Agus Setiawan', '082100000011'],
                'tenant12@ekost.test' => ['Mahasiswa Desain Komunikasi Visual', 'Dewi Kartika', '082100000012'],
                'ade@gmail.com' => ['Mahasiswa Sistem Informasi', 'Kontak Darurat Ade', '082100000013'],
            ];

            $tenantIds = [];
            foreach ($tenantProfiles as $email => [$profession, $emergencyName, $emergencyNumber]) {
                $tenantIds[$email] = DB::table('tenants')->insertGetId([
                    'user_id' => $userIds[$email],
                    'profession' => $profession,
                    'emergency_name' => $emergencyName,
                    'emergency_number' => $emergencyNumber,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            /*
             * 2. FASILITAS
             * facility_name boleh sama untuk jenis branch dan room, tetapi
             * kombinasi owner_id + facility_name + facility_type unik.
             */
            $facilityDefinitions = [
                ['Wi-Fi', 'Internet nirkabel di area kost.', 'branch'],
                ['Parkir Motor', 'Area parkir motor penghuni.', 'branch'],
                ['Dapur Bersama', 'Dapur bersama untuk penghuni.', 'branch'],
                ['Laundry', 'Area mencuci dan menjemur pakaian.', 'branch'],
                ['Keamanan 24 Jam', 'Keamanan dan pemantauan selama 24 jam.', 'branch'],
                ['CCTV', 'Pemantauan area umum kost.', 'branch'],
                ['Wi-Fi', 'Internet nirkabel yang tersedia di kamar.', 'room'],
                ['Kamar Mandi Dalam', 'Kamar mandi pribadi di dalam kamar.', 'room'],
                ['AC', 'Pendingin udara di dalam kamar.', 'room'],
                ['Kasur', 'Kasur dan tempat tidur.', 'room'],
                ['Lemari', 'Lemari pakaian.', 'room'],
                ['Meja Belajar', 'Meja untuk belajar atau bekerja.', 'room'],
            ];

            $facilityIds = ['branch' => [], 'room' => []];
            foreach ($facilityDefinitions as [$name, $description, $type]) {
                $facilityIds[$type][$name] = DB::table('facility')->insertGetId([
                    'owner_id' => $ownerId,
                    'facility_name' => $name,
                    'facility_type' => $type,
                    'description' => $description,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            /*
             * 3. CABANG + PENEMPATAN MANAGER
             */
            $branchDefinitions = [
                [
                    'name' => 'Pondok Biroe Ketintang',
                    'address' => 'Jl. Ketintang, Ketintang, Gayungan, Surabaya, Jawa Timur',
                    'latitude' => -7.3163179, 'longitude' => 112.7281679,
                    'gender_type' => 'putra', 'manager' => 'manager1@ekost.test',
                    'status' => 'active',
                    'facilities' => ['Wi-Fi', 'Parkir Motor', 'Dapur Bersama', 'Laundry', 'Keamanan 24 Jam', 'CCTV'],
                ],
                [
                    'name' => 'Pondok Biroe Lidah Wetan',
                    'address' => 'Jl. Lidah Wetan, Lakarsantri, Surabaya, Jawa Timur',
                    'latitude' => -7.2972519, 'longitude' => 112.6730335,
                    'gender_type' => 'putri', 'manager' => 'manager2@ekost.test',
                    'status' => 'active',
                    'facilities' => ['Wi-Fi', 'Parkir Motor', 'Laundry', 'Keamanan 24 Jam'],
                ],
                [
                    'name' => 'Pondok Biroe Jemursari',
                    'address' => 'Jl. Jemursari, Wonocolo, Surabaya, Jawa Timur',
                    'latitude' => -7.3162913, 'longitude' => 112.7300400,
                    'gender_type' => 'campuran', 'manager' => 'manager1@ekost.test',
                    'status' => 'active',
                    'facilities' => ['Wi-Fi', 'Parkir Motor', 'Dapur Bersama', 'Keamanan 24 Jam'],
                ],
                [
                    'name' => 'Pondok Biroe Wonokromo',
                    'address' => 'Jl. Wonokromo, Wonokromo, Surabaya, Jawa Timur',
                    'latitude' => -7.3095000, 'longitude' => 112.7352000,
                    'gender_type' => 'campuran', 'manager' => null,
                    'status' => 'inactive',
                    'facilities' => ['Wi-Fi', 'Parkir Motor'],
                ],
            ];

            $branches = [];
            foreach ($branchDefinitions as $index => $definition) {
                $branchId = DB::table('branch_dorm')->insertGetId([
                    'owner_id' => $ownerId,
                    'name' => $definition['name'],
                    'address' => $definition['address'],
                    'latitude' => $definition['latitude'],
                    'longitude' => $definition['longitude'],
                    'gender_type' => $definition['gender_type'],
                    'image_path' => null,
                    'status' => $definition['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($definition['manager']) {
                    DB::table('manager_branch')->insert([
                        'manager_id' => $managerIds[$definition['manager']],
                        'branch_dorm_id' => $branchId,
                        'owner_id' => $ownerId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                foreach ($definition['facilities'] as $facilityName) {
                    DB::table('branch_facility')->insert([
                        'branch_dorm_id' => $branchId,
                        'facility_id' => $facilityIds['branch'][$facilityName],
                    ]);
                }

                $branches[$index] = $branchId;
            }

            /*
             * 4. ART (HOUSEMAID) + MULTI-BRANCH ASSIGNMENT
             * Mengikuti schema terbaru: housemaid.owner_id dan pivot
             * housemaid_branch. Kolom lama branch_dorm_id/name tidak digunakan.
             */
            $housemaidDefinitions = [
                ['fname' => 'Sulastri', 'lname' => 'Wati', 'phone' => '083100000001', 'salary' => 1800000, 'status' => 'active', 'branches' => [0, 2]],
                ['fname' => 'Yuni', 'lname' => 'Lestari', 'phone' => '083100000002', 'salary' => 1750000, 'status' => 'active', 'branches' => [1]],
                ['fname' => 'Harun', 'lname' => 'Santoso', 'phone' => '083100000003', 'salary' => 2000000, 'status' => 'active', 'branches' => [0, 1, 2]],
                ['fname' => 'Mira', 'lname' => 'Anggraini', 'phone' => '083100000004', 'salary' => 1650000, 'status' => 'inactive', 'branches' => []],
            ];

            foreach ($housemaidDefinitions as $definition) {
                $housemaidId = DB::table('housemaid')->insertGetId([
                    'owner_id' => $ownerId,
                    'fname' => $definition['fname'],
                    'lname' => $definition['lname'],
                    'salary' => $definition['salary'],
                    'phone_number' => $definition['phone'],
                    'address' => 'Surabaya, Jawa Timur',
                    'status' => $definition['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($definition['branches'] as $branchIndex) {
                    DB::table('housemaid_branch')->insert([
                        'housemaid_id' => $housemaidId,
                        'branch_dorm_id' => $branches[$branchIndex],
                    ]);
                }
            }

            /*
             * 5. KAMAR + PIVOT FASILITAS
             * image_path dibiarkan null karena arsip proyek tidak menyertakan
             * file gambar demo. Pengelola dapat mengunggah gambar dari UI.
             */
            $roomDefinitions = [
                ['branch' => 0, 'code' => 'A-101', 'type' => 'Single', 'price' => 900000, 'status' => 'active'],
                ['branch' => 0, 'code' => 'A-102', 'type' => 'Single', 'price' => 950000, 'status' => 'active'],
                ['branch' => 0, 'code' => 'A-103', 'type' => 'Deluxe', 'price' => 1400000, 'status' => 'active'],
                ['branch' => 0, 'code' => 'A-104', 'type' => 'Deluxe', 'price' => 1450000, 'status' => 'active'],
                ['branch' => 0, 'code' => 'A-105', 'type' => 'Single', 'price' => 850000, 'status' => 'maintenance'],

                ['branch' => 1, 'code' => 'B-201', 'type' => 'Single', 'price' => 800000, 'status' => 'active'],
                ['branch' => 1, 'code' => 'B-202', 'type' => 'Deluxe', 'price' => 1300000, 'status' => 'active'],
                ['branch' => 1, 'code' => 'B-203', 'type' => 'Deluxe', 'price' => 1350000, 'status' => 'active'],
                ['branch' => 1, 'code' => 'B-204', 'type' => 'Suite', 'price' => 1800000, 'status' => 'active'],
                ['branch' => 1, 'code' => 'B-205', 'type' => 'Single', 'price' => 825000, 'status' => 'inactive'],

                ['branch' => 2, 'code' => 'C-301', 'type' => 'Single', 'price' => 875000, 'status' => 'active'],
                ['branch' => 2, 'code' => 'C-302', 'type' => 'Deluxe', 'price' => 1250000, 'status' => 'active'],
                ['branch' => 2, 'code' => 'C-303', 'type' => 'Suite', 'price' => 1750000, 'status' => 'active'],
                ['branch' => 2, 'code' => 'C-304', 'type' => 'Single', 'price' => 900000, 'status' => 'active'],

                ['branch' => 3, 'code' => 'D-401', 'type' => 'Single', 'price' => 750000, 'status' => 'inactive'],
            ];

            $rooms = [];
            foreach ($roomDefinitions as $definition) {
                $roomId = DB::table('room')->insertGetId([
                    'room_code' => $definition['code'],
                    'branch_dorm_id' => $branches[$definition['branch']],
                    'type' => $definition['type'],
                    'monthly_price' => $definition['price'],
                    'image_path' => null,
                    'description' => "Kamar {$definition['type']} {$definition['code']} dengan fasilitas yang nyaman untuk penghuni.",
                    'status' => $definition['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $rooms[$definition['code']] = [
                    'id' => $roomId,
                    'price' => $definition['price'],
                    'branch' => $definition['branch'],
                ];

                $roomFacilityNames = ['Kasur', 'Lemari', 'Meja Belajar', 'Wi-Fi'];
                if (in_array($definition['type'], ['Deluxe', 'Suite'], true)) {
                    $roomFacilityNames = array_merge($roomFacilityNames, ['AC', 'Kamar Mandi Dalam']);
                }

                foreach (array_unique($roomFacilityNames) as $facilityName) {
                    DB::table('room_facility')->insert([
                        'room_id' => $roomId,
                        'facility_id' => $facilityIds['room'][$facilityName],
                    ]);
                }
            }

            /*
             * 6. HELPER TRANSAKSI
             * Semua booking paid menghasilkan satu rent. Payment method memakai
             * kode kanal yang benar-benar dipakai UI simulasi pembayaran.
             */
            $createBooking = function (array $data) use ($now, $rooms, $tenantIds): array {
                $room = $rooms[$data['room']];
                $duration = (int) $data['duration'];
                $start = Carbon::parse($data['start'])->startOfDay();
                $end = Carbon::parse($data['end'] ?? $start->copy()->addMonthsNoOverflow($duration)->subDay())->startOfDay();
                $createdAt = Carbon::parse($data['created_at'] ?? $now);
                $paymentStatus = $data['payment_status'];
                $paidAt = $paymentStatus === 'paid'
                    ? Carbon::parse($data['paid_at'] ?? $createdAt->copy()->addMinutes(10))
                    : null;
                $expiresAt = isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;
                $total = $room['price'] * $duration;

                $bookingId = DB::table('booking')->insertGetId([
                    'booking_code' => null,
                    'tenant_id' => $tenantIds[$data['tenant']],
                    'room_id' => $room['id'],
                    'booking_type' => $data['booking_type'] ?? 'new',
                    'previous_rent_id' => $data['previous_rent_id'] ?? null,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'duration_month' => $duration,
                    'price_snapshot' => $room['price'],
                    'status' => $data['booking_status'],
                    'expires_at' => $expiresAt,
                    'cancelled_at' => $data['cancelled_at'] ?? null,
                    'cancellation_reason' => $data['cancellation_reason'] ?? null,
                    'created_at' => $createdAt,
                    'updated_at' => $paidAt ?? $createdAt,
                ]);
                DB::table('booking')->where('id', $bookingId)->update([
                    'booking_code' => 'BOOKING-' . $bookingId,
                ]);

                $paymentId = DB::table('payment')->insertGetId([
                    'payment_code' => null,
                    'booking_id' => $bookingId,
                    'amount' => $total,
                    'payment_method' => $data['payment_method'] ?? null,
                    'provider' => $data['provider'] ?? null,
                    'provider_reference' => $data['provider_reference'] ?? null,
                    'payment_reference' => $data['payment_reference'] ?? null,
                    'channel_code' => $data['channel_code'] ?? null,
                    'payment_instruction' => $data['payment_instruction'] ?? null,
                    'qr_code_url' => $data['qr_code_url'] ?? null,
                    'checkout_url' => $data['checkout_url'] ?? null,
                    'status' => $paymentStatus,
                    'paid_at' => $paidAt,
                    'expires_at' => $expiresAt,
                    'created_at' => $createdAt,
                    'updated_at' => $paidAt ?? $createdAt,
                ]);
                DB::table('payment')->where('id', $paymentId)->update([
                    'payment_code' => 'PAY-' . $paymentId,
                ]);

                $rentId = null;
                if (($data['create_rent'] ?? false) === true) {
                    $rentId = DB::table('rent')->insertGetId([
                        'rent_code' => null,
                        'booking_id' => $bookingId,
                        'tenant_id' => $tenantIds[$data['tenant']],
                        'room_id' => $room['id'],
                        'start_date' => $start->toDateString(),
                        'end_date' => $end->toDateString(),
                        'status' => $data['rent_status'],
                        'total_price' => $total,
                        'created_at' => $paidAt ?? $createdAt,
                        'updated_at' => $paidAt ?? $createdAt,
                    ]);
                    DB::table('rent')->where('id', $rentId)->update([
                        'rent_code' => 'RENT-' . $rentId,
                    ]);
                }

                return [
                    'booking_id' => $bookingId,
                    'payment_id' => $paymentId,
                    'rent_id' => $rentId,
                    'tenant_id' => $tenantIds[$data['tenant']],
                    'room_id' => $room['id'],
                    'room_code' => $data['room'],
                    'start' => $start,
                    'end' => $end,
                    'total' => $total,
                ];
            };

            /*
             * 7. RENT BERBAYAR UNTUK DASHBOARD, RIWAYAT, DAN FILTER
             *
             * Data sengaja tersebar pada rent completed, active, scheduled,
             * dan cancelled. Setiap tenant hanya memiliki satu rent aktif/
             * terjadwal pada satu waktu.
             */
            $rentRecords = [];

            $rentRecords['completed_old'] = $createBooking([
                'tenant' => 'tenant01@ekost.test', 'room' => 'A-101',
                'start' => $today->copy()->subMonths(6)->subDays(1),
                'end' => $today->copy()->subMonths(3)->subDays(1),
                'duration' => 3, 'booking_status' => 'confirmed',
                'payment_status' => 'paid', 'payment_method' => 'va_bca',
                'rent_status' => 'completed', 'create_rent' => true,
                'created_at' => $today->copy()->subMonths(6)->subDays(16),
            ]);

            $rentRecords['eligible_renewal'] = $createBooking([
                'tenant' => 'ade@gmail.com', 'room' => 'A-102',
                'start' => $today->copy()->addDays(14)->subMonthsNoOverflow(3)->addDay(),
                'end' => $today->copy()->addDays(14),
                'duration' => 3, 'booking_status' => 'confirmed',
                'payment_status' => 'paid', 'payment_method' => 'qris',
                'rent_status' => 'active', 'create_rent' => true,
                'created_at' => $today->copy()->subMonths(3)->subDays(14),
            ]);

            $rentRecords['renewal_pending_base'] = $createBooking([
                'tenant' => 'tenant03@ekost.test', 'room' => 'A-103',
                'start' => $today->copy()->addDays(20)->subMonthsNoOverflow(3)->addDay(),
                'end' => $today->copy()->addDays(20),
                'duration' => 3, 'booking_status' => 'confirmed',
                'payment_status' => 'paid', 'payment_method' => 'va_mandiri',
                'rent_status' => 'active', 'create_rent' => true,
                'created_at' => $today->copy()->subMonths(3)->subDays(13),
            ]);

            $rentRecords['reminder_h30'] = $createBooking([
                'tenant' => 'tenant04@ekost.test', 'room' => 'B-201',
                'start' => $today->copy()->addDays(30)->subMonthsNoOverflow(3)->addDay(),
                'end' => $today->copy()->addDays(30),
                'duration' => 3, 'booking_status' => 'confirmed',
                'payment_status' => 'paid', 'payment_method' => 'gopay',
                'rent_status' => 'active', 'create_rent' => true,
                'created_at' => $today->copy()->subMonths(3)->subDays(13),
            ]);

            $rentRecords['reminder_h7'] = $createBooking([
                'tenant' => 'tenant05@ekost.test', 'room' => 'B-202',
                'start' => $today->copy()->addDays(7)->subMonthsNoOverflow(3)->addDay(),
                'end' => $today->copy()->addDays(7),
                'duration' => 3, 'booking_status' => 'confirmed',
                'payment_status' => 'paid', 'payment_method' => 'va_bni',
                'rent_status' => 'active', 'create_rent' => true,
                'created_at' => $today->copy()->subMonths(3)->subDays(13),
            ]);

            $rentRecords['reminder_h'] = $createBooking([
                'tenant' => 'tenant06@ekost.test', 'room' => 'B-203',
                'start' => $today->copy()->subMonthsNoOverflow(3)->addDay(),
                'end' => $today,
                'duration' => 3, 'booking_status' => 'confirmed',
                'payment_status' => 'paid', 'payment_method' => 'ovo',
                'rent_status' => 'active', 'create_rent' => true,
                'created_at' => $today->copy()->subMonths(3)->subDays(13),
            ]);

            $rentRecords['scheduled'] = $createBooking([
                'tenant' => 'tenant07@ekost.test', 'room' => 'C-301',
                'start' => $today->copy()->addDays(10),
                'duration' => 3, 'booking_status' => 'confirmed',
                'payment_status' => 'paid', 'payment_method' => 'va_bri',
                'rent_status' => 'scheduled', 'create_rent' => true,
                'created_at' => $today->copy()->subDays(4),
            ]);

            $rentRecords['completed_recent'] = $createBooking([
                'tenant' => 'tenant08@ekost.test', 'room' => 'C-302',
                'start' => $today->copy()->subDays(10)->subMonthsNoOverflow(4)->addDay(),
                'end' => $today->copy()->subDays(10),
                'duration' => 4, 'booking_status' => 'confirmed',
                'payment_status' => 'paid', 'payment_method' => 'shopeepay',
                'rent_status' => 'completed', 'create_rent' => true,
                'created_at' => $today->copy()->subMonths(4)->subDays(13),
            ]);

            $rentRecords['cancelled_rent'] = $createBooking([
                'tenant' => 'tenant09@ekost.test', 'room' => 'C-303',
                'start' => $today->copy()->subMonths(5)->addDay(),
                'end' => $today->copy()->subMonths(2),
                'duration' => 3, 'booking_status' => 'confirmed',
                'payment_status' => 'paid', 'payment_method' => 'va_permata',
                'rent_status' => 'cancelled', 'create_rent' => true,
                'created_at' => $today->copy()->subMonths(5)->subDays(14),
            ]);

            /*
             * 8. BOOKING RENEWAL
             * tenant03 memiliki rent aktif dan satu pengajuan renewal pending
             * dengan tenggat 72 jam. tenant02 tetap eligible dan belum memiliki
             * renewal, sehingga alur pengajuan baru juga bisa didemokan.
             */
            $baseRentId = $rentRecords['renewal_pending_base']['rent_id'];
            $baseRentEnd = $rentRecords['renewal_pending_base']['end'];
            $rentRecords['renewal_booking'] = $createBooking([
                'tenant' => 'tenant03@ekost.test', 'room' => 'A-103',
                'start' => $baseRentEnd->copy()->addDay(),
                'duration' => 3, 'booking_type' => 'renewal',
                'previous_rent_id' => $baseRentId,
                'booking_status' => 'pending_payment',
                'payment_status' => 'pending',
                'payment_method' => 'qris',
                'provider' => 'simulation',
                'provider_reference' => 'QR-RENEWAL-DEMO-001',
                'payment_reference' => 'QR-RENEWAL-DEMO-001',
                'channel_code' => 'QRIS',
                'payment_instruction' => 'Instruksi QRIS simulasi untuk pembayaran perpanjangan sewa. Pindai kode QR demo pada halaman pembayaran.',
                'expires_at' => $now->copy()->addHours(72),
                'create_rent' => false,
                'created_at' => $now->copy()->subMinutes(3),
            ]);

            /*
             * 9. BOOKING REGULER DAN PAYMENT EDGE CASE
             * - pending belum memilih kanal pembayaran
             * - pending dengan instruksi QRIS
             * - gagal (bisa dicoba ulang selama booking berlaku)
             * - expired
             * - cancelled dengan alasan dan timestamp
             */
            $rentRecords['pending_unselected'] = $createBooking([
                'tenant' => 'tenant10@ekost.test', 'room' => 'A-104',
                'start' => $today->copy()->addDays(15),
                'duration' => 3, 'booking_status' => 'pending_payment',
                'payment_status' => 'pending',
                'expires_at' => $now->copy()->addMinutes(25),
                'create_rent' => false,
                'created_at' => $now->copy()->subMinutes(5),
            ]);

            $rentRecords['pending_qris'] = $createBooking([
                'tenant' => 'tenant11@ekost.test', 'room' => 'B-204',
                'start' => $today->copy()->addDays(20),
                'duration' => 1, 'booking_status' => 'pending_payment',
                'payment_status' => 'pending',
                'payment_method' => 'qris',
                'provider' => 'simulation',
                'provider_reference' => 'QR-DEMO-20261123-001',
                'payment_reference' => 'QR-DEMO-20261123-001',
                'channel_code' => 'QRIS',
                'payment_instruction' => 'Buka aplikasi e-wallet atau mobile banking yang mendukung QRIS, lalu pindai kode QR pembayaran pada halaman instruksi.',
                'expires_at' => $now->copy()->addMinutes(25),
                'create_rent' => false,
                'created_at' => $now->copy()->subMinutes(4),
            ]);

            $rentRecords['failed_payment'] = $createBooking([
                'tenant' => 'tenant12@ekost.test', 'room' => 'C-304',
                'start' => $today->copy()->addDays(30),
                'duration' => 3, 'booking_status' => 'pending_payment',
                'payment_status' => 'failed',
                'payment_method' => 'gopay',
                'provider' => 'simulation',
                'provider_reference' => 'GOPAY-DEMO-FAILED-001',
                'payment_reference' => 'GOPAY-DEMO-FAILED-001',
                'channel_code' => 'GOPAY',
                'payment_instruction' => 'Simulasi pembayaran GoPay gagal. Pengguna dapat memilih kanal pembayaran lain.',
                'expires_at' => $now->copy()->addMinutes(20),
                'create_rent' => false,
                'created_at' => $now->copy()->subMinutes(10),
            ]);

            $rentRecords['expired_booking'] = $createBooking([
                'tenant' => 'tenant10@ekost.test', 'room' => 'A-104',
                'start' => $today->copy()->addDays(60),
                'duration' => 1, 'booking_status' => 'expired',
                'payment_status' => 'expired',
                'expires_at' => $now->copy()->subHours(2),
                'create_rent' => false,
                'created_at' => $now->copy()->subHours(3),
            ]);

            $rentRecords['cancelled_booking'] = $createBooking([
                'tenant' => 'tenant11@ekost.test', 'room' => 'B-204',
                'start' => $today->copy()->addDays(75),
                'duration' => 3, 'booking_status' => 'cancelled',
                'payment_status' => 'cancelled',
                'expires_at' => $now->copy()->addMinutes(10),
                'cancelled_at' => $now->copy()->subDay(),
                'cancellation_reason' => 'Perubahan rencana tempat tinggal.',
                'create_rent' => false,
                'created_at' => $now->copy()->subDays(2),
            ]);

            /*
             * 10. NOTIFIKASI DATABASE
             * Notifikasi disiapkan untuk owner dan tenant agar daftar notifikasi,
             * badge belum dibaca, serta halaman detail dapat langsung diperagakan.
             */
            $ownerUserId = $userIds['owner@ekost.test'];

            $ownerNotifications = [
                [
                    'type' => 'booking_created',
                    'key' => 'booking:' . $rentRecords['pending_unselected']['booking_id'] . ':created',
                    'title' => 'Booking baru diterima',
                    'message' => 'Penyewa mengajukan booking kamar A-104 di Pondok Biroe Ketintang.',
                    'category' => 'booking', 'icon' => 'calendar-plus',
                    'action_url' => '/owner/dashboard',
                    'booking_id' => $rentRecords['pending_unselected']['booking_id'],
                ],
                [
                    'type' => 'payment_paid',
                    'key' => 'payment:' . $rentRecords['eligible_renewal']['payment_id'] . ':paid',
                    'title' => 'Pembayaran berhasil diterima',
                    'message' => 'Pembayaran penyewa untuk kamar A-102 berhasil diterima.',
                    'category' => 'payment', 'icon' => 'wallet',
                    'action_url' => '/owner/payments',
                    'payment_id' => $rentRecords['eligible_renewal']['payment_id'],
                ],
                [
                    'type' => 'rent_created',
                    'key' => 'rent:' . $rentRecords['eligible_renewal']['rent_id'] . ':created',
                    'title' => 'Sewa baru berhasil dibuat',
                    'message' => 'Masa sewa kamar A-102 telah dibuat dan sedang berlangsung.',
                    'category' => 'rent', 'icon' => 'file-text',
                    'action_url' => '/owner/rents',
                    'rent_id' => $rentRecords['eligible_renewal']['rent_id'],
                ],
                [
                    'type' => 'booking_cancelled',
                    'key' => 'booking:' . $rentRecords['cancelled_booking']['booking_id'] . ':cancelled',
                    'title' => 'Booking dibatalkan',
                    'message' => 'Salah satu booking telah dibatalkan oleh penyewa.',
                    'category' => 'booking', 'icon' => 'calendar-x',
                    'action_url' => '/owner/dashboard',
                    'booking_id' => $rentRecords['cancelled_booking']['booking_id'],
                ],
                [
                    'type' => 'rent_expiration',
                    'key' => 'rent:' . $rentRecords['reminder_h30']['rent_id'] . ':expiration:H-30',
                    'title' => 'Masa sewa akan berakhir 30 hari lagi',
                    'message' => 'Masa sewa penyewa pada kamar B-201 akan berakhir dalam 30 hari.',
                    'category' => 'rent', 'icon' => 'clock',
                    'action_url' => '/owner/rents',
                    'rent_id' => $rentRecords['reminder_h30']['rent_id'],
                ],
                [
                    'type' => 'rent_expiration',
                    'key' => 'rent:' . $rentRecords['reminder_h7']['rent_id'] . ':expiration:H-7',
                    'title' => 'Masa sewa akan berakhir 7 hari lagi',
                    'message' => 'Masa sewa penyewa pada kamar B-202 akan berakhir dalam 7 hari.',
                    'category' => 'rent', 'icon' => 'clock',
                    'action_url' => '/owner/rents',
                    'rent_id' => $rentRecords['reminder_h7']['rent_id'],
                ],
                [
                    'type' => 'rent_expiration',
                    'key' => 'rent:' . $rentRecords['reminder_h']['rent_id'] . ':expiration:H',
                    'title' => 'Masa sewa berakhir hari ini',
                    'message' => 'Masa sewa penyewa pada kamar B-203 berakhir hari ini.',
                    'category' => 'rent', 'icon' => 'clock',
                    'action_url' => '/owner/rents',
                    'rent_id' => $rentRecords['reminder_h']['rent_id'],
                ],
            ];

            foreach ($ownerNotifications as $index => $item) {
                $payload = [
                    'event_type' => $item['type'],
                    'event_key' => $item['key'],
                    'title' => $item['title'],
                    'message' => $item['message'],
                    'category' => $item['category'],
                    'icon' => $item['icon'],
                    'action_url' => $item['action_url'],
                    'created_at_label' => $now->copy()->subMinutes(15 + $index * 20)->toIso8601String(),
                ];
                foreach (['booking_id', 'payment_id', 'rent_id'] as $key) {
                    if (isset($item[$key])) {
                        $payload[$key] = $item[$key];
                    }
                }

                DB::table('notifications')->insert([
                    'id' => (string) Str::uuid(),
                    'type' => OwnerActivityNotification::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $ownerUserId,
                    'data' => json_encode($payload, JSON_THROW_ON_ERROR),
                    'read_at' => $index === 1 ? $now->copy()->subMinutes(5) : null,
                    'created_at' => $now->copy()->subMinutes(15 + $index * 20),
                    'updated_at' => $now,
                ]);
            }

            /*
             * 11. REMINDER H-30, H-7, DAN H
             * Satu baris reminder dan email dibuat untuk setiap rent. Ini
             * mencegah scheduler mengirim ulang reminder demo setelah seeding.
             */
            $reminderDefinitions = [
                ['key' => 'reminder_h30', 'type' => 'H-30'],
                ['key' => 'reminder_h7', 'type' => 'H-7'],
                ['key' => 'reminder_h', 'type' => 'H'],
            ];

            foreach ($reminderDefinitions as $definition) {
                $record = $rentRecords[$definition['key']];
                $rentId = $record['rent_id'];
                $tenantEmail = match ($definition['type']) {
                    'H-30' => 'tenant04@ekost.test',
                    'H-7' => 'tenant05@ekost.test',
                    default => 'tenant06@ekost.test',
                };
                $sentAt = $now->copy()->subMinutes(30);

                DB::table('rent_expiration_reminders')->insert([
                    'rent_id' => $rentId,
                    'reminder_type' => $definition['type'],
                    'sent_at' => $sentAt,
                    'created_at' => $sentAt,
                    'updated_at' => $sentAt,
                ]);

                DB::table('rent_expiration_reminder_emails')->insert([
                    'rent_id' => $rentId,
                    'user_id' => $userIds[$tenantEmail],
                    'reminder_type' => $definition['type'],
                    'recipient_email' => $tenantEmail,
                    'status' => 'sent',
                    'attempts' => 1,
                    'last_attempt_at' => $sentAt,
                    'sent_at' => $sentAt,
                    'failed_at' => null,
                    'last_error' => null,
                    'created_at' => $sentAt,
                    'updated_at' => $sentAt,
                ]);

                $rent = DB::table('rent')->where('id', $rentId)->first();
                $room = DB::table('room')->where('id', $rent->room_id)->first();
                $days = ['H-30' => 30, 'H-7' => 7, 'H' => 0][$definition['type']];
                $title = match ($definition['type']) {
                    'H-30' => 'Masa sewa akan berakhir dalam 30 hari',
                    'H-7' => 'Masa sewa akan segera berakhir',
                    default => 'Masa sewa berakhir hari ini',
                };
                $message = match ($definition['type']) {
                    'H-30' => 'Masa sewa kamar Anda akan berakhir dalam 30 hari. Periode pengajuan perpanjangan sudah dibuka.',
                    'H-7' => 'Masa sewa kamar Anda akan berakhir dalam 7 hari. Segera ajukan perpanjangan jika ingin tetap tinggal.',
                    default => 'Masa sewa kamar Anda berakhir hari ini.',
                };
                $actionUrl = $definition['type'] === 'H'
                    ? '/tenant/dashboard'
                    : '/tenant/rents/' . $rentId . '/renewal';

                DB::table('notifications')->insert([
                    'id' => (string) Str::uuid(),
                    'type' => RentExpirationNotification::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $userIds[$tenantEmail],
                    'data' => json_encode([
                        'category' => 'rent_expiration',
                        'reminder_type' => $definition['type'],
                        'title' => $title,
                        'message' => $message,
                        'rent_id' => $rentId,
                        'rent_code' => $rent->rent_code,
                        'room_id' => $rent->room_id,
                        'room_code' => $room->room_code,
                        'days_remaining' => $days,
                        'action_url' => $actionUrl,
                    ], JSON_THROW_ON_ERROR),
                    'read_at' => null,
                    'created_at' => $sentAt,
                    'updated_at' => $sentAt,
                ]);
            }

            /*
             * 12. TENANT NOTIFICATIONS TAMBAHAN
             * Riwayat notifikasi untuk booking/pembayaran juga disertakan agar
             * tenant notification center tidak hanya berisi reminder sewa.
             */
            $tenantActivityNotifications = [
                [
                    'email' => 'tenant10@ekost.test',
                    'title' => 'Booking berhasil dibuat',
                    'message' => 'Booking kamar A-104 berhasil dibuat. Silakan pilih metode pembayaran.',
                    'category' => 'booking',
                    'action_url' => '/tenant/bookings/' . $rentRecords['pending_unselected']['booking_id'],
                ],
                [
                    'email' => 'tenant11@ekost.test',
                    'title' => 'Menunggu pembayaran',
                    'message' => 'Instruksi pembayaran QRIS untuk booking kamar B-204 sudah tersedia.',
                    'category' => 'payment',
                    'action_url' => '/tenant/bookings/' . $rentRecords['pending_qris']['booking_id'],
                ],
            ];

            foreach ($tenantActivityNotifications as $index => $item) {
                DB::table('notifications')->insert([
                    'id' => (string) Str::uuid(),
                    'type' => 'App\\Notifications\\TenantActivityNotification',
                    'notifiable_type' => User::class,
                    'notifiable_id' => $userIds[$item['email']],
                    'data' => json_encode([
                        'title' => $item['title'],
                        'message' => $item['message'],
                        'category' => $item['category'],
                        'action_url' => $item['action_url'],
                    ], JSON_THROW_ON_ERROR),
                    'read_at' => null,
                    'created_at' => $now->copy()->subMinutes(10 + $index * 5),
                    'updated_at' => $now,
                ]);
            }
        });
    }
}
