<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'email',
        'passwordHash',
        'name',
        'role',
        'teamId',
        'employeeId',
    ];

    protected $hidden = [
        'passwordHash',
        'remember_token',
    ];

    // Auth password mapping (Prisma database field passwordHash)
    public function getAuthPasswordName(): string
    {
        return 'passwordHash';
    }

    public function getAuthPassword(): string
    {
        return $this->passwordHash;
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'teamId');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employeeId');
    }
}
