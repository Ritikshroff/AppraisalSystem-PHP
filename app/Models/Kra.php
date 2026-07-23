<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kra extends Model
{
    protected $table = 'kras';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'appraisalId',
        'objective',
        'weightage',
        'appraiseeRating',
        'appraiserRating',
        'comments',
        'displayOrder',
    ];

    protected $casts = [
        'weightage' => 'double',
        'appraiseeRating' => 'double',
        'appraiserRating' => 'double',
    ];

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class, 'appraisalId');
    }
}
