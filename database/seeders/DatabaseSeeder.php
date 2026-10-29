<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for eKost.
 *
 * Run on a DEVELOPMENT database only:
 *   php artisan migrate:fresh --seed
 *
 * Adjust status strings if your application uses different values/constants.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();

            // 1. Users: one owner, two managers, and eight tenants.
            $users = [];
            $userRows = [
                ['owner.ekost@example.test', 'Budi', 'Santoso', '081200000001', 'Surabaya', 'owner'],
                ['manager.satu@example.test', 'Rina', 'Lestari', '081200000002', 'Surabaya', 'manager'],
                ['manager.dua@example.test', 'Dimas', 'Pratama', '081200000003', 'Sidoarjo', 'manager'],
                ['tenant.01@example.test', 'Andi', 'Saputra', '081200000011', 'Surabaya', 'tenant'],
                ['tenant.02@example.test', 'Siti', 'Rahma', '081200000012', 'Gresik', 'tenant'],
                ['tenant.03@example.test', 'Fajar', 'Maulana', '081200000013', 'Lamongan', 'tenant'],
                ['tenant.04@example.test', 'Nadia', 'Putri', '081200000014', 'Malang', 'tenant'],
                ['tenant.05@example.test', 'Rizky', 'Firmansyah', '081200000015', 'Kediri', 'tenant'],
                ['tenant.06@example.test', 'Dewi', 'Anggraini', '081200000016', 'Madiun', 'tenant'],
                ['tenant.07@example.test', 'Yoga', 'Prakoso', '081200000017', 'Jombang', 'tenant'],
                ['tenant.08@example.test', 'Intan', 'Permata', '081200000018', 'Mojokerto', 'tenant'],
            ];

            foreach ($userRows as [$email, $fname, $lname, $phone, $address, $role]) {
                $users[$email] = DB::table('users')->insertGetId([
                    'email' => $email,
                    'fname' => $fname,
                    'lname' => $lname,
                    'phone_number' => $phone,
                    'address' => $address,
                    'password' => Hash::make('password123'),
                    'role' => $role,
                    'remember_token' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // 2. Owner and managers.
            $ownerId = DB::table('owners')->insertGetId([
                'user_id' => $users['owner.ekost@example.test'],
                'address' => 'Jl. Ketintang, Surabaya',
                'created_at' => $now, 'updated_at' => $now,
            ]);

            $managerOne = DB::table('managers')->insertGetId([
                'user_id' => $users['manager.satu@example.test'],
                'owner_id' => $ownerId,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $managerTwo = DB::table('managers')->insertGetId([
                'user_id' => $users['manager.dua@example.test'],
                'owner_id' => $ownerId,
                'created_at' => $now, 'updated_at' => $now,
            ]);

            // 3. Tenants.
            $tenantIds = [];
            $tenantDetails = [
                ['tenant.01@example.test', 'Mahasiswa Sistem Informasi', 'Bambang Saputra', '081300000001'],
                ['tenant.02@example.test', 'Mahasiswa Manajemen', 'Sri Rahmawati', '081300000002'],
                ['tenant.03@example.test', 'Mahasiswa Teknik Informatika', 'Agus Maulana', '081300000003'],
                ['tenant.04@example.test', 'Mahasiswa Pendidikan', 'Dian Puspita', '081300000004'],
                ['tenant.05@example.test', 'Mahasiswa Akuntansi', 'Rudi Firmansyah', '081300000005'],
                ['tenant.06@example.test', 'Mahasiswa Psikologi', 'Wati Anggraini', '081300000006'],
                ['tenant.07@example.test', 'Mahasiswa Ekonomi', 'Bambang Prakoso', '081300000007'],
                ['tenant.08@example.test', 'Mahasiswa Sistem Informasi', 'Sari Permata', '081300000008'],
            ];
            foreach ($tenantDetails as [$email, $profession, $emergencyName, $emergencyNumber]) {
                $tenantIds[] = DB::table('tenants')->insertGetId([
                    'user_id' => $users[$email],
                    'profession' => $profession,
                    'emergency_name' => $emergencyName,
                    'emergency_number' => $emergencyNumber,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            // 4. Branches. If your schema has extra required columns, add them here.
            $branches = [];
            $branchRows = [
                [$ownerId, 'Pondok Biroe Ketintang', 'Jl. Ketintang No. 10, Surabaya', 'active', $managerOne],
                [$ownerId, 'Pondok Biroe Lidah', 'Jl. Lidah Wetan No. 21, Surabaya', 'active', $managerTwo],
                [$ownerId, 'Pondok Biroe Jemursari', 'Jl. Jemursari No. 8, Surabaya', 'active', $managerOne],
            ];
            foreach ($branchRows as [$branchOwnerId, $name, $address, $status, $managerId]) {
                $branchId = DB::table('branch_dorm')->insertGetId([
                    'owner_id' => $branchOwnerId,
                    'name' => $name,
                    'address' => $address,
                    'image_path' => null,
                    'status' => $status,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                $branches[] = ['id' => $branchId, 'manager_id' => $managerId];
                DB::table('manager_branch')->insert([
                    'manager_id' => $managerId,
                    'branch_dorm_id' => $branchId,
                    'owner_id' => $branchOwnerId,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            // 5. Housemaids.
            foreach ($branches as $i => $branch) {
                DB::table('housemaid')->insert([
                    'branch_dorm_id' => $branch['id'],
                    'name' => ['Mbak Sulastri', 'Pak Harun', 'Mbak Yuni'][$i],
                    'fname' => ['Sulastri', 'Harun', 'Yuni'][$i],
                    'lname' => ['Wati', 'Santoso', 'Lestari'][$i],
                    'salary' => [1800000, 2000000, 1750000][$i],
                    'phone_number' => '08210000000' . ($i + 1),
                    'address' => 'Surabaya',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            // 6. Facilities and branch/room pivots.
            $facilityRows = [
                ['Wi-Fi', 'Internet nirkabel di area kost'],
                ['Parkir Motor', 'Area parkir motor'],
                ['Dapur Bersama', 'Dapur bersama untuk penghuni'],
                ['Laundry', 'Area cuci dan jemur'],
                ['Keamanan 24 Jam', 'Pemantauan keamanan sepanjang hari'],
                ['Kamar Mandi Dalam', 'Kamar mandi di dalam kamar'],
                ['AC', 'Pendingin udara di kamar'],
            ];
            $facilityIds = [];
            foreach ($facilityRows as [$name, $description]) {
                $facilityIds[$name] = DB::table('facility')->insertGetId([
                    'facility_name' => $name,
                    'description' => $description,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            foreach ($branches as $i => $branch) {
                $branchFacilityNames = $i === 1
                    ? ['Wi-Fi', 'Parkir Motor', 'Laundry', 'Keamanan 24 Jam']
                    : ['Wi-Fi', 'Parkir Motor', 'Dapur Bersama', 'Laundry', 'Keamanan 24 Jam'];
                foreach ($branchFacilityNames as $facilityName) {
                    DB::table('branch_facility')->insert([
                        'branch_dorm_id' => $branch['id'],
                        'facility_id' => $facilityIds[$facilityName],
                    ]);
                }
            }

            // 7. Rooms: 15 rooms across three branches, with mixed availability.
            $rooms = [];
            $roomSpecs = [
                [0, 'A-101', 'Single', 900000, 'available'],
                [0, 'A-102', 'Single', 950000, 'occupied'],
                [0, 'A-103', 'Deluxe', 1400000, 'occupied'],
                [0, 'A-104', 'Deluxe', 1450000, 'available'],
                [0, 'A-105', 'Single', 850000, 'inactive'],
                [1, 'B-201', 'Single', 800000, 'occupied'],
                [1, 'B-202', 'Deluxe', 1300000, 'occupied'],
                [1, 'B-203', 'Deluxe', 1350000, 'available'],
                [1, 'B-204', 'Suite', 1800000, 'available'],
                [1, 'B-205', 'Single', 825000, 'available'],
                [2, 'C-301', 'Single', 875000, 'occupied'],
                [2, 'C-302', 'Deluxe', 1250000, 'occupied'],
                [2, 'C-303', 'Suite', 1750000, 'available'],
                [2, 'C-304', 'Single', 900000, 'available'],
                [2, 'C-305', 'Deluxe', 1300000, 'available'],
            ];

            foreach ($roomSpecs as [$branchIndex, $roomCode, $type, $price, $status]) {
                $roomId = DB::table('room')->insertGetId([
                    'room_code' => $roomCode,
                    'branch_dorm_id' => $branches[$branchIndex]['id'],
                    'type' => $type,
                    'monthly_price' => $price,
                    'image_path' => null,
                    'description' => "Kamar tipe {$type} di {$roomCode}, cocok untuk mahasiswa.",
                    'status' => $status,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                $rooms[$roomCode] = [
                    'id' => $roomId, 'branch_index' => $branchIndex,
                    'price' => $price, 'status' => $status,
                ];

                DB::table('room_images')->insert([
                    'room_id' => $roomId,
                    'image_path' => 'rooms/demo-' . strtolower($roomCode) . '.jpg',
                    'is_primary' => true,
                    'created_at' => $now, 'updated_at' => $now,
                ]);

                $roomFacilityNames = ['Wi-Fi', 'Parkir Motor'];
                if ($type === 'Deluxe' || $type === 'Suite') {
                    $roomFacilityNames[] = 'AC';
                    $roomFacilityNames[] = 'Kamar Mandi Dalam';
                }
                foreach ($roomFacilityNames as $facilityName) {
                    DB::table('room_facility')->insert([
                        'room_id' => $roomId,
                        'facility_id' => $facilityIds[$facilityName],
                    ]);
                }
            }

            // 8. Bookings, payments and rents.
            // Dates are spread over the previous 11 months to make monthly charts useful.
            $transactions = [
                // tenant index, room code, months ago, duration, booking status, payment status
                [0, 'A-102', 10, 6, 'confirmed', 'success'],
                [1, 'A-103', 9, 6, 'confirmed', 'success'],
                [2, 'B-201', 8, 3, 'confirmed', 'success'],
                [3, 'B-202', 7, 6, 'confirmed', 'success'],
                [4, 'C-301', 6, 3, 'confirmed', 'success'],
                [5, 'C-302', 5, 6, 'confirmed', 'success'],
                [6, 'A-102', 4, 3, 'confirmed', 'success'],
                [7, 'B-201', 3, 6, 'confirmed', 'success'],
                [0, 'A-103', 2, 3, 'confirmed', 'success'],
                [1, 'B-202', 1, 6, 'confirmed', 'success'],
                [2, 'C-301', 0, 3, 'pending', 'pending'],
                [3, 'A-101', 0, 3, 'pending', 'failed'],
                [4, 'B-203', 0, 3, 'expired', 'failed'],
                [5, 'C-303', 0, 6, 'cancelled', 'failed'],
            ];

            foreach ($transactions as $i => [$tenantIndex, $roomCode, $monthsAgo, $duration, $bookingStatus, $paymentStatus]) {
                $room = $rooms[$roomCode];
                $startDate = Carbon::now()->startOfMonth()->subMonths($monthsAgo)->addDays(4);
                $endDate = $startDate->copy()->addMonths($duration);
                $total = $room['price'] * $duration;
                $createdAt = $startDate->copy()->subDays(14);
                $bookingId = DB::table('booking')->insertGetId([
                    'booking_code' => 'BKG-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                    'tenant_id' => $tenantIds[$tenantIndex],
                    'room_id' => $room['id'],
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'duration_month' => $duration,
                    'price_snapshot' => $room['price'],
                    'status' => $bookingStatus,
                    'expires_at' => $bookingStatus === 'pending' ? $createdAt->copy()->addHours(24) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $paidAt = $paymentStatus === 'success' ? $createdAt->copy()->addHours(2) : null;
                DB::table('payment')->insert([
                    'payment_code' => 'PAY-' . ($i + 1),
                    'booking_id' => $bookingId,
                    'amount' => $total,
                    'payment_method' => ['bank_transfer', 'e_wallet', 'virtual_account'][$i % 3],
                    'status' => $paymentStatus,
                    'paid_at' => $paidAt,
                    'expires_at' => $paymentStatus === 'pending' ? $createdAt->copy()->addHours(24) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $paidAt ?? $createdAt,
                ]);

                // Per the project rule, only fully paid bookings generate a rent.
                if ($paymentStatus === 'success') {
                    $rentStatus = $endDate->isPast() ? 'completed'
                        : ($startDate->isFuture() ? 'scheduled' : 'active');

                    DB::table('rent')->insert([
                        'rent_code' => 'RENT-' . ($i + 1),
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
                }
            }
        });
    }
}
