<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['branch_code' => 'BR001', 'branch_type' => 'main', 'branch_name' => 'Main Branch', 'address' => null, 'is_active' => true],
            ['branch_code' => 'BR002', 'branch_type' => 'branch', 'branch_name' => 'Branch 2', 'address' => null, 'is_active' => true],
            ['branch_code' => 'BR003', 'branch_type' => 'branch', 'branch_name' => 'Branch 3', 'address' => null, 'is_active' => true],
        ];

        foreach ($branches as $b) {
            Branch::updateOrCreate(
                ['branch_code' => $b['branch_code']],
                [
                    'branch_type' => $b['branch_type'],
                    'branch_name' => $b['branch_name'],
                    'address' => $b['address'],
                    'is_active' => $b['is_active'],
                ]
            );
        }
    }
}
