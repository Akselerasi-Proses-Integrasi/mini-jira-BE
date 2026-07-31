<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_invitations', function (Blueprint $table) {
            $table->id('invitation_id');
            $table->foreignId('project_id')
                  ->constrained('projects', 'project_id')
                  ->cascadeOnDelete();
            $table->foreignId('invited_by')
                  ->constrained('users', 'user_id')
                  ->cascadeOnDelete();
            $table->string('email', 255);
            $table->enum('role', ['member', 'team_leader'])->default('member');
            $table->string('token', 100)->unique();
            $table->enum('status', ['pending', 'accepted', 'expired', 'cancelled'])
                  ->default('pending');
            $table->timestamps('expires_at');
            $table->timestamps('created_at')->useCurrent();
            $table->timestamps('accepted_at')->nullable();

            // Satu email per project hanya boleh punya 1 undangan aktif (pending)
            $table->unique(['project_id', 'email', 'status']);
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_invitations');
    }
};
