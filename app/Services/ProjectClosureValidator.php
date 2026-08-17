<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Collection;

class ProjectClosureValidator
{
    public function getIncompleteTasks(Project $project): Collection
    {
        return $project->sprints()
            ->get()
            ->flatMap(fn($sprint) => $sprint->tasks)
            ->filter(fn($task) => strtolower($task->status) !== 'done')
            ->values();
    }

    public function hasIncompleteTasks(Project $project): bool
    {
        return $this->getIncompleteTasks($project)->isNotEmpty();
    }
}