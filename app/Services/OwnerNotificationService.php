<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Rent;
use App\Models\User;
use App\Notifications\OwnerActivityNotification;
use Illuminate\Support\Facades\URL;

class OwnerNotificationService
{
    public function bookingCreated(Booking $booking): void
    {
        $booking->loadMissing(['room.branchDorm.owner.user', 'tenant.user']);
        $room = $booking->room;
        $branch = $room?->branchDorm;
        $owner = $branch?->owner?->user;
        if (! $owner) return;

        $renewal = $booking->booking_type === 'renewal';
        $this->sendOnce($owner, 'booking_created', 'booking:'.$booking->id.':created', [
            'title' => $renewal ? 'Pengajuan perpanjangan baru' : 'Booking baru diterima',
            'message' => sprintf('%s mengajukan %s untuk kamar %s di cabang %s.', $this->tenantName($booking), $renewal ? 'perpanjangan sewa' : 'booking', $room?->room_code ?? '-', $branch?->name ?? '-'),
            'category' => 'booking', 'icon' => 'calendar-plus', 'branch_name' => $branch?->name,
            'booking_id' => $booking->id, 'booking_code' => $booking->booking_code,
            'action_url' => route('owner.bookings.index'), 'created_at_label' => now()->toIso8601String(),
        ]);
    }

    public function bookingCancelled(Booking $booking): void
    {
        $booking->loadMissing(['room.branchDorm.owner.user', 'tenant.user']);
        $branch = $booking->room?->branchDorm;
        $owner = $branch?->owner?->user;
        if (! $owner) return;

        $this->sendOnce($owner, 'booking_cancelled', 'booking:'.$booking->id.':cancelled', [
            'title' => 'Booking dibatalkan',
            'message' => sprintf('Booking %s oleh %s untuk kamar %s di cabang %s telah dibatalkan.', $booking->booking_code ?? '#'.$booking->id, $this->tenantName($booking), $booking->room?->room_code ?? '-', $branch?->name ?? '-'),
            'category' => 'booking', 'icon' => 'calendar-x', 'branch_name' => $branch?->name,
            'booking_id' => $booking->id, 'action_url' => route('owner.bookings.index'),
        ]);
    }

    public function paymentPaid(Booking $booking, int $paymentId): void
    {
        $booking->loadMissing(['room.branchDorm.owner.user', 'tenant.user', 'payments']);
        $branch = $booking->room?->branchDorm;
        $owner = $branch?->owner?->user;
        $payment = $booking->payments->firstWhere('id', $paymentId);
        if (! $owner || ! $payment) return;

        $this->sendOnce($owner, 'payment_paid', 'payment:'.$payment->id.':paid', [
            'title' => 'Pembayaran berhasil diterima',
            'message' => sprintf('Pembayaran %s sebesar Rp%s dari %s untuk cabang %s berhasil.', $payment->payment_code ?? '#'.$payment->id, number_format((float) $payment->amount, 0, ',', '.'), $this->tenantName($booking), $branch?->name ?? '-'),
            'category' => 'payment', 'icon' => 'wallet', 'branch_name' => $branch?->name,
            'booking_id' => $booking->id, 'payment_id' => $payment->id, 'action_url' => route('owner.payments'),
        ]);
    }

    public function rentCreated(Rent $rent): void
    {
        $rent->loadMissing(['room.branchDorm.owner.user', 'tenant.user', 'booking']);
        $branch = $rent->room?->branchDorm;
        $owner = $branch?->owner?->user;
        if (! $owner) return;

        $this->sendOnce($owner, 'rent_created', 'rent:'.$rent->id.':created', [
            'title' => 'Sewa baru berhasil dibuat',
            'message' => sprintf('Sewa %s untuk %s pada kamar %s di cabang %s telah dibuat.', $rent->rent_code ?? '#'.$rent->id, $this->tenantNameFromRent($rent), $rent->room?->room_code ?? '-', $branch?->name ?? '-'),
            'category' => 'rent', 'icon' => 'file-text', 'branch_name' => $branch?->name,
            'rent_id' => $rent->id, 'action_url' => route('owner.rents'),
        ]);
    }

    public function rentExpiration(Rent $rent, string $reminderType): void
    {
        $rent->loadMissing(['room.branchDorm.owner.user', 'tenant.user']);
        $branch = $rent->room?->branchDorm;
        $owner = $branch?->owner?->user;
        if (! $owner) return;
        $days = ['H-30' => '30 hari lagi', 'H-7' => '7 hari lagi', 'H' => 'hari ini'][$reminderType] ?? $reminderType;

        $this->sendOnce($owner, 'rent_expiration', 'rent:'.$rent->id.':expiration:'.$reminderType, [
            'title' => 'Masa sewa akan berakhir '.$days,
            'message' => sprintf('Masa sewa %s milik %s pada kamar %s di cabang %s akan berakhir %s.', $rent->rent_code ?? '#'.$rent->id, $this->tenantNameFromRent($rent), $rent->room?->room_code ?? '-', $branch?->name ?? '-', $days),
            'category' => 'rent', 'icon' => 'clock', 'branch_name' => $branch?->name,
            'rent_id' => $rent->id, 'action_url' => route('owner.rents'),
        ]);
    }

    private function sendOnce(User $owner, string $type, string $eventKey, array $payload): void
    {
        $exists = $owner->notifications()
            ->where('type', OwnerActivityNotification::class)
            ->where('data->event_key', $eventKey)
            ->exists();
        if ($exists) return;

        $payload['event_type'] = $type;
        $payload['event_key'] = $eventKey;
        $owner->notify(new OwnerActivityNotification($payload));
    }

    private function tenantName(Booking $booking): string
    {
        return trim(($booking->tenant?->user?->fname ?? '').' '.($booking->tenant?->user?->lname ?? '')) ?: 'Penyewa';
    }

    private function tenantNameFromRent(Rent $rent): string
    {
        return trim(($rent->tenant?->user?->fname ?? '').' '.($rent->tenant?->user?->lname ?? '')) ?: 'Penyewa';
    }
}
