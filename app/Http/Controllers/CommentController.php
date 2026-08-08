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
    // Pembatasan Komentar saat Closed
    private function ensureProjectIsActive(Project $project)
    {
        if (strtolower($project->status) === 'closed') {
            abort(response()->json([
                'message' => 'Proyek sudah ditutup (Read-Only). Tidak dapat menambahkan komentar baru.'
            ], Response::HTTP_FORBIDDEN));
        }
    }

    // Mengambil daftar komentar pada Task
    public function index(Project $project, Task $task)
    {
        $task->load('sprint');
        
        // Memastikan task benar-benar berada di bawah proyek yang diakses
        if ($task->sprint->project_id !== $project->project_id) {
            return response()->json([
                'message' => 'Task tidak valid untuk proyek ini.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Load komentar beserta data user yang menulisnya
        $comments = $task->comments()->with('user:user_id,nama,email')->get();

        return response()->json([
            'message' => 'Berhasil mengambil daftar komentar.',
            'data'    => $comments
        ], Response::HTTP_OK);
    }

    // Menyimpan komentar baru
    public function store(Request $request, Project $project, Task $task)
    {
        // Validasi read-only mode
        $this->ensureProjectIsActive($project);

        $task->load('sprint');
        if ($task->sprint->project_id !== $project->project_id) {
            return response()->json([
                'message' => 'Task tidak valid untuk proyek ini.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validasi: Wajib mengisi setidaknya satu dari tiga (teks, file, atau link)
        $validated = $request->validate([
            'isi_teks'   => 'required_without_all:attachment,link_url|nullable|string',
            'attachment' => 'required_without_all:isi_teks,link_url|nullable|file|max:5120', // Maksimum 5MB
            'link_url'   => 'required_without_all:isi_teks,attachment|nullable|url|max:500',
        ]);

        $attachmentUrl = null;

        // Upload Gambar/Berkas
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('comments_attachments', 'public');
            $attachmentUrl = url('storage/' . $path);
        }

        // Teks & Hyperlink disimpan ke database
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
        $this->ensureProjectIsActive($project);

        if ($comment->task_id !== $task->task_id) {
            return response()->json([
                'message' => 'Komentar tidak ditemukan pada task ini.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Hanya penulis komentar yang boleh menghapus
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