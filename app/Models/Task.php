<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $primaryKey = 'task_id';
    public $timestamps = false;

    protected $fillable = [
        'sprint_id',
        'assigne_id',
        'created_by',
        'judul',
        'deskripsi',
        'status',
    ];

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class, 'sprint_id', 'sprint_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigne_id', 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'task_id', 'task_id');
    }
}