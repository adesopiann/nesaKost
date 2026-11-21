<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Housemaid extends Model
{
    protected $table = 'housemaid';

    protected $fillable = ['owner_id', 'fname', 'lname', 'salary', 'phone_number', 'address', 'status'];

    protected function casts(): array
    {
        return ['salary' => 'decimal:2'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'owner_id');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(BranchDorm::class, 'housemaid_branch', 'housemaid_id', 'branch_dorm_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([$this->fname, $this->lname])));
    }
}
