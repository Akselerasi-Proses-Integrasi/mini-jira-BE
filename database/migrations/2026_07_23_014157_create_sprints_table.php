<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprints', function (Blueprint $table) {
            $table->id('sprint_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id')->cascadeOnDelete();
            $table->string('nama_sprint', 100);
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprints');
    }
};