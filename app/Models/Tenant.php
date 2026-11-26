<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    protected $table = 'tenants';

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            $tenant->public_id ??= (string) Str::uuid();
        });
    }

    protected $fillable = [
        'user_id',
        'profession',
        'emergency_name',
        'emergency_number',
    ];

    /**
     * Relasi ke akun pengguna.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

        public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function rents(): HasMany
    {
        return $this->hasMany(Rent::class);
    }
}
