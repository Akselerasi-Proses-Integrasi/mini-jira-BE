<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
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
}