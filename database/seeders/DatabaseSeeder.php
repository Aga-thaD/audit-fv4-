<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Renzo Miranda',
            'email' => 'renzo.miranda@teamspan.com',
            'password' => 'renzo973',
            'user_role' => 'Admin',
            'audit_create' => true,
            'audit_view' => true,
            'audit_update' => true,
            'audit_delete' => true,
            'pqc_create' => true,
            'pqc_view' => true,
            'pqc_update' => true,
            'pqc_delete' => true,
            'user_create' => true,
            'user_view' => true,
            'user_update' => true,
            'user_delete' => true,
        ]);

        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@teamspan.com',
            'password' => 'renzo973',
            'user_role' => 'Associate',
            'user_lob' => 'CALL ENTERING',
            'audit_create' => false,
            'audit_view' => true,
            'audit_update' => true,
            'audit_delete' => false,
            'pqc_create' => false,
            'pqc_view' => true,
            'pqc_update' => true,
            'pqc_delete' => false,
            'user_create' => false,
            'user_view' => false,
            'user_update' => false,
            'user_delete' => false,
        ]);

        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane.doe@teamspan.com',
            'password' => 'renzo973',
            'user_role' => 'Associate',
            'user_lob' => 'ERG FOLLOW-UP',
            'audit_create' => false,
            'audit_view' => true,
            'audit_update' => true,
            'audit_delete' => false,
            'pqc_create' => false,
            'pqc_view' => true,
            'pqc_update' => true,
            'pqc_delete' => false,
            'user_create' => false,
            'user_view' => false,
            'user_update' => false,
            'user_delete' => false,
        ]);
    }
}
