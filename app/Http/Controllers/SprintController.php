<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SprintController extends Controller
{
    private function ensureProjectIsActive(Project $project)
    {
        if (strtolower($project->status) === 'closed') {
            abort(response()->json([
                'message' => 'Proyek sudah ditutup (Read-Only). Tidak dapat melakukan modifikasi data.'
            ], Response::HTTP_FORBIDDEN));
        }
    }

    public function index(Project $project)
    {
        $sprints = $project->sprints()->orderBy('tgl_mulai', 'asc')->get();

        return response()->json([
            'message' => 'Berhasil mengambil daftar sprint.',
            'data'    => $sprints
        ], Response::HTTP_OK);
    }

    public function store(Request $request, Project $project)
    {
        $this->ensureProjectIsActive($project);

        $validated = $request->validate([
            'nama_sprint' => 'required|string|max:100',
            'tgl_mulai'   => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
        ]);

        $sprint = $project->sprints()->create($validated);

        return response()->json([
            'message' => 'Sprint berhasil dibuat.',
            'data'    => $sprint
        ], Response::HTTP_CREATED);
    }

    public function show(Project $project, Sprint $sprint)
    {
        if ($sprint->project_id !== $project->project_id) {
            return response()->json([
                'message' => 'Sprint tidak ditemukan pada proyek ini.'
            ], Response::HTTP_NOT_FOUND);
        }

        $sprint->load('tasks'); 

        return response()->json([
            'message' => 'Berhasil mengambil detail sprint.',
            'data'    => $sprint
        ], Response::HTTP_OK);
    }

    public function update(Request $request, Project $project, Sprint $sprint)
    {
        $this->ensureProjectIsActive($project);

        if ($sprint->project_id !== $project->project_id) {
            return response()->json([
                'message' => 'Sprint tidak ditemukan pada proyek ini.'
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'nama_sprint' => 'sometimes|required|string|max:100',
            'tgl_mulai'   => 'sometimes|required|date',
            'tgl_selesai' => 'sometimes|required|date|after_or_equal:tgl_mulai',
        ]);

        $sprint->update($validated);

        return response()->json([
            'message' => 'Sprint berhasil diperbarui.',
            'data'    => $sprint
        ], Response::HTTP_OK);
    }

    public function destroy(Project $project, Sprint $sprint)
    {
        $this->ensureProjectIsActive($project);

        if ($sprint->project_id !== $project->project_id) {
            return response()->json([
                'message' => 'Sprint tidak ditemukan pada proyek ini.'
            ], Response::HTTP_NOT_FOUND);
        }

        $sprint->delete();

        return response()->json([
            'message' => 'Sprint berhasil dihapus.'
        ], Response::HTTP_OK);
    }
}