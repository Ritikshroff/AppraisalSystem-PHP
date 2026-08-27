<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NextCycleKra extends Model
{
    protected $table = 'next_cycle_kras';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'appraisalId',
        'objective',
        'weightage',
        'displayOrder',
    ];

    protected $casts = [
        'weightage' => 'double',
    ];

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class, 'appraisalId');
    }
}
