<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sprint extends Model
{
    protected $primaryKey = 'sprint_id';
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'nama_sprint',
        'tgl_mulai',
        'tgl_selesai',
    ];

    protected $casts = [
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'sprint_id', 'sprint_id');
    }
}