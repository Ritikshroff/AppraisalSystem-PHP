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
        'buHeadId',
        'type',
        'grade',
        'appraisalPeriod',
        'status',
        'sectionOneAnswers',
        'managerReview',
        'buHeadReview',
        'managerOverallRating',
        // Section 5 — Appraiser fields
        'appraiserOverallRating',
        'appraiserRecommendation',
        'appraiserNewKraNotes',
        // Section 6 — Reviewer fields
        'reviewerComments',
        'reviewerRating',
        'finalRating',
        'hikePercentage',
        'promotionRecommended',
        'adjustments',
        'incrementAmount',
        'newCtc',
        'justification',
        'aiPerformanceSummary',
        'sentimentLabel',
        'sentimentScore',
        'aiStrengths',
        'aiWeaknesses',
        'aiRiskSignals',
        'employeeSubmittedAt',
        'managerSubmittedAt',
        'appraiserSignedAt',
        'buHeadSubmittedAt',
        'reviewerSignedAt',
        'analyzedAt',
        'deadlineAt',
        'specialAppeal',
        'specialAppealStatus',
        'specialAppealComments',
        'authorFeedbackRating',
        'authorFeedbackComments',
    ];

    protected $casts = [
        'authorFeedbackRating'   => 'integer',
        'managerOverallRating'   => 'double',
        'appraiserOverallRating' => 'double',
        'reviewerRating'         => 'double',
        'finalRating'            => 'double',
        'hikePercentage'         => 'double',
        'sentimentScore'         => 'double',
        'employeeSubmittedAt'    => 'datetime',
        'managerSubmittedAt'     => 'datetime',
        'appraiserSignedAt'      => 'datetime',
        'buHeadSubmittedAt'      => 'datetime',
        'reviewerSignedAt'       => 'datetime',
        'analyzedAt'             => 'datetime',
        'deadlineAt'             => 'datetime',
        'promotionRecommended'   => 'boolean',
        'adjustments'            => 'double',
        'incrementAmount'        => 'double',
        'newCtc'                 => 'double',
        'specialAppeal'          => 'boolean',
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

    public function buHead(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'buHeadId');
    }

    public function kras(): HasMany
    {
        return $this->hasMany(Kra::class, 'appraisalId')->orderBy('displayOrder', 'asc');
    }

    public function competencyRatings(): HasMany
    {
        return $this->hasMany(CompetencyRating::class, 'appraisalId')->orderBy('displayOrder', 'asc');
    }

    public function skillRatings(): HasMany
    {
        return $this->hasMany(SkillRating::class, 'appraisalId')->orderBy('displayOrder', 'asc');
    }

    public function nextCycleKras(): HasMany
    {
        return $this->hasMany(NextCycleKra::class, 'appraisalId')->orderBy('displayOrder', 'asc');
    }
}
