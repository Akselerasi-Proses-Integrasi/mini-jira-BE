<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class CommentController extends Controller
{
    // Mengambil daftar komentar pada Task
    public function index(Project $project, Task $task)
    {
        $task->load('sprint');

        if ($task->sprint->project_id !== $project->project_id) {
            return response()->json([
                'message' => 'Task tidak valid untuk proyek ini.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $comments = $task->comments()->with('user:user_id,nama,email')->get();

        return response()->json([
            'message' => 'Berhasil mengambil daftar komentar.',
            'data'    => $comments
        ], Response::HTTP_OK);
    }

    // Menyimpan komentar baru
    public function store(Request $request, Project $project, Task $task)
    {
        $task->load('sprint');
        if ($task->sprint->project_id !== $project->project_id) {
            return response()->json([
                'message' => 'Task tidak valid untuk proyek ini.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $validated = $request->validate([
            'isi_teks'   => 'required_without_all:attachment,link_url|nullable|string',
            'attachment' => 'required_without_all:isi_teks,link_url|nullable|file|max:5120',
            'link_url'   => 'required_without_all:isi_teks,attachment|nullable|url|max:500',
        ]);

        $attachmentUrl = null;

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('comments_attachments', 'public');
            $attachmentUrl = url('storage/' . $path);
        }

        $comment = $task->comments()->create([
            'user_id'        => auth()->id(),
            'isi_teks'       => $validated['isi_teks'] ?? null,
            'attachment_url' => $attachmentUrl,
            'link_url'       => $validated['link_url'] ?? null,
        ]);

        return response()->json([
            'message' => 'Komentar berhasil ditambahkan.',
            'data'    => $comment
        ], Response::HTTP_CREATED);
    }

    // Menghapus komentar
    public function destroy(Project $project, Task $task, Comment $comment)
    {
        if ($comment->task_id !== $task->task_id) {
            return response()->json([
                'message' => 'Komentar tidak ditemukan pada task ini.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($comment->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Akses ditolak. Anda hanya dapat menghapus komentar Anda sendiri.'
            ], Response::HTTP_FORBIDDEN);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Komentar berhasil dihapus.'
        ], Response::HTTP_OK);
    }
}