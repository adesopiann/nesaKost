<?php

use App\Models\BranchDorm;
use App\Models\Owner;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Symfony\Component\Process\Process;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = DB::connection()->getDatabaseName();
if ($database !== 'nesaKost_testing') {
    fwrite(STDERR, "STOP: koneksi aktif mengarah ke '{$database}', bukan nesaKost_testing.\n");
    exit(2);
}

function makeTenantForConcurrency(): Tenant
{
    $user = User::query()->create([
        'email' => 'concurrency-' . Str::uuid() . '@example.test',
        'fname' => 'Concurrency',
        'lname' => 'Tenant',
        'address' => 'Surabaya',
        'password' => bcrypt(Str::random(24)),
        'role' => 'tenant',
    ]);

    return Tenant::query()->create(['user_id' => $user->id]);
}

function makeRoomForConcurrency(): Room
{
    $ownerUser = User::query()->create([
        'email' => 'concurrency-owner-' . Str::uuid() . '@example.test',
        'fname' => 'Concurrency',
        'lname' => 'Owner',
        'address' => 'Surabaya',
        'password' => bcrypt(Str::random(24)),
        'role' => 'owner',
    ]);

    $owner = Owner::query()->create([
        'user_id' => $ownerUser->id,
        'address' => 'Surabaya',
    ]);

    $branch = BranchDorm::query()->create([
        'owner_id' => $owner->id,
        'name' => 'Concurrency Test Branch',
        'address' => 'Surabaya',
        'status' => 'active',
    ]);

    return Room::query()->create([
        'room_code' => 'CONC-' . Str::upper(Str::random(8)),
        'branch_dorm_id' => $branch->id,
        'type' => 'standard',
        'monthly_price' => 900000,
        'status' => 'active',
    ]);
}

function waitForFile(string $path, int $timeoutSeconds = 10): bool
{
    $deadline = microtime(true) + $timeoutSeconds;
    while (microtime(true) < $deadline) {
        if (is_file($path)) {
            return true;
        }
        usleep(20000);
    }
    return false;
}

function runRace(string $label, int $tenantA, int $roomA, int $tenantB, int $roomB): bool
{
    $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nesakost-' . Str::uuid();
    $ready = $base . '.ready';
    $resultA = $base . '.a';
    $resultB = $base . '.b';
    $startedB = $base . '.b.started';

    $worker = __DIR__ . '/worker.php';
    $start = '2027-02-01';
    $end = '2027-02-28';
    $php = PHP_BINARY;

    $a = new Process([$php, $worker, (string) $tenantA, (string) $roomA, $start, $end, $ready, $resultA, '3000', $base . '.a.started']);
    $b = new Process([$php, $worker, (string) $tenantB, (string) $roomB, $start, $end, $ready . '.b', $resultB, '0', $startedB]);

    $a->setTimeout(20);
    $b->setTimeout(20);

    echo "\n{$label}\n";
    $a->start();

    if (!waitForFile($ready)) {
        $a->stop(1);
        $workerResult = is_file($resultA) ? trim(file_get_contents($resultA)) : 'tidak ada result file';
        throw new RuntimeException(
            'Worker A tidak mencapai titik lock dalam 10 detik. '
            . 'Result: ' . $workerResult
            . ' STDOUT: ' . $a->getOutput()
            . ' STDERR: ' . $a->getErrorOutput()
        );
    }

    // Worker A telah membuat booking di dalam transaksi dan menahan lock.
    $b->start();

    if (!waitForFile($startedB)) {
        $a->stop(1);
        $b->stop(1);
        throw new RuntimeException('Worker B tidak mulai dalam 10 detik.');
    }

    $a->wait();
    $b->wait();

    $outA = is_file($resultA) ? trim(file_get_contents($resultA)) : 'missing-result';
    $outB = is_file($resultB) ? trim(file_get_contents($resultB)) : 'missing-result';

    @unlink($ready);
    @unlink($ready . '.b');
    @unlink($startedB);
    @unlink($base . '.a.started');
    @unlink($resultA);
    @unlink($resultB);

    echo "Worker A: {$outA}\n";
    echo "Worker B: {$outB}\n";

    $created = (str_starts_with($outA, 'created:') ? 1 : 0)
        + (str_starts_with($outB, 'created:') ? 1 : 0);

    $passed = $created === 1
        && (str_starts_with($outA, 'created:') || str_starts_with($outB, 'created:'));

    echo $passed ? "PASS: tepat satu booking berhasil.\n" : "FAIL: hasil tidak sesuai ekspektasi.\n";

    return $passed;
}

try {
    // Data fixture di-commit agar terlihat oleh proses PHP anak.
    $tenantA = makeTenantForConcurrency();
    $tenantB = makeTenantForConcurrency();
    $tenantC = makeTenantForConcurrency();
    $roomA = makeRoomForConcurrency();
    $roomB = makeRoomForConcurrency();
    $roomC = makeRoomForConcurrency();

    // Race 1: tenant yang sama, kamar berbeda -> mutex tenant harus serialisasi.
    $sameTenantPass = runRace(
        'Race 1: dua booking tenant yang sama pada kamar berbeda',
        $tenantA->id,
        $roomA->id,
        $tenantA->id,
        $roomB->id
    );

    // Race 2: tenant berbeda, kamar sama -> mutex kamar + current read harus mencegah double booking.
    $sameRoomPass = runRace(
        'Race 2: dua tenant memesan kamar yang sama',
        $tenantB->id,
        $roomC->id,
        $tenantC->id,
        $roomC->id
    );

    echo "\nDatabase aktif: {$database}\n";
    echo 'Hasil akhir: ' . (($sameTenantPass && $sameRoomPass) ? '2/2 PASS' : 'ADA PENGUJIAN GAGAL') . "\n";
    exit(($sameTenantPass && $sameRoomPass) ? 0 : 1);
} finally {
    // Hapus hanya fixture dengan penanda khusus; tidak ada drop/truncate tabel.
    DB::table('booking')->where('booking_code', 'like', 'CONCURRENCY-%')->delete();

    $roomIds = array_filter([
        isset($roomA) ? $roomA->id : null,
        isset($roomB) ? $roomB->id : null,
        isset($roomC) ? $roomC->id : null,
    ]);

    if ($roomIds) {
        DB::table('room')->whereIn('id', $roomIds)->delete();
    }

    $branchIds = DB::table('branch_dorm')
        ->where('name', 'Concurrency Test Branch')
        ->pluck('id');

    if ($branchIds->isNotEmpty()) {
        DB::table('branch_dorm')->whereIn('id', $branchIds)->delete();
    }

    $testUsers = User::query()
        ->where('email', 'like', 'concurrency-%@example.test')
        ->orWhere('email', 'like', 'concurrency-owner-%@example.test')
        ->get();

    $testUserIds = $testUsers->pluck('id');
    $ownerIds = DB::table('owners')->whereIn('user_id', $testUserIds)->pluck('id');

    if ($ownerIds->isNotEmpty()) {
        DB::table('owners')->whereIn('id', $ownerIds)->delete();
    }

    $tenantIds = array_filter([
        isset($tenantA) ? $tenantA->id : null,
        isset($tenantB) ? $tenantB->id : null,
        isset($tenantC) ? $tenantC->id : null,
    ]);

    if ($tenantIds) {
        DB::table('tenants')->whereIn('id', $tenantIds)->delete();
    }

    if ($testUserIds->isNotEmpty()) {
        DB::table('users')->whereIn('id', $testUserIds)->delete();
    }
}
