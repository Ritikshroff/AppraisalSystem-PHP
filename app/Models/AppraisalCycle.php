<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppraisalCycle extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'appraisalType',
        'periodLabel',
        'startDate',
        'endDate',
        'isActive',
    ];

    protected $casts = [
        'startDate' => 'datetime',
        'endDate' => 'datetime',
        'isActive' => 'boolean',
    ];

    public function appraisals(): HasMany
    {
        return $this->hasMany(Appraisal::class, 'cycleId');
    }
}
