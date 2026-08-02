<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongTo;

class ProjectInvitation extends Model
{
    protected $primaryKey = 'invitation_id';
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'invited_by',
        'email',
        'role',
        'token',
        'status',
        'expires_at',
        'created_at',
        'accepted_at',
    ];

    protected $cast = [
        'expires_at'    => 'datetime',
        'created_at'    => 'datetime',
        'accepted_at'   => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by', 'user_id');
    }

    // Scope Undangan yang masih bisa diterima (pending & belum expired)
    public function scopeValid($query)
    {
        return $query->where('status', 'pending')
                     ->where('expires_at', '>', now());
    }
}