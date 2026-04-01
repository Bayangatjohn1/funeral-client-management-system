<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasAny = DB::table('packages')->exists();
        if ($hasAny) {
            return;
        }

        DB::table('packages')->insert([
            [
                'name' => 'First Class',
                'coffin_type' => 'Premium Coffin',
                'price' => 250000.00,
                'inclusions' => 'Full service package.',
                'freebies' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Second Class',
                'coffin_type' => 'Standard Coffin',
                'price' => 50000.00,
                'inclusions' => 'Standard service package.',
                'freebies' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('packages')
            ->whereIn('name', ['First Class', 'Second Class'])
            ->delete();
    }
};
