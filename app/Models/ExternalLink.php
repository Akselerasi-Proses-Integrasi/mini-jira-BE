<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalLink extends Model
{
    protected $primaryKey = 'link_id';
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'url',
        'label',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}