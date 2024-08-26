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
            'password' => 'renzo973'
        ]);

        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@teamspan.com',
            'password' => 'renzo973'
        ]);

        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane.doe@teamspan.com',
            'password' => 'renzo973'
        ]);
    }
}
