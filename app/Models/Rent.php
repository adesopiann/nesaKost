<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rent extends Model
{
    protected $table = 'rent';

    protected $fillable = [
        'rent_code',
        'booking_id',
        'tenant_id',
        'room_id',
        'start_date',
        'end_date',
        'status',
        'total_price',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_price' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

        /**
     * Booking renewal yang diajukan berdasarkan rent ini.
     */
    public function renewalBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'previous_rent_id');
    }
}