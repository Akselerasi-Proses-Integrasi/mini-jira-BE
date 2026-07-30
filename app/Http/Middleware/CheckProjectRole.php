<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ProjectMember;
use Symfony\Component\HttpFoundation\Response;

class CheckProjectRole
{
    /**
     * @param Request $request
     * @param Closure $next
     * @param string ...$roles  Contoh: 'owner', 'team_leader'
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $project = $request->route('project');

        if (!$project) {
            return response()->json(['message' => 'Project tidak ditemukan.'], 404);
        }

        $projectId = $project->project_id;

        // Cache status membership 
        $membershipKey = "project.role.membership.{$projectId}." . auth()->id();
        $membership = cache()->remember($membershipKey, 60, function () use ($projectId) {
            return ProjectMember::where('project_id', $projectId)
                ->where('user_id', auth()->id())
                ->first();
        });

        if (!$membership || !in_array($membership->role, $roles)) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya owner atau team leader yang diizinkan.',
            ], 403);
        }

        return $next($request);
    }
}