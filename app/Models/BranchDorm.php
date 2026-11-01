<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BranchDorm extends Model
{
    protected $table = 'branch_dorm';

    protected $fillable = [
        'owner_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'gender_type',
        'image_path',
        'status',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'owner_id');
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(
            Facility::class,
            'branch_facility',
            'branch_dorm_id',
            'facility_id'
        );
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'branch_dorm_id');
    }

    public function getBranchCodeAttribute(): string
    {
        return 'CBG-' . str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }

        protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

}
