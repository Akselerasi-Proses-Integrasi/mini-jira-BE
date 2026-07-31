<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $projectName;
    public $inviterName;
    public $role;
    public $isExistingUser;

    // Buat instance pesan
    public function __construct(
        string $projectName,
        string $inviterName,
        string $invitationUrl,
        string $role,
        bool $isExistingUser = false
    ) {
        $this->projectName    = $projectName;
        $this->inviterName    = $inviterName;
        $this->invitationUrl  = $invitationUrl;
        $this->role           = $role;
        $this->isExistingUser = $isExistingUser;
    }

    public function envelope(): Envelope
    {
        $subject = $this->isExistingUser
            ? "Kamu telah ditambahkan ke proyek: {$this->projectName}"
            : 'Undangan bergabung ke proyek: {$this->projectName}';

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.project-invitation',
            with: [
                'projectName'   => $this->projectName,
                'inviterName'   => $this->inviterName,
                'invitationUrl' => $this->invitationUrl,
                'role'          => $this->role,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}