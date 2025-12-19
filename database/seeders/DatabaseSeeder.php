<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create System Admin
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'system_admin',
        ]);

        // Create Teams
        $engineeringTeam = Team::create([
            'name' => 'Alpha Team',
            'description' => 'Main engineering department',
            'is_active' => true,
        ]);

        $salesTeam = Team::create([
            'name' => 'Bravo Team',
            'description' => 'Sales department',
            'is_active' => true,
        ]);

        // Create Team Admin for Engineering Team
        User::create([
            'name' => 'Alpha Admin',
            'email' => 'a.admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'team_admin',
            'team_id' => $engineeringTeam->id,
        ]);

        // Create Team Member for Engineering Team
        User::create([
            'name' => 'Alpha Member',
            'email' => 'a.member@example.com',
            'password' => Hash::make('password'),
            'role' => 'team_member',
            'team_id' => $engineeringTeam->id,
        ]);

        // Create Team Admin for Sales Team
        User::create([
            'name' => 'Bravo Admin',
            'email' => 'b.admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'team_admin',
            'team_id' => $salesTeam->id,
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('');
        $this->command->info('Login Credentials:');
        $this->command->info('System Admin: admin@example.com / password');
        $this->command->info('Alpha Admin: a.admin@example.com / password');
        $this->command->info('Alpha Member: a.member@example.com / password');
        $this->command->info('Bravo Admin: b.admin@example.com / password');
    }
}
