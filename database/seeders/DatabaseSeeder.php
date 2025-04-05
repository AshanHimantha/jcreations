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
        // Create an admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'roles' => ['admin'],
        ]);

        // Create a cashier user
        User::factory()->create([
            'name' => 'Cashier User',
            'email' => 'cashier@example.com',
            'roles' => ['cashier'],
        ]);

        // Create a staff user
        User::factory()->create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'roles' => ['staff'],
        ]);

        // Create user with multiple roles (admin and staff)
        User::factory()->create([
            'name' => 'Admin Staff User',
            'email' => 'adminstaff@example.com',
            'roles' => ['admin', 'staff'],
        ]);

        // Create user with all roles
        User::factory()->create([
            'name' => 'Super User',
            'email' => 'super@example.com',
            'roles' => ['admin', 'staff', 'cashier'],
        ]);

        // Create test user with staff role
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'roles' => ['staff'],
        ]);

        // Create additional random users with various roles
        User::factory(1)->create([
            'roles' => ['cashier'],
        ]);
        
        User::factory(1)->create([
            'roles' => ['staff'],
        ]);
        
        User::factory(1)->create([
            'roles' => ['staff', 'cashier'],
        ]);
    }
}