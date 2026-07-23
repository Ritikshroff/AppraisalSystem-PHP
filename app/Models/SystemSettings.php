<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSettings extends Model
{
    protected $table = 'system_settings';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'globalDeadlineStart',
        'globalDeadlineEnd',
    ];

    protected $casts = [
        'globalDeadlineStart' => 'datetime',
        'globalDeadlineEnd' => 'datetime',
    ];
}
