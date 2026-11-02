<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder demo eKost - disesuaikan dengan migration proyek nesaKost2.
 *
 * Akun demo:
 * Owner   : owner@ekost.test
 * Manager : manager1@ekost.test / manager2@ekost.test
 * Tenant : tenant01@ekost.test s.d. tenant08@ekost.test
 * Password seluruh akun: password123
 *
 * Jalankan pada database development:
 *   php artisan migrate:fresh --seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $password = Hash::make('password123');

            /*
             * 1. USERS
             * Kolom address pada migration users bersifat NOT NULL.
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
            ];

            $userIds = [];
            foreach ($userDefinitions as $definition) {
                $userIds[$definition['email']] = DB::table('users')->insertGetId([
                    'email' => $definition['email'],
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

            /*
             * 2. OWNER, MANAGERS, TENANTS
             */
            $ownerId = DB::table('owners')->insertGetId([
                'user_id' => $userIds['owner@ekost.test'],
                'address' => 'Jl. Ketintang, Surabaya, Jawa Timur',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $managerIds = [];
            foreach (['manager1@ekost.test', 'manager2@ekost.test'] as $email) {
                $managerIds[] = DB::table('managers')->insertGetId([
                    'user_id' => $userIds[$email],
                    'owner_id' => $ownerId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $tenantIds = [];
            $tenantProfiles = [
                ['tenant01@ekost.test', 'Mahasiswa Sistem Informasi', 'Agus Saputra', '082100000001'],
                ['tenant02@ekost.test', 'Mahasiswa Manajemen', 'Sri Rahmawati', '082100000002'],
                ['tenant03@ekost.test', 'Mahasiswa Teknik Informatika', 'Bambang Maulana', '082100000003'],
                ['tenant04@ekost.test', 'Mahasiswa Pendidikan', 'Dian Puspita', '082100000004'],
                ['tenant05@ekost.test', 'Mahasiswa Akuntansi', 'Rudi Firmansyah', '082100000005'],
                ['tenant06@ekost.test', 'Mahasiswa Psikologi', 'Wati Anggraini', '082100000006'],
                ['tenant07@ekost.test', 'Mahasiswa Ekonomi', 'Bambang Prakoso', '082100000007'],
                ['tenant08@ekost.test', 'Mahasiswa Sistem Informasi', 'Sari Permata', '082100000008'],
            ];

            foreach ($tenantProfiles as [$email, $profession, $emergencyName, $emergencyNumber]) {
                $tenantIds[] = DB::table('tenants')->insertGetId([
                    'user_id' => $userIds[$email],
                    'profession' => $profession,
                    'emergency_name' => $emergencyName,
                    'emergency_number' => $emergencyNumber,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            /*
             * 3. FACILITIES
             */
            $facilityDefinitions = [
                ['Wi-Fi', 'Internet nirkabel di area kost.'],
                ['Parkir Motor', 'Area parkir motor penghuni.'],
                ['Dapur Bersama', 'Dapur bersama untuk penghuni.'],
                ['Laundry', 'Area mencuci dan menjemur pakaian.'],
                ['Keamanan 24 Jam', 'Keamanan dan pemantauan selama 24 jam.'],
                ['Kamar Mandi Dalam', 'Kamar mandi pribadi di dalam kamar.'],
                ['AC', 'Pendingin udara di dalam kamar.'],
                ['Kasur', 'Kasur dan tempat tidur.'],
                ['Lemari', 'Lemari pakaian.'],
                ['Meja Belajar', 'Meja untuk belajar atau bekerja.'],
            ];

            $facilityIds = [];
            foreach ($facilityDefinitions as [$name, $description]) {
                $facilityIds[$name] = DB::table('facility')->insertGetId([
                    'facility_name' => $name,
                    'description' => $description,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            /*
             * 4. BRANCHES + MANAGER_BRANCH + HOUSEMAID
             * gender_type mengikuti validasi aplikasi: putra, putri, campuran.
             * Koordinat adalah titik demo di sekitar area Surabaya/UNESA.
             */
            $branchDefinitions = [
                [
                    'name' => 'Pondok Biroe Ketintang',
                    'address' => 'Jl. Ketintang, Ketintang, Gayungan, Surabaya, Jawa Timur',
                    'latitude' => -7.3163178974848435,  'longitude' => 112.72816785943468,
                    'gender_type' => 'putra', 'manager_index' => 0,
                    'facilities' => ['Wi-Fi', 'Parkir Motor', 'Dapur Bersama', 'Laundry', 'Keamanan 24 Jam'],
                    'maid' => ['Sulastri', 'Wati', 1800000],
                ],
                [
                    'name' => 'Pondok Biroe Lidah Wetan',
                    'address' => 'Jl. Lidah Wetan, Lakarsantri, Surabaya, Jawa Timur',
                     'latitude' => -7.297251959929174,  'longitude' => 112.67303349141106,
                    'gender_type' => 'putri', 'manager_index' => 1,
                    'facilities' => ['Wi-Fi', 'Parkir Motor', 'Laundry', 'Keamanan 24 Jam'],
                    'maid' => ['Yuni', 'Lestari', 1750000],
                ],
                [
                    'name' => 'Pondok Biroe Jemursari',
                    'address' => 'Jl. Jemursari, Wonocolo, Surabaya, Jawa Timur',
                    'latitude' => -7.316291293774127,  'longitude' => 112.73004004122451,
                    'gender_type' => 'campuran', 'manager_index' => 0,
                    'facilities' => ['Wi-Fi', 'Parkir Motor', 'Dapur Bersama', 'Keamanan 24 Jam'],
                    'maid' => ['Harun', 'Santoso', 2000000],
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
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $managerId = $managerIds[$definition['manager_index']];
                DB::table('manager_branch')->insert([
                    'manager_id' => $managerId,
                    'branch_dorm_id' => $branchId,
                    'owner_id' => $ownerId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($definition['facilities'] as $facilityName) {
                    DB::table('branch_facility')->insert([
                        'branch_dorm_id' => $branchId,
                        'facility_id' => $facilityIds[$facilityName],
                    ]);
                }

                [$maidFirst, $maidLast, $salary] = $definition['maid'];
                DB::table('housemaid')->insert([
                    'branch_dorm_id' => $branchId,
                    'name' => trim($maidFirst . ' ' . $maidLast),
                    'fname' => $maidFirst,
                    'lname' => $maidLast,
                    'salary' => $salary,
                    'phone_number' => '08310000000' . ($index + 1),
                    'address' => $definition['address'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $branches[] = ['id' => $branchId, 'definition' => $definition];
            }

            /*
             * 5. ROOMS + ROOM_IMAGES + ROOM_FACILITY
             * Status mengikuti validasi RoomController: active, occupied,
             * maintenance, inactive.
             */
            $roomDefinitions = [
                [0, 'A-101', 'Single', 900000, 'active'],
                [0, 'A-102', 'Single', 950000, 'occupied'],
                [0, 'A-103', 'Deluxe', 1400000, 'occupied'],
                [0, 'A-104', 'Deluxe', 1450000, 'active'],
                [0, 'A-105', 'Single', 850000, 'maintenance'],

                [1, 'B-201', 'Single', 800000, 'occupied'],
                [1, 'B-202', 'Deluxe', 1300000, 'occupied'],
                [1, 'B-203', 'Deluxe', 1350000, 'active'],
                [1, 'B-204', 'Suite', 1800000, 'active'],
                [1, 'B-205', 'Single', 825000, 'inactive'],

                [2, 'C-301', 'Single', 875000, 'occupied'],
                [2, 'C-302', 'Deluxe', 1250000, 'occupied'],
                [2, 'C-303', 'Suite', 1750000, 'active'],
                [2, 'C-304', 'Single', 900000, 'active'],
                [2, 'C-305', 'Deluxe', 1300000, 'active'],
            ];

            $rooms = [];
            foreach ($roomDefinitions as [$branchIndex, $roomCode, $type, $price, $status]) {
                $roomId = DB::table('room')->insertGetId([
                    'room_code' => $roomCode,
                    'branch_dorm_id' => $branches[$branchIndex]['id'],
                    'type' => $type,
                    'monthly_price' => $price,
                    'image_path' => 'rooms/demo-' . strtolower($roomCode) . '.jpg',
                    'description' => "Kamar {$type} {$roomCode} dengan fasilitas yang nyaman untuk mahasiswa.",
                    'status' => $status,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $imagePath = 'rooms/demo-' . strtolower($roomCode) . '.jpg';
                DB::table('room_images')->insert([
                    'room_id' => $roomId,
                    'image_path' => $imagePath,
                    'is_primary' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $rooms[$roomCode] = [
                    'id' => $roomId,
                    'branch_index' => $branchIndex,
                    'price' => $price,
                ];

                $roomFacilityNames = ['Kasur', 'Lemari', 'Meja Belajar', 'Wi-Fi'];
                if (in_array($type, ['Deluxe', 'Suite'], true)) {
                    $roomFacilityNames = array_merge($roomFacilityNames, ['AC', 'Kamar Mandi Dalam']);
                }

                foreach (array_unique($roomFacilityNames) as $facilityName) {
                    DB::table('room_facility')->insert([
                        'room_id' => $roomId,
                        'facility_id' => $facilityIds[$facilityName],
                    ]);
                }
            }

            /*
             * 6. BOOKINGS + PAYMENTS + RENTS
             *
             * Status mengikuti migration:
             * booking: pending_payment, confirmed, expired, cancelled
             * payment: pending, paid, failed, expired
             * rent: scheduled, active, completed, cancelled
             *
             * Rent hanya dibuat untuk pembayaran paid.
             */
            $transactionDefinitions = [
                // tenant index, room code, months ago, duration, booking status, payment status
                [0, 'A-102', 11, 6, 'confirmed', 'paid'],
                [1, 'A-103', 10, 6, 'confirmed', 'paid'],
                [2, 'B-201', 9, 3, 'confirmed', 'paid'],
                [3, 'B-202', 8, 6, 'confirmed', 'paid'],
                [4, 'C-301', 7, 3, 'confirmed', 'paid'],
                [5, 'C-302', 6, 6, 'confirmed', 'paid'],
                [6, 'A-102', 5, 3, 'confirmed', 'paid'],
                [7, 'B-201', 4, 6, 'confirmed', 'paid'],
                [0, 'A-103', 3, 3, 'confirmed', 'paid'],
                [1, 'B-202', 2, 6, 'confirmed', 'paid'],
                [2, 'C-301', 0, 3, 'pending_payment', 'pending'],
                [3, 'A-101', 0, 3, 'pending_payment', 'failed'],
                [4, 'B-203', 1, 3, 'expired', 'expired'],
                [5, 'C-303', 1, 6, 'cancelled', 'failed'],
            ];

            $createdRents = [];
            foreach ($transactionDefinitions as $index => [$tenantIndex, $roomCode, $monthsAgo, $duration, $bookingStatus, $paymentStatus]) {
                $room = $rooms[$roomCode];
                $startDate = Carbon::now()->startOfMonth()->subMonths($monthsAgo)->addDays(4);
                $endDate = $startDate->copy()->addMonths($duration);
                $createdAt = $startDate->copy()->subDays(14);
                $total = $room['price'] * $duration;

                $bookingId = DB::table('booking')->insertGetId([
                    'booking_code' => null,
                    'tenant_id' => $tenantIds[$tenantIndex],
                    'room_id' => $room['id'],
                    'booking_type' => 'new',
                    'previous_rent_id' => null,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'duration_month' => $duration,
                    'price_snapshot' => $room['price'],
                    'status' => $bookingStatus,
                    'expires_at' => in_array($bookingStatus, ['pending_payment', 'expired'], true)
                        ? $createdAt->copy()->addHours(24)
                        : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
                DB::table('booking')->where('id', $bookingId)->update([
                    'booking_code' => 'BOOKING-' . $bookingId,
                ]);

                $paidAt = $paymentStatus === 'paid' ? $createdAt->copy()->addHours(2) : null;
                $paymentId = DB::table('payment')->insertGetId([
                    'payment_code' => null,
                    'booking_id' => $bookingId,
                    'amount' => $total,
                    'payment_method' => ['bank_transfer', 'e_wallet', 'virtual_account'][$index % 3],
                    'status' => $paymentStatus,
                    'paid_at' => $paidAt,
                    'expires_at' => $paymentStatus === 'pending'
                        ? $createdAt->copy()->addHours(24)
                        : null,
                    'created_at' => $createdAt,
                    'updated_at' => $paidAt ?? $createdAt,
                ]);
                DB::table('payment')->where('id', $paymentId)->update([
                    'payment_code' => 'PAY-' . $paymentId,
                ]);

                if ($paymentStatus === 'paid') {
                    $rentStatus = $endDate->lt(Carbon::today())
                        ? 'completed'
                        : ($startDate->gt(Carbon::today()) ? 'scheduled' : 'active');

                    $rentId = DB::table('rent')->insertGetId([
                        'rent_code' => null,
                        'booking_id' => $bookingId,
                        'tenant_id' => $tenantIds[$tenantIndex],
                        'room_id' => $room['id'],
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                        'status' => $rentStatus,
                        'total_price' => $total,
                        'created_at' => $paidAt,
                        'updated_at' => $paidAt,
                    ]);
                    DB::table('rent')->where('id', $rentId)->update([
                        'rent_code' => 'RENT-' . $rentId,
                    ]);

                    $createdRents[] = [
                        'id' => $rentId,
                        'tenant_index' => $tenantIndex,
                        'room_code' => $roomCode,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'duration' => $duration,
                        'price' => $room['price'],
                    ];
                }
            }

            /*
             * 7. CONTOH BOOKING RENEWAL
             * Menghubungkan booking_type=renewal ke rent sebelumnya.
             * Booking renewal ini masih menunggu pembayaran, sehingga belum
             * membuat rent baru.
             */
            $renewalBase = collect($createdRents)->first(function ($rent) {
                return $rent['end_date']->gt(Carbon::now()->addDays(10));
            });

            if ($renewalBase) {
                $room = $rooms[$renewalBase['room_code']];
                $renewalStart = $renewalBase['end_date']->copy();
                $renewalDuration = 3;
                $renewalEnd = $renewalStart->copy()->addMonths($renewalDuration);
                $renewalCreatedAt = Carbon::now()->subDays(2);

                $renewalBookingId = DB::table('booking')->insertGetId([
                    'booking_code' => null,
                    'tenant_id' => $tenantIds[$renewalBase['tenant_index']],
                    'room_id' => $room['id'],
                    'booking_type' => 'renewal',
                    'previous_rent_id' => $renewalBase['id'],
                    'start_date' => $renewalStart->toDateString(),
                    'end_date' => $renewalEnd->toDateString(),
                    'duration_month' => $renewalDuration,
                    'price_snapshot' => $room['price'],
                    'status' => 'pending_payment',
                    'expires_at' => $renewalCreatedAt->copy()->addHours(24),
                    'created_at' => $renewalCreatedAt,
                    'updated_at' => $renewalCreatedAt,
                ]);
                DB::table('booking')->where('id', $renewalBookingId)->update([
                    'booking_code' => 'BOOKING-' . $renewalBookingId,
                ]);

                $renewalPaymentId = DB::table('payment')->insertGetId([
                    'payment_code' => null,
                    'booking_id' => $renewalBookingId,
                    'amount' => $room['price'] * $renewalDuration,
                    'payment_method' => 'bank_transfer',
                    'status' => 'pending',
                    'paid_at' => null,
                    'expires_at' => $renewalCreatedAt->copy()->addHours(24),
                    'created_at' => $renewalCreatedAt,
                    'updated_at' => $renewalCreatedAt,
                ]);
                DB::table('payment')->where('id', $renewalPaymentId)->update([
                    'payment_code' => 'PAY-' . $renewalPaymentId,
                ]);
            }
        });
    }
}
