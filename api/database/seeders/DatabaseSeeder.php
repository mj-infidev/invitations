<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Every seeder is safe to run again: it
     * updates its rows rather than adding copies.
     */
    public function run(): void
    {
        $this->call([
            TemplateSeeder::class,
            AdminSeeder::class,
            DemoInvitationSeeder::class,
        ]);
    }
}
