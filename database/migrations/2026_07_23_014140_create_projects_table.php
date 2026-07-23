<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id('project_id');
            $table->string('nama_proyek', 150);
            $table->text('deskripsi')->nullable();
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->string('kode_proyek', 20)->unique();
            $table->enum('status', ['Active', 'Closed'])->default('Active');
            $table->enum('approval_mode', ['default', 'restricted'])->default('default');
            $table->boolean('has_team_leader')->default(false);
            $table->foreignId('owner_id')->constrained('users', 'user_id')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};