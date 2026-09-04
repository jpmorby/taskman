<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Local development fixtures only - never populate a production database.
        if (app()->isProduction()) {
            $this->command?->warn('Skipping seeders: this database is running in production.');

            return;
        }

        $this->seedAdmin();

        Task::factory()->count(10)->create();
    }

    /**
     * Create the local admin account.
     *
     * The password comes from SEED_ADMIN_PASSWORD; without it a random one is
     * generated and printed once. Never hard-code a password here.
     */
    protected function seedAdmin(): void
    {
        $password = (string) config('seeding.admin_password', '');

        if ($password === '') {
            $password = Str::password(24);
            $this->command?->info("Generated admin password: {$password}");
            $this->command?->comment('Set SEED_ADMIN_PASSWORD in your .env to choose your own.');
        }

        User::factory()->create([
            'name' => 'Jon Morby',
            'email' => 'jon@redmail.com',
            'password' => $password,
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);
    }
}
