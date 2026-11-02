<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $table = 'booking';

    protected $fillable = [
        'booking_code',
        'tenant_id',
        'room_id',
        'booking_type',
        'previous_rent_id',
        'start_date',
        'end_date',
        'duration_month',
        'price_snapshot',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'expires_at' => 'datetime',
            'price_snapshot' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function rent(): HasOne
    {
        return $this->hasOne(Rent::class);
    }

        /**
     * Rent terdahulu yang menjadi dasar pengajuan renewal.
     * Null untuk booking baru.
     */
    public function previousRent(): BelongsTo
    {
        return $this->belongsTo(Rent::class, 'previous_rent_id');
    }

    /**
     * Semua pengajuan renewal yang merujuk pada rent ini.
     */
    public function renewalBookings(): HasMany
    {
        return $this->hasMany(self::class, 'previous_rent_id');
    }
}