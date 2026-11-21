<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Facility extends Model
{
    protected $table = 'facility';

    protected $fillable = ['owner_id', 'facility_name', 'facility_type', 'description'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'owner_id');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(BranchDorm::class, 'branch_facility', 'facility_id', 'branch_dorm_id');
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'room_facility', 'facility_id', 'room_id');
    }
}
