<?php

namespace App\Mail;

use App\Models\Rent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;

class RentExpirationReminderMail extends Mailable
{
    use Queueable, SerializesModels;

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

    public function envelope(): Envelope
    {
        $subject = match ($this->reminderType) {
            'H-30' => 'Pengingat: Masa Sewa Anda Berakhir dalam 30 Hari',
            'H-7' => 'Penting: Masa Sewa Anda Berakhir dalam 7 Hari',
            'H' => 'Pemberitahuan: Masa Sewa Anda Berakhir Hari Ini',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $content = match ($this->reminderType) {
            'H-30' => [
                'heading' => 'Masa sewa Anda akan segera berakhir',
                'message' => 'Masa sewa kamar Anda akan berakhir dalam 30 hari. Periode pengajuan perpanjangan sudah dibuka. Jika ingin tetap tinggal di kamar yang sama, silakan ajukan perpanjangan melalui akun eKost Anda.',
                'actionLabel' => 'Ajukan Perpanjangan',
                'showRenewalAction' => true,
            ],
            'H-7' => [
                'heading' => 'Masa sewa Anda berakhir dalam 7 hari',
                'message' => 'Masa sewa kamar Anda akan berakhir dalam 7 hari. Segera ajukan perpanjangan jika ingin tetap tinggal. Pastikan Anda menyelesaikan proses pengajuan dan pembayaran sesuai ketentuan yang berlaku.',
                'actionLabel' => 'Lihat Perpanjangan Sewa',
                'showRenewalAction' => true,
            ],
            'H' => [
                'heading' => 'Masa sewa Anda berakhir hari ini',
                'message' => 'Masa sewa kamar Anda dijadwalkan berakhir hari ini. Silakan periksa informasi sewa melalui akun eKost Anda dan pastikan proses selanjutnya telah disiapkan.',
                'actionLabel' => 'Buka eKost',
                'showRenewalAction' => false,
            ],
        };

        return new Content(
            view: 'emails.rent-expiration-reminder',
            with: [
                'rent' => $this->rent,
                'reminderType' => $this->reminderType,
                'tenantName' => $this->rent->tenant?->user?->fname ?: 'Penyewa',
                'heading' => $content['heading'],
                'bodyText' => $content['message'],
                'actionLabel' => $content['actionLabel'],
                'showRenewalAction' => $content['showRenewalAction'],
                'actionUrl' => in_array($this->reminderType, ['H-30', 'H-7'], true)
                    ? route('tenant.rents.renewal.create', ['rentCode' => $this->rent->rent_code])
                    : route('tenant.dashboard'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
