<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;
    protected $primaryKey = 'project_id';
    public $timestamps = false;

    protected $fillable = [
        'nama_proyek',
        'deskripsi',
        'tgl_mulai',
        'tgl_selesai',
        'kode_proyek',
        'status',
        'approval_mode',
        'has_team_leader',
        'owner_id',
    ];

    protected $casts = [
        'has_team_leader' => 'boolean',
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id', 'user_id');
    }

    public function teamLeaders(): HasMany
    {
    return $this->hasMany(ProjectMember::class, 'project_id', 'project_id')
        ->where('role', 'team_leader');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'project_id', 'project_id');
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class, 'project_id', 'project_id');
    }

    public function externalLinks(): HasMany
    {
        return $this->hasMany(ExternalLink::class, 'project_id', 'project_id');
    }
}