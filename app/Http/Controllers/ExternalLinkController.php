<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExternalLinkRequest;
use App\Http\Requests\UpdateExternalLinkRequest;
use App\Models\ExternalLink;
use App\Models\Project;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ExternalLinkController extends Controller
{
    // Menampilkan semua external link project
    public function index(Project $project)
    {
        $links = $project->externalLinks()
            ->orderBy('link_id', 'asc')
            ->get(['link_id', 'url', 'label']);

        return response()->json([
            'message'       => 'Daftar tautan eksternal berhasil dimuat.',
            'data'          => $links,
        ], Response::HTTP_OK);
    }
    
    // Menyimpan external link baru
    public function store(StoreExternalLinkRequest $request, Project $project)
    {
        $validated = $request->validated();

        $link = DB::transaction(function () use ($project, $validated) {
            return ExternalLink::create([
                'project_id'        => $project->project_id,
                'url'               => $validated['url'],
                'label'             => $validated['label'] ?? null,
            ]);
        });

        return response()->json([
            'message' => 'Tautan eksternal berhasil ditambahkan.',
            'data'    => $link,
        ], Response::HTTP_CREATED);
    }

    // Update external link yg sudah ada
    public function update(UpdateExternalLinkRequest $request, Project $project, ExternalLink $link)
    {
        // Make sure link milik project
        if ((int) $link->project_id !== (int) $project->project_id) {
            return response()->json([
                'message'       => 'Tautan tidak ditemukan pada project ini.',
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($link, $validated) {
            $link->update($validated);
        });

        return response()->json([
            'message' => 'Tautan eksternal berhasil diperbarui.',
            'data'    => $link->fresh(),
        ], Response::HTTP_OK);
    }


    // Menghapus external link
    public function destroy(Project $project, ExternalLink $link)
    {
        if ((int) $link->project_id !== (int) $project->project_id) {
            return response()->json([
                'message' => 'Tautan tidak ditemukan pada project ini.',
            ], Response::HTTP_NOT_FOUND);
        }

        DB::transaction(function () use ($link) {
            $link->delete();
        });

        return response()->json([
            'message' => 'Tautan eksternal berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}