<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'managerId',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'managerId');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Employee::class, 'teamId');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'teamId');
    }

    public function appraisals(): HasMany
    {
        return $this->hasMany(Appraisal::class, 'teamId');
    }
}
