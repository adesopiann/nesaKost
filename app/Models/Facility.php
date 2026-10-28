<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Facility extends Model
{
    protected $table = 'facility';

    protected $fillable = [
        'facility_name',
        'description',
    ];

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(
            BranchDorm::class,
            'branch_facility',
            'facility_id',
            'branch_dorm_id'
        );
    }
}
