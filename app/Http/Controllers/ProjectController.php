<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function store(StoreProjectRequest $request)
    {
        $validated = $request->validated();

        $project = DB::transaction(function () use ($validated) {
            $project = Project::create([
                'nama_proyek'       => $validated['nama_proyek'],
                'deskripsi'         => $validated['deskripsi'],
                'tgl_mulai'         => $validated['tgl_mulai'],
                'tgl_selesai'       => $validated['tgl_selesai'],
                'kode_proyek'       => $this->generateUniqueProjectCode(),
                'status'            => 'Active',
                'approval_mode'     => 'default',
                'has_team_leader'   => false,
                'owner_id'          => auth()->id(),
            ]);

            // Pembuat proyek otomatis role 'owner'
            ProjectMember::create([
                'project_id' => $project->project_id,
                'user_id'    => auth()->id(),
                'role'       => 'owner',
                'joined_via' => 'direct',
                'joined_at'  => now(),
            ]);

            return $project;
        });

        return response()->json([
            'message' => 'Proyek berhasil dibuat.',
            'data'    => $project->load('owner'),
        ], 201);
    }

    private function generateUniqueProjectCode(): string
    {
        do {
            $code = 'PRJ-' . strtoupper(Str::random(8));
        } while (Project::where('kode_proyek', $code)->exists());

        return $code;
    }
}