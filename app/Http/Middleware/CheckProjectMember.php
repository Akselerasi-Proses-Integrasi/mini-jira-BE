<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ProjectMember;
use Symfony\Component\HttpFoundation\Response;

class CheckProjectMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');

        if (!$project) {
            return response()->json(['message' => 'Project tidak ditemukan.'], 404);
        }

        $projectId = $project->project_id;

        $membership = ProjectMember::where('project_id', $projectId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$membership) {
            return response()->json([
                'message' => 'Akses ditolak. Kamu bukan anggota project ini.',
            ], 403);
        }

        return $next($request);
    }
}