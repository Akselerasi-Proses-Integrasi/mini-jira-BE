<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\JoinProjectByCodeRequest;
use App\Http\Requests\UpdateTeamLeaderConfigRequest;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\Response;
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
                'has_team_leader'   => $validated['has_team_leader'] ?? false,
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
            'data'    => $project->load('owner', 'externalLinks'),
        ], 201);
    }

    public function joinByCode(JoinProjectByCodeRequest $request)
    {
        $validated = $request->validated();

        $result = DB::transaction(function () use ($validated){
            // Cari project berdasarkan kode_proyek dengan constrain status 'Active'
            $project = Project::where('kode_proyek', $validated['kode_proyek'])
                ->where('status', 'Active')
                ->first();

            if (!$project) {
                return [
                    'success' => false,
                    'message' => 'Kode proyek tidak ditemukan atau project sudah ditutup.',
                    'code'    => Response::HTTP_NOT_FOUND
                ];
            }

            // Cek apakah user sudah menjadi member
            $existingMember = ProjectMember::where('project_id', $project->project_id)
                ->where('user_id', auth()->id())
                ->first();

            if ($existingMember) {
                return [
                    'success' => false,
                    'message' => 'Kamu sudah menjadi anggota project ini.',
                    'code'    => Response::HTTP_CONFLICT
                ];
            }

            // Insert member baru (user) ke project
            ProjectMember::create([
                'project_id' => $project->project_id,
                'user_id'    => auth()->id(),
                'role'       => 'member',
                'joined_via' => 'code',
                'joined_at'  => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Berhasil bergabung ke project.',
                'data'    => $project->load('owner', 'externalLinks'),
                'code'    => Response::HTTP_OK,
            ];


        });

        // Transaction gagal (business logic error), return tanpa data
        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['code']);
        }

        return response()->json([
            'message' => $result['message'],
            'data'    => $result['data'],
        ], $result['code']);
    }

    public function updateTeamLeader(UpdateTeamLeaderConfigRequest $request, Project $project)
    {
        $validated = $request->validated();

        // Hanya user dgn role 'owner' yg bisa ubah konfigurasi
        if ((int) $project->owner_id !== (int) auth()->id()){
            return response()->json([
                'message' => 'Hanya owner proyek yang dapat mengubah struktur tim.',
            ], 403);
        }

        $newValue = (bool) $validated['has_team_leader'];
        $oldValue = (bool) $project->has_team_leader;

        if ($newValue == $oldValue) {
            $statusText = $newValue ? 'aktif' : 'non-aktif';

            return response()->json([
                'message'      => "Peran Team Leader sudah {$statusText} pada proyek ini.",
                'data'         => $project->load('owner', 'externalLinks'),
            ], 200);
        }

        DB::transaction(function () use ($project, $newValue) {
            // Jika has_team_leader di non-aktifkan, demote semua team_leader menjadi member
            if ($newValue === false) {
                ProjectMember::where('project_id', $project->project_id)
                    ->where('role', 'team_leader')
                    ->update(['role' => 'member']);
            }

            $project->has_team_leader = $newValue;
            $project->save();

        });

        $msg = $newValue
            ? 'Peran Team Leader berhasil diaktifkan.'
            : 'Peran Team Leader berhasil dinonaktifkan. Semua anggota dengan role Team Leader telah diubah menjadi Member.';

        return response()->json([
            'message' => $msg,
            'data'    => $project->load('owner', 'externalLinks'),
        ], 200);
        
    }

    private function generateUniqueProjectCode(): string
    {
        do {
            $code = 'PRJ-' . strtoupper(Str::random(8));
        } while (Project::where('kode_proyek', $code)->exists());

        return $code;
    }
}