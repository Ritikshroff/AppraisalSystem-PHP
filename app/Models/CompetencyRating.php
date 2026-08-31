<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetencyRating extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'appraisalId',
        'competencyName',
        'employeeScore',
        'appraiserScore',
        'displayOrder',
    ];

    protected $casts = [
        'employeeScore'  => 'integer',
        'appraiserScore' => 'integer',
        'displayOrder'   => 'integer',
    ];

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class, 'appraisalId');
    }
}
