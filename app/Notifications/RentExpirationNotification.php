<?php

namespace App\Notifications;

use App\Models\Rent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

class RentExpirationNotification extends Notification
{
    use Queueable;

    /**
     * Reminder identifiers supported by the rent expiration workflow.
     */
    private const REMINDER_TYPES = ['H-30', 'H-7', 'H'];

    public function __construct(
        public readonly Rent $rent,
        public readonly string $reminderType,
    ) {
        if (! in_array($this->reminderType, self::REMINDER_TYPES, true)) {
            throw new InvalidArgumentException(
                'Jenis pengingat masa sewa harus H-30, H-7, atau H.'
            );
        }
    }

    /**
     * Phase 4.2 stores notifications in the database.
     * Email and WhatsApp channels will be added in their respective subphases.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Data consumed by the tenant notification UI.
     *
     * @return array<string, mixed>
     */
    public function toDatabase($notifiable): array
    {
        $daysRemaining = match ($this->reminderType) {
            'H-30' => 30,
            'H-7' => 7,
            'H' => 0,
        };

        $title = match ($this->reminderType) {
            'H-30' => 'Masa sewa akan berakhir dalam 30 hari',
            'H-7' => 'Masa sewa akan segera berakhir',
            'H' => 'Masa sewa berakhir hari ini',
        };

        $message = match ($this->reminderType) {
            'H-30' => 'Masa sewa kamar Anda akan berakhir dalam 30 hari. Periode pengajuan perpanjangan sudah dibuka.',
            'H-7' => 'Masa sewa kamar Anda akan berakhir dalam 7 hari. Segera ajukan perpanjangan jika ingin tetap tinggal.',
            'H' => 'Masa sewa kamar Anda berakhir hari ini.',
        };

        $room = $this->rent->room;
        $bookingUrl = in_array($this->reminderType, ['H-30', 'H-7'], true)
            ? route('tenant.rents.renewal.create', ['rentCode' => $this->rent->rent_code])
            : route('tenant.dashboard');

        return [
            'category' => 'rent_expiration',
            'reminder_type' => $this->reminderType,
            'title' => $title,
            'message' => $message,
            'rent_id' => $this->rent->id,
            'rent_code' => $this->rent->rent_code,
            'room_id' => $this->rent->room_id,
            'room_code' => $room?->room_code,
            'days_remaining' => $daysRemaining,
            'action_url' => $bookingUrl,
        ];
    }
}
