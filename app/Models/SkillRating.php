<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillRating extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'appraisalId',
        'skillName',
        'employeeRating',
        'managerRating',
        'displayOrder',
    ];

    protected $casts = [
        'employeeRating' => 'integer',
        'managerRating' => 'integer',
    ];

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class, 'appraisalId');
    }
}
