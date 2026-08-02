<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\JoinProjectByCodeRequest;
use App\Http\Requests\UpdateTeamLeaderConfigRequest;
use App\Http\Requests\UpdateApprovalModeRequest;
use App\Http\Requests\AssignTeamLeaderRequest;
use App\Http\Requests\RevokeTeamLeaderRequest;
use App\Http\Requests\SendProjectInvitationRequest;
use App\Http\Requests\AcceptProjectInvitationRequest;
use App\Mail\ProjectInvitationMail;
use App\Models\ProjectInvitation;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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

    public function updateApprovalMode(UpdateApprovalModeRequest $request, Project $project)
{
    $validated = $request->validated();

    $newValue = $validated['approval_mode'];
    $oldValue = $project->approval_mode;

    if ($newValue === $oldValue) {
        return response()->json([
            'message' => "Mode approval sudah '{$newValue}' pada proyek ini.",
            'data'    => $project->load('owner', 'externalLinks'),
        ], Response::HTTP_OK);
    }

    DB::transaction(function () use ($project, $newValue) {
        $project->approval_mode = $newValue;
        $project->save();
    });

    return response()->json([
        'message' => "Mode approval berhasil diubah menjadi '{$newValue}'.",
        'data'    => $project->load('owner', 'externalLinks'),
    ], Response::HTTP_OK);
}

    // Assign team leader pada member tertentu
    public function assignTeamLeader(AssignTeamLeaderRequest $request, Project $project)
    {
        $validated = $request->validated();

        // Fitur has_team_leader harus aktif pada proyek
        if (!$project->has_team_leader) {
            return response()->json([
                'message'   => 'Fitur team leader belum diaktifkan pada proyek ini.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $targetUser = User::findOrFail($validated['user_id']);

        // Owner tidak bisa di-assign menjadi 'team leader' 
        if ((int) $targetUser->user_id === (int) $project->owner_id) {
            return response()->json([
                'message' => 'Owner tidak dapat dijadikan team leader.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Cek apakah user member dari project
        $membership = ProjectMember::where('project_id', $project->project_id)
            ->where('user_id', $targetUser->user_id)
            ->first();

        if (!$membership) {
            return response()->json([
                'message' => 'Anggota tersebut bukan member proyek ini.',
            ], Response::HTTP_NOT_FOUND);
        }
        
        // Owner tidak bisa di-assign menjadi 'team leader', karena 'owner' adalah role tertinggi
        if ($membership->role === 'owner') {
            return response()->json([
                'message'   => 'Owner tidak dapat dijadikan team leader.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Memastikan apakah user sudah menjadi team leader sebelumnya
        if ($membership->role === 'team_leader') {
            return response()->json([
                'message'   => 'Anggota tersebut sudah menjadi team leader.',
                'data'      => $project->load('owner', 'members', 'externalLinks'),
            ], Response::HTTP_OK);
        }

        // Update role user menjadi 'team_leader'
        DB::transaction(function () use ($membership) {
            $membership->update(['role' => 'team_leader']);
        });

        return response()->json([
            'message' => 'Team leader berhasil ditetapkan.',
            'data'    => $project->load('owner', 'members', 'externalLinks'),
        ], Response::HTTP_OK);
    }

    public function revokeTeamLeader(RevokeTeamLeaderRequest $request, Project $project, User $user)
    {

        // Fitur has_team_leader harus aktif pada proyek
        if (!$project->has_team_leader) {
            return response()->json([
                'message'   => 'Fitur team leader belum diaktifkan pada proyek ini.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Cek apakah user member dari project
        $membership = ProjectMember::where('project_id', $project->project_id)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$membership) {
            return response()->json([
                'message' => 'Anggota tersebut bukan member proyek ini.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Target user bukan 'team_leader'
        if ($membership->role !== 'team_leader') {
            return response()->json([
                'message'   => 'Anggota tersebut bukan team leader.',
                'data'      => $project->load('owner', 'members', 'externalLinks'),
            ], Response::HTTP_OK);
        }

        // Revoke role 'team_member' dan update menjadi member
        DB::transaction(function () use ($membership) {
            $membership->update(['role' => 'member']);
        });

        return response()->json([
            'message' => 'Peran team leader berhasil dicabut.',
            'data'    => $project->load('owner', 'members', 'externalLinks'),
        ], Response::HTTP_OK);

    }

    // Kirim undangan via email ke proyek.
    // Hanya owner atau team leader yang bisa mengirim undangan.
    // Jika email terdaftar, langsung jadi member + kirim notifikasi.
    // Jika email belum terdaftar, buat record ProjectInvitation + kirim link undangan.
    public function sendInvitation(SendProjectInvitationRequest $request, Project $project)
    {
        $email = strtolower(trim($request->email));
        $requestedRole = $request->role ?? 'member';

        // Jika user sudah menjadi member → tolak (idempoten)
        $existingMember = ProjectMember::where('project_id', $project->project_id)
            ->whereHas('user', fn($q) => $q->where('email', $email))
            ->first();

        if ($existingMember) {
            return response()->json([
                'message' => 'Pengguna dengan email tersebut sudah menjadi anggota project ini.',
            ], Response::HTTP_CONFLICT);
        }

        // Cek apakah ada undangan pending yang sama (idempoten)
        $pendingInvitation = ProjectInvitation::where('project_id', $project->project_id)
            ->where('email', $email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($pendingInvitation) {
            // Kirim ulang email yang sama untuk UX (atau bisa return 200 dengan pesan)
            Mail::to($email)->later(
                now()->addSeconds(5),
                new ProjectInvitationMail(
                    projectName: $project->nama_proyek,
                    inviterName: auth()->user()->nama,
                    invitationUrl: URL::temporarySignedRoute(
                        'invitations.accept',
                        now()->addDays(7),
                        ['token' => $pendingInvitation->token]
                    ),
                    role: $pendingInvitation->role,
                    isExistingUser: false
                )
            );

            return response()->json([
                'message' => 'Undangan masih berlaku. Email undangan telah dikirim ulang.',
            ], Response::HTTP_OK);
        }

        // Cek apakah user dengan email tersebut sudah terdaftar
        $user = User::where('email', $email)->first();

        DB::transaction(function () use ($project, $user, $email, $requestedRole, $existingMember) {
            if ($user && !$existingMember) {
                // User EXISTS: langsung menjadi member
                ProjectMember::create([
                    'project_id' => $project->project_id,
                    'user_id'    => $user->user_id,
                    'role'       => $requestedRole === 'team_leader' ? 'team_leader' : 'member',
                    'joined_via' => 'invite',
                    'joined_at'  => now(),
                ]);
            } else {
                // User BELUM ADA: buat record invitation
                $token = Str::random(64);

                ProjectInvitation::create([
                    'project_id'  => $project->project_id,
                    'invited_by'  => auth()->id(),
                    'email'       => $email,
                    'role'        => $requestedRole === 'team_leader' ? 'team_leader' : 'member',
                    'token'       => $token,
                    'status'      => 'pending',
                    'expires_at'  => now()->addDays(7),
                    'created_at'  => now(),
                ]);
            }
        });

        // Kirim email (di luar transaksi agar tidak memblokir)
        if ($user) {
            Mail::to($email)->later(
                now()->addSeconds(2),
                new ProjectInvitationMail(
                    projectName: $project->nama_proyek,
                    inviterName: auth()->user()->nama,
                    invitationUrl: url('/projects/' . $project->kode_proyek),
                    role: $requestedRole === 'team_leader' ? 'team_leader' : 'member',
                    isExistingUser: true
                )
            );
        } else {
            $invitation = ProjectInvitation::where('project_id', $project->project_id)
                ->where('email', $email)
                ->where('status', 'pending')
                ->latest('created_at')
                ->first();

            Mail::to($email)->later(
                now()->addSeconds(2),
                new ProjectInvitationMail(
                    projectName: $project->nama_proyek,
                    inviterName: auth()->user()->nama,
                    invitationUrl: URL::temporarySignedRoute(
                        'invitations.accept',
                        now()->addDays(7),
                        ['token' => $invitation->token]
                    ),
                    role: $requestedRole,
                    isExistingUser: false
                )
            );
        }

        return response()->json([
            'message' => $user
                ? 'Undangan telah dikirim. Pengguna telah menjadi anggota project.'
                : 'Undangan telah dikirim ke email.',
        ], Response::HTTP_CREATED);
    }

    // User menerima undangan dengan token.
    public function acceptInvitation(AcceptProjectInvitationRequest $request)
    {
        $invitation = $request->invitation;
        $user = auth()->user();

        // Pastikan user dengan email yang diundang cocok
        if (strtolower($invitation->email) !== strtolower($user->email)) {
            return response()->json([
                'message' => 'Email kamu tidak cocok dengan undangan.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Cek apakah sudah jadi member
        $alreadyMember = ProjectMember::where('project_id', $invitation->project_id)
            ->where('user_id', $user->user_id)
            ->first();

        if ($alreadyMember) {
            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            return response()->json([
                'message' => 'Kamu sudah menjadi anggota project ini.',
                'data'    => $invitation->project->load('owner', 'members'),
            ], Response::HTTP_OK);
        }

        DB::transaction(function () use ($invitation, $user) {
            ProjectMember::create([
                'project_id' => $invitation->project_id,
                'user_id'    => $user->user_id,
                'role'       => $invitation->role,
                'joined_via' => 'invite',
                'joined_at'  => now(),
            ]);

            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Berhasil bergabung ke project.',
            'data'    => $invitation->project->load('owner', 'members'),
        ], Response::HTTP_OK);
    }

    public function listInvitations(Project $project)
    {
        $invitations = ProjectInvitation::where('project_id', $project->project_id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Daftar undangan.',
            'data'    => $invitations,
        ], Response::HTTP_OK);
    }

    public function cancelInvitation(Project $project, $invitationId)
    {
        $invitation = ProjectInvitation::where('project_id', $project->project_id)
            ->where('invitation_id', $invitationId)
            ->where('status', 'pending')
            ->firstOrFail();

        $invitation->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Undangan berhasil dibatalkan.',
        ], Response::HTTP_OK);
    }

    private function generateUniqueProjectCode(): string
    {
        do {
            $code = 'PRJ-' . strtoupper(Str::random(8));
        } while (Project::where('kode_proyek', $code)->exists());

        return $code;
    }
}