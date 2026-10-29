<?php

namespace App\Models;

use App\Models\RoomImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends Model
{
    protected $table = 'room';

    protected $fillable = [
        'room_code',
        'branch_dorm_id',
        'type',
        'monthly_price',
        'image_path',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
        ];
    }

    public function branchDorm(): BelongsTo
    {
        return $this->belongsTo(
            BranchDorm::class,
            'branch_dorm_id'
        );
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(
            BranchDorm::class,
            'branch_dorm_id'
        );
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(
            Facility::class,
            'room_facility',
            'room_id',
            'facility_id'
        );
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'room_id');
    }

    public function rents(): HasMany
    {
        return $this->hasMany(Rent::class, 'room_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class, 'room_id');
    }

    public function firstImage(): HasOne
    {
        return $this->hasOne(RoomImage::class, 'room_id')
            ->oldestOfMany('id');
    }
}
