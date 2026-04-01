<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $main = Branch::where('branch_code', 'BR001')->first();

        // OWNER (read-only)
        User::updateOrCreate(
            ['email' => 'owner@funeral.test'],
            [
                'name' => 'Owner',
                'password' => Hash::make('Owner12345!'),
                'role' => 'owner',
                'branch_id' => null,
            ]
        );

        // ADMIN (main branch)
        User::updateOrCreate(
            ['email' => 'admin@funeral.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Admin12345!'),
                'role' => 'admin',
                'branch_id' => $main?->id,
            ]
        );

        // STAFF (main branch) - optional
        $staff = User::updateOrCreate(
            ['email' => 'staff@funeral.test'],
            [
                'name' => 'Staff',
                'password' => Hash::make('Staff12345!'),
                'role' => 'staff',
                'branch_id' => $main?->id,
            ]
        );

        // Ensure branch access pivot exists for staff/admin
        $admin = User::where('email', 'admin@funeral.test')->first();
        foreach ([$admin, $staff] as $user) {
            if ($user && $main) {
                DB::table('user_branches')->updateOrInsert(
                    ['user_id' => $user->id, 'branch_id' => $main->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}
