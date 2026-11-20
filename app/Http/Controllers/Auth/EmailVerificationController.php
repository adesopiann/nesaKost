<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class EmailVerificationController extends Controller
{
    public function __construct(private readonly EmailVerificationService $verificationService) {}

    public function show(Request $request): RedirectResponse|View
    {
        $user = $this->pendingUser($request);
        if ($user === null) return redirect()->route('login')->withErrors(['email' => 'Silakan login atau daftar terlebih dahulu untuk verifikasi email.']);
        if ($user->email_verified_at !== null) {
            $request->session()->forget('pending_email_verification_user_id');
            return redirect()->route('login')->with('success', 'Email kamu sudah terverifikasi. Silakan login.');
        }
        return view('pages.auth.verify-email', ['email' => $user->email]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if ($user === null) return redirect()->route('login')->withErrors(['email' => 'Sesi verifikasi tidak ditemukan. Silakan login kembali.']);
        $data = $request->validate(['otp' => ['required', 'digits:6']], [
            'otp.required' => 'Masukkan kode verifikasi yang dikirim ke email kamu.',
            'otp.digits' => 'Kode verifikasi harus terdiri dari 6 digit.',
        ]);
        $this->verificationService->verify($user, $data['otp']);
        $request->session()->forget('pending_email_verification_user_id');
        return redirect()->route('login')->with('success', 'Email berhasil diverifikasi. Sekarang kamu dapat login.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if ($user === null) return redirect()->route('login')->withErrors(['email' => 'Sesi verifikasi tidak ditemukan. Silakan login kembali.']);
        try {
            $this->verificationService->send($user);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Gagal mengirim OTP verifikasi email.', ['user_id' => $user->id, 'exception' => $exception->getMessage()]);
            return back()->withErrors(['otp' => 'Email verifikasi belum berhasil dikirim. Periksa konfigurasi email atau coba lagi nanti.']);
        }
        return back()->with('success', 'Kode verifikasi baru berhasil dikirim ke email kamu.');
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('pending_email_verification_user_id');
        return $userId ? User::query()->find($userId) : null;
    }
}
