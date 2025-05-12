<?php

// database/seeders/UserSeeder.php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create super admin user
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@system.com',
            'password' => Hash::make('password'),
            'tenant_id' => null,
            'role' => 'super_admin',
            'status' => true,
        ]);

        // Create tenant admin for Example Company
        // User::create([
        //     'name' => 'Example Admin',
        //     'email' => 'admin@example.com',
        //     'password' => Hash::make('password'),
        //     'tenant_id' => 1,
        //     'role' => 'tenant_admin',
        //     'status' => true,
        // ]);

        // // Create tenant admin for Test Organization
        // User::create([
        //     'name' => 'Test Admin',
        //     'email' => 'admin@test.org',
        //     'password' => Hash::make('password'),
        //     'tenant_id' => 2,
        //     'role' => 'tenant_admin',
        //     'status' => true,
        // ]);

        // // Create agent users for Example Company
        // User::create([
        //     'name' => 'Agent User 1',
        //     'email' => 'agent1@example.com',
        //     'password' => Hash::make('password'),
        //     'tenant_id' => 1,
        //     'role' => 'agent',
        //     'related_id' => 1, // Agent ID 1
        //     'status' => true,
        // ]);

        // User::create([
        //     'name' => 'Agent User 2',
        //     'email' => 'agent2@example.com',
        //     'password' => Hash::make('password'),
        //     'tenant_id' => 1,
        //     'role' => 'agent',
        //     'related_id' => 2, // Agent ID 2
        //     'status' => true,
        // ]);

        // // Create provider users for Example Company
        // User::create([
        //     'name' => 'Provider User 1',
        //     'email' => 'provider1@example.com',
        //     'password' => Hash::make('password'),
        //     'tenant_id' => 1,
        //     'role' => 'provider',
        //     'related_id' => 1, // Provider ID 1
        //     'status' => true,
        // ]);

        // User::create([
        //     'name' => 'Provider User 2',
        //     'email' => 'provider2@example.com',
        //     'password' => Hash::make('password'),
        //     'tenant_id' => 1,
        //     'role' => 'provider',
        //     'related_id' => 2, // Provider ID 2
        //     'status' => true,
        // ]);
    }
}
