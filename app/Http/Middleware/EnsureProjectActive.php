<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Project;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');

        if (!$project instanceof Project) {
            return $next($request);
        }

        if (strtolower($project->status) === 'closed') {
            return response()->json([
                'message' => 'Proyek sudah ditutup (Read-Only). Tidak dapat melakukan operasi ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}