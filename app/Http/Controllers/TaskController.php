<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\ProjectMember;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    private function ensureProjectIsActive(Project $project)
    {
        if (strtolower($project->status) === 'closed') {
            abort(response()->json([
                'message' => 'Proyek sudah ditutup (Read-Only). Tidak dapat melakukan modifikasi task.'
            ], Response::HTTP_FORBIDDEN));
        }
    }

    private function getCurrentUserRole(Project $project)
    {
        $membership = ProjectMember::where('project_id', $project->project_id)
            ->where('user_id', auth()->id())
            ->first();

        return $membership ? $membership->role : null;
    }

    private function getTaskCreatorId(Task $task): ?int
    {
        return $task->created_by;
    }

    private function getTaskAssigneeId(Task $task): ?int
    {
        return $task->assigne_id;
    }

    public function index(Project $project, Sprint $sprint)
    {
        if ($sprint->project_id !== $project->project_id) {
            return response()->json(['message' => 'Sprint tidak ditemukan pada proyek ini.'], 404);
        }

        $tasks = $sprint->tasks()->with(['assignee', 'creator'])->get();

        return response()->json([
            'message' => 'Berhasil mengambil daftar task.',
            'data'    => $tasks
        ], 200);
    }

    public function store(Request $request, Project $project, Sprint $sprint)
    {
        $this->ensureProjectIsActive($project);

        if ($sprint->project_id !== $project->project_id) {
            return response()->json(['message' => 'Sprint tidak valid untuk proyek ini.'], 400);
        }

        $validated = $request->validate([
            'judul'      => 'required|string|max:200',
            'deskripsi'  => 'nullable|string',
            'assigne_id' => 'nullable|exists:users,user_id', // Opsional
        ]);

        if (!empty($validated['assigne_id'])) {
            $isMember = ProjectMember::where('project_id', $project->project_id)
                ->where('user_id', $validated['assigne_id'])->exists();
            if (!$isMember) {
                return response()->json(['message' => 'Assignee harus merupakan anggota proyek ini.'], 400);
            }
        }

        $task = $sprint->tasks()->create([
            'judul'      => $validated['judul'],
            'deskripsi'  => $validated['deskripsi'] ?? null,
            'assigne_id' => $validated['assigne_id'] ?? null,
            'created_by' => auth()->id(),
            'status'     => 'to do', // Default status awal
        ]);

        return response()->json([
            'message' => 'Task berhasil dibuat.',
            'data'    => $task
        ], 201);
    }

    public function update(Request $request, Project $project, Sprint $sprint, Task $task)
    {
        $this->ensureProjectIsActive($project);
        if ($task->sprint_id !== $sprint->sprint_id) return response()->json(['message' => 'Task tidak valid.'], 400);

        $validated = $request->validate([
            'judul'      => 'sometimes|required|string|max:200',
            'deskripsi'  => 'nullable|string',
            'assigne_id' => 'nullable|exists:users,user_id',
        ]);

        $task->update($validated);

        return response()->json([
            'message' => 'Task berhasil diperbarui.',
            'data'    => $task
        ], 200);
    }

    public function updateStatus(Request $request, Project $project, Sprint $sprint, Task $task)
    {
        $this->ensureProjectIsActive($project);
        if ($task->sprint_id !== $sprint->sprint_id) return response()->json(['message' => 'Task tidak valid.'], 400);

        $validated = $request->validate([
            'status' => 'required|in:to do,in progress,blocked,waiting approval,done'
        ]);

        $newStatus = $validated['status'];
        $oldStatus = $task->status;
        $userRole  = $this->getCurrentUserRole($project);
        $currentUserId = auth()->id();

        if ($newStatus === $oldStatus) {
            return response()->json(['message' => 'Status tidak ada perubahan.', 'data' => $task], 200);
        }

        if ($oldStatus === 'done') {
            if (!in_array($userRole, ['owner', 'team_leader'])) {
                return response()->json(['message' => 'Akses ditolak. Hanya Owner dan Team Leader yang dapat melakukan reopen pada task berstatus Done.'], 403);
            }
        }

        if ($oldStatus === 'waiting approval' && $newStatus === 'in progress') {
            if (strtolower($project->approval_mode) === 'restricted') {
                if (!in_array($userRole, ['owner', 'team_leader'])) {
                    return response()->json(['message' => 'Mode proyek ini Restricted. Hanya Owner atau Team Leader yang bisa melakukan reject task yang menunggu approval.'], 403);
                }
            } else {
                $isAssignee = $this->getTaskAssigneeId($task) === $currentUserId;
                $isCreator  = $this->getTaskCreatorId($task) === $currentUserId;

                if (!in_array($userRole, ['owner', 'team_leader']) && !$isAssignee && !$isCreator) {
                    return response()->json(['message' => 'Akses ditolak. Hanya Assignee, Pembuat task, Team Leader, atau Owner yang dapat melakukan reject task.'], 403);
                }
            }
        }

        if ($newStatus === 'done' && $oldStatus === 'waiting approval') {
            if (strtolower($project->approval_mode) === 'restricted') {
                if (!in_array($userRole, ['owner', 'team_leader'])) {
                    return response()->json(['message' => 'Mode proyek ini Restricted. Hanya Owner atau Team Leader yang bisa memvalidasi task menjadi Done.'], 403);
                }
            }
        }

        $allowedTransitions = [
            'to do'            => ['in progress', 'blocked'],
            'in progress'      => ['to do', 'blocked', 'waiting approval'],
            'blocked'          => ['to do', 'in progress'],
            'waiting approval' => ['done', 'in progress'],
            'done'             => ['to do', 'in progress'],
        ];

        if (!in_array($newStatus, $allowedTransitions[$oldStatus])) {
            return response()->json([
                'message' => "Transisi status tidak valid. Tidak bisa mengubah status dari '$oldStatus' langsung ke '$newStatus'."
            ], 400);
        }

        $task->update(['status' => $newStatus]);

        return response()->json([
            'message' => 'Status task berhasil diperbarui.',
            'data'    => $task
        ], 200);
    }

    public function destroy(Project $project, Sprint $sprint, Task $task)
    {
        $this->ensureProjectIsActive($project);
        if ($task->sprint_id !== $sprint->sprint_id) return response()->json(['message' => 'Task tidak valid.'], 400);

        $userRole = $this->getCurrentUserRole($project);
        if (!in_array($userRole, ['owner', 'team_leader']) && $task->created_by !== auth()->id()) {
             return response()->json(['message' => 'Hanya Owner, Team Leader, atau Pembuat task yang bisa menghapus task ini.'], 403);
        }

        $task->delete();

        return response()->json(['message' => 'Task berhasil dihapus.'], 200);
    }
}