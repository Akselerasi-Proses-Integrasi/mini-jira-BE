<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'nama',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // Project yang dimiliki user ini sebagai owner
    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id', 'user_id');
    }

    // Semua project_member record milik user ini
    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'user_id', 'user_id');
    }

    public function memberProjects(): BelongsToMany
    {
        return $this->belongsToMany(
            Project::class,
            'project_members',
            'user_id',
            'project_id'
        )->withPivot(['role', 'joined_via', 'joined_at']);
    }

    // Task yang di-assign ke user ini
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigne_id', 'user_id');
    }

    // Task yang dibuat oleh user ini
    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by', 'user_id');
    }

    // Comment yang ditulis user ini
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'user_id', 'user_id');
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}