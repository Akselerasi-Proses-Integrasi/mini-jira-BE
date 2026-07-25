<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'nama_proyek'     => fake()->sentence(3),
            'deskripsi'       => fake()->paragraph(),
            'tgl_mulai'       => fake()->date(),
            'tgl_selesai'     => fake()->date(),
            'kode_proyek'     => 'PRJ-' . strtoupper(fake()->unique()->bothify('????????')),
            'status'          => 'Active',
            'approval_mode'   => 'default',
            'has_team_leader' => false,
        ];
    }
}
