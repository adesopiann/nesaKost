<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function chooseRole()
    {
        return view('pages.auth.choose-role');
    }

    public function showRegister(string $role)
    {
        abort_unless(in_array($role, ['owner', 'tenant'], true), 404);
        return view('pages.auth.register', compact('role'));
    }

    public function register(Request $request, EmailVerificationService $verificationService)
    {
        $role = $request->input('role');

        // Public signup hanya boleh membuat akun owner atau tenant.
        if (! in_array($role, ['owner', 'tenant'], true)) {
            throw ValidationException::withMessages([
                'role' => 'Pilih pendaftaran sebagai pemilik kos atau penyewa.',
            ]);
        }

        $rules = [
            'role' => ['required', Rule::in(['owner', 'tenant'])],
            'fname' => ['required', 'string', 'max:100'],
            'lname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:2000'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        if ($role === 'tenant') {
            $rules += [
                'profession' => ['required', 'string', 'max:100'],
                'emergency_name' => ['required', 'string', 'max:150'],
                'emergency_number' => ['required', 'string', 'max:20'],
            ];
        }

        $data = $request->validate($rules, [
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $userId = DB::transaction(function () use ($data, $role) {
            $now = now();

            $userId = DB::table('users')->insertGetId([
                'email' => $data['email'],
                'fname' => $data['fname'],
                'lname' => $data['lname'],
                'phone_number' => $data['phone_number'],
                'address' => $data['address'],
                'password' => Hash::make($data['password']),
                'role' => $role,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($role === 'owner') {
                DB::table('owners')->insert([
                    'user_id' => $userId,
                    'address' => $data['address'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('tenants')->insert([
                    'user_id' => $userId,
                    'profession' => $data['profession'],
                    'emergency_name' => $data['emergency_name'],
                    'emergency_number' => $data['emergency_number'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return $userId;
        });

        $user = User::query()->findOrFail($userId);
        $request->session()->put('pending_email_verification_user_id', $user->id);
        try {
            $verificationService->send($user);
        } catch (Throwable $exception) {
            Log::error('Gagal mengirim OTP setelah registrasi.', ['user_id' => $user->id, 'exception' => $exception->getMessage()]);
            return redirect()->route('email.verification.show')->withErrors(['otp' => 'Akun berhasil dibuat, tetapi email OTP belum terkirim. Periksa konfigurasi email, lalu gunakan tombol kirim ulang.']);
        }
        return redirect()->route('email.verification.show')->with('success', 'Pendaftaran berhasil. Kami telah mengirim kode verifikasi ke email kamu.');
    }

    public function showLogin()
    {
        return view('pages.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::validate($credentials)) {
            throw ValidationException::withMessages(['email' => 'Email atau password yang kamu masukkan salah.']);
        }

        $user = User::query()->where('email', $credentials['email'])->first();
        if ($user === null) {
            throw ValidationException::withMessages(['email' => 'Email atau password yang kamu masukkan salah.']);
        }
        if ($user->email_verified_at === null) {
            $request->session()->put('pending_email_verification_user_id', $user->id);
            return redirect()->route('email.verification.show')->withErrors(['email' => 'Email kamu belum terverifikasi. Verifikasi terlebih dahulu sebelum login.']);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Email atau password yang kamu masukkan salah.']);
        }

        $request->session()->regenerate();

        $destination = match ($request->user()->role) {
            'owner' => '/owner/dashboard',
            'manager' => '/manager/dashboard',
            'tenant' => '/tenant/dashboard',
            default => null,
        };

        if ($destination === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Role akun tidak dikenali. Hubungi administrator.',
            ]);
        }

        return redirect()->intended($destination);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('guest.home')
            ->with('success', 'Kamu berhasil keluar dari akun.');
    }
}
