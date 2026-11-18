<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->paginate(10);

        return view('pages.tenant.notifications.index', compact('notifications'));
    }

    public function open(string $notificationId): RedirectResponse
    {
        $notification = $this->findOwnedNotification($notificationId);
        $notification->markAsRead();

        $target = data_get($notification->data, 'action_url');
        $fallback = route('tenant.notifications.index');

        // Only follow URLs generated for this application; reject external redirects.
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $targetHost = is_string($target) ? parse_url($target, PHP_URL_HOST) : null;

        if (! is_string($target) || ($targetHost && $appHost && $targetHost !== $appHost)) {
            $target = $fallback;
        }

        return redirect()->to($target);
    }

    public function markAsRead(string $notificationId): RedirectResponse
    {
        $this->findOwnedNotification($notificationId)->markAsRead();

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    private function findOwnedNotification(string $notificationId): DatabaseNotification
    {
        return auth()->user()
            ->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();
    }
}
