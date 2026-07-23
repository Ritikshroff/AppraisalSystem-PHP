<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'employeeCode',
        'fullName',
        'email',
        'department',
        'designation',
        'role',
        'teamId',
        'managerId',
        'doj',
        'salary',
        'lastHike',
    ];

    protected $casts = [
        'doj' => 'datetime',
        'salary' => 'double',
        'lastHike' => 'double',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'teamId');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'managerId');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(Employee::class, 'managerId');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'employeeId');
    }

    public function appraisals(): HasMany
    {
        return $this->hasMany(Appraisal::class, 'employeeId');
    }

    public function managedAppraisals(): HasMany
    {
        return $this->hasMany(Appraisal::class, 'managerId');
    }

    public function approvedAppraisals(): HasMany
    {
        return $this->hasMany(Appraisal::class, 'ceoId');
    }
}
