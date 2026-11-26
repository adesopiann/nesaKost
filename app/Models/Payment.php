<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payment';

    protected $fillable = [
        'payment_code',
        'booking_id',
        'amount',
        'payment_method',
        'provider',
        'provider_reference',
        'payment_reference',
        'payment_instruction',
        'status',
        'paid_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'payment_code';
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}