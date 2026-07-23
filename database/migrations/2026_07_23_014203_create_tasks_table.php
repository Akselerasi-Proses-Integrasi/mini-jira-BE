<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id('task_id');
            $table->foreignId('sprint_id')->constrained('sprints', 'sprint_id')->cascadeOnDelete();
            $table->foreignId('assigne_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('judul', 200);
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['to do', 'in progress', 'blocked', 'waiting approval', 'done'])
                  ->default('to do');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};