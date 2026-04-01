<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Payment;
use App\Models\User;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::where('branch_code', 'BR001')->first();
        if (!$branch) {
            return;
        }

        // Create a staff user for the sample case if missing
        $staff = User::firstOrCreate(
            ['email' => 'demo.staff@funeral.test'],
            [
                'name' => 'Demo Staff',
                'password' => Hash::make('Staff12345!'),
                'role' => 'staff',
                'branch_id' => $branch->id,
            ]
        );

        DB::table('user_branches')->updateOrInsert(
            ['user_id' => $staff->id, 'branch_id' => $branch->id],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $client = Client::firstOrCreate(
            ['full_name' => 'Juan Dela Cruz', 'branch_id' => $branch->id],
            [
                'relationship_to_deceased' => 'Son',
                'contact_number' => '09171234567',
                'address' => 'Quezon City',
            ]
        );

        $deceased = Deceased::firstOrCreate(
            ['full_name' => 'Maria Dela Cruz', 'branch_id' => $branch->id],
            [
                'client_id' => $client->id,
                'address' => 'Quezon City',
                'date_of_death' => now()->subDays(2)->toDateString(),
            ]
        );

        $case = FuneralCase::firstOrCreate(
            ['case_code' => 'CASE-DEMO-001', 'branch_id' => $branch->id],
            [
                'client_id' => $client->id,
                'deceased_id' => $deceased->id,
                'subtotal_amount' => 150000,
                'discount_type' => 'NONE',
                'discount_amount' => 0,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total_amount' => 150000,
                'total_paid' => 50000,
                'balance_amount' => 100000,
                'payment_status' => 'PARTIAL',
                'case_status' => 'ACTIVE',
                'reported_at' => now()->subDay(),
                'encoded_by' => $staff->id,
            ]
        );

        Payment::firstOrCreate(
            [
                'funeral_case_id' => $case->id,
                'amount' => 50000,
                'branch_id' => $branch->id,
                'receipt_number' => 'RCPT-' . now()->format('Y') . '-000001',
            ],
            [
                'method' => 'CASH',
                'balance_after_payment' => 100000,
                'payment_status_after_payment' => 'PARTIAL',
                'paid_date' => now()->toDateString(),
                'paid_at' => now()->subHours(2),
                'recorded_by' => $staff->id,
            ]
        );
    }
}

