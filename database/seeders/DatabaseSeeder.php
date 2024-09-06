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
        // Create teams
        $teams = [
            ['name' => 'TrueSource', 'slug' => 'truesource-team'],
            ['name' => 'SOS', 'slug' => 'sos-team'],
        ];

        foreach ($teams as $teamData) {
            Team::create($teamData);
        }

        // Create users and associate them with teams
        $users = [
            [
                'name' => 'Renzo Miranda',
                'email' => 'renzo.miranda@teamspan.com',
                'password' => Hash::make('renzo973'),
                'user_role' => 'Admin',
                'user_lob' => ['MANAGEMENT'],
                'audit_create' => true, 'audit_view' => true, 'audit_update' => true, 'audit_delete' => true,
                'pqc_create' => true, 'pqc_view' => true, 'pqc_update' => true, 'pqc_delete' => true,
                'user_create' => true, 'user_view' => true, 'user_update' => true, 'user_delete' => true,
                'teams' => ['TrueSource', 'SOS']
            ],
            [
                'name' => 'Diona Ramos',
                'email' => 'diona.ramos@teamspan.com',
                'password' => Hash::make('password'),
                'user_role' => 'Manager',
                'user_lob' => ['MANAGEMENT'],
                'audit_create' => true, 'audit_view' => true, 'audit_update' => true, 'audit_delete' => true,
                'pqc_create' => true, 'pqc_view' => true, 'pqc_update' => true, 'pqc_delete' => true,
                'user_create' => true, 'user_view' => true, 'user_update' => true, 'user_delete' => true,
                'teams' => ['TrueSource']
            ],
            [
                'name' => 'Manilyn Lopez',
                'email' => 'manilyn.lopez@teamspan.com',
                'password' => Hash::make('password'),
                'user_role' => 'Manager',
                'user_lob' => ['MANAGEMENT'],
                'audit_create' => true, 'audit_view' => true, 'audit_update' => true, 'audit_delete' => true,
                'pqc_create' => true, 'pqc_view' => true, 'pqc_update' => true, 'pqc_delete' => true,
                'user_create' => true, 'user_view' => true, 'user_update' => true, 'user_delete' => true,
                'teams' => ['SOS']
            ],
            [
                'name' => 'John Doe',
                'email' => 'john.doe@teamspan.com',
                'password' => Hash::make('password'),
                'user_role' => 'Associate',
                'user_lob' => ['CALL ENTERING'],
                'audit_create' => false, 'audit_view' => true, 'audit_update' => true, 'audit_delete' => false,
                'pqc_create' => false, 'pqc_view' => true, 'pqc_update' => true, 'pqc_delete' => false,
                'user_create' => false, 'user_view' => false, 'user_update' => false, 'user_delete' => false,
                'teams' => ['TrueSource']
            ],
            [
                'name' => 'Jane Doe',
                'email' => 'jane.doe@teamspan.com',
                'password' => Hash::make('password'),
                'user_role' => 'Associate',
                'user_lob' => ['ERG FOLLOW-UP'],
                'audit_create' => false, 'audit_view' => true, 'audit_update' => true, 'audit_delete' => false,
                'pqc_create' => false, 'pqc_view' => true, 'pqc_update' => true, 'pqc_delete' => false,
                'user_create' => false, 'user_view' => false, 'user_update' => false, 'user_delete' => false,
                'teams' => ['SOS']
            ],
        ];

        foreach ($users as $userData) {
            $teamNames = $userData['teams'];
            unset($userData['teams']);

            $user = User::create($userData);

            foreach ($teamNames as $teamName) {
                $team = Team::where('name', $teamName)->first();
                if ($team) {
                    $user->teams()->attach($team);
                }
            }
        }
    }
}
