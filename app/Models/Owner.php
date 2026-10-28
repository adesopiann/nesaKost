<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Owner extends Model
{
    protected $table = 'owners';

    protected $fillable = ['user_id', 'address'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(BranchDorm::class, 'owner_id');
    }
}
