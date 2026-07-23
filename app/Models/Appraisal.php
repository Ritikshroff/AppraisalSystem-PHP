<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appraisal extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'employeeId',
        'teamId',
        'cycleId',
        'managerId',
        'ceoId',
        'type',
        'appraisalPeriod',
        'status',
        'sectionOneAnswers',
        'managerReview',
        'ceoReview',
        'managerOverallRating',
        'finalRating',
        'hikePercentage',
        'aiPerformanceSummary',
        'sentimentLabel',
        'sentimentScore',
        'aiStrengths',
        'aiWeaknesses',
        'aiRiskSignals',
        'employeeSubmittedAt',
        'managerSubmittedAt',
        'ceoSubmittedAt',
        'analyzedAt',
        'deadlineAt',
    ];

    protected $casts = [
        'managerOverallRating' => 'double',
        'finalRating' => 'double',
        'hikePercentage' => 'double',
        'sentimentScore' => 'double',
        'employeeSubmittedAt' => 'datetime',
        'managerSubmittedAt' => 'datetime',
        'ceoSubmittedAt' => 'datetime',
        'analyzedAt' => 'datetime',
        'deadlineAt' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employeeId');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'teamId');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(AppraisalCycle::class, 'cycleId');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'managerId');
    }

    public function ceo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'ceoId');
    }

    public function kras(): HasMany
    {
        return $this->hasMany(Kra::class, 'appraisalId')->orderBy('displayOrder', 'asc');
    }

    public function skillRatings(): HasMany
    {
        return $this->hasMany(SkillRating::class, 'appraisalId')->orderBy('displayOrder', 'asc');
    }
}
