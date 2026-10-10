<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * The first admin, from ADMIN_EMAIL and ADMIN_PASSWORD in .env. Running it
     * again resets that admin's password to the one in .env.
     */
    public function run(): void
    {
        $email = config('auth.seed_admin.email');
        $password = config('auth.seed_admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL or ADMIN_PASSWORD is empty in .env: no admin seeded.');

            return;
        }

        Admin::updateOrCreate(
            ['email' => $email],
            ['name' => 'Admin', 'password' => $password], // hashed by the model's cast
        );
    }
}
