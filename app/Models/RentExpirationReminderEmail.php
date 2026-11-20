<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentExpirationReminderEmail extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED_UNVERIFIED = 'skipped_unverified';

    protected $table = 'rent_expiration_reminder_emails';

    protected $fillable = [
        'rent_id',
        'user_id',
        'reminder_type',
        'recipient_email',
        'status',
        'attempts',
        'last_attempt_at',
        'sent_at',
        'failed_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'last_attempt_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class, 'rent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
