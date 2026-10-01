<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NameGenerationConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_full_name_is_generated_from_normalized_parts(): void
    {
        $branch = $this->branch();

        $client = Client::create([
            'branch_id' => $branch->id,
            'first_name' => '  Maria   Clara ',
            'middle_name' => ' de   la ',
            'last_name' => ' Cruz ',
            'suffix' => '',
            'relationship_to_deceased' => 'Other',
        ]);

        $this->assertSame('Maria Clara de la Cruz', $client->fresh()->full_name);
        $this->assertNull($client->fresh()->suffix);

        $client->update([
            'first_name' => 'Maria',
            'middle_name' => null,
            'last_name' => 'Santos',
            'suffix' => 'Jr.',
        ]);

        $this->assertSame('Maria Santos Jr.', $client->fresh()->full_name);
    }

    public function test_deceased_full_name_is_generated_from_normalized_parts(): void
    {
        $branch = $this->branch();
        $client = Client::create([
            'branch_id' => $branch->id,
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'relationship_to_deceased' => 'Other',
        ]);

        $deceased = Deceased::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'first_name' => ' Juan ',
            'middle_name' => '  P. ',
            'last_name' => ' Dela   Cruz ',
            'suffix' => ' Sr. ',
        ]);

        $this->assertSame('Juan P. Dela Cruz Sr.', $deceased->fresh()->full_name);
    }

    public function test_user_name_is_generated_when_first_and_last_name_are_the_write_source(): void
    {
        $user = User::create([
            'first_name' => '  Jose ',
            'last_name' => ' Rizal ',
            'email' => 'jose@example.test',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->assertSame('Jose Rizal', $user->fresh()->name);
    }

    public function test_user_lowercase_name_parts_are_title_cased(): void
    {
        $user = User::create([
            'first_name' => '  josé ',
            'last_name' => ' niño-reyes ',
            'email' => 'lowercase.user@example.test',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $user->refresh();

        $this->assertSame('José', $user->first_name);
        $this->assertSame('Niño-Reyes', $user->last_name);
        $this->assertSame('José Niño-Reyes', $user->name);
    }

    public function test_user_mixed_case_and_uppercase_names_are_preserved(): void
    {
        $user = User::create([
            'first_name' => 'McDonald',
            'last_name' => 'De la Cruz',
            'email' => 'preserved.user@example.test',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $user->refresh();

        $this->assertSame('McDonald', $user->first_name);
        $this->assertSame('De la Cruz', $user->last_name);
        $this->assertSame('McDonald De la Cruz', $user->name);

        $uppercaseUser = User::create([
            'first_name' => 'JUAN',
            'last_name' => 'CRUZ',
            'email' => 'uppercase.user@example.test',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->assertSame('JUAN CRUZ', $uppercaseUser->fresh()->name);
    }

    public function test_user_partially_capitalized_multiword_names_are_completed(): void
    {
        $user = User::create([
            'first_name' => 'John kurt',
            'last_name' => 'Sdfsdf sdfsdfs',
            'email' => 'multiword.user@example.test',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $user->refresh();

        $this->assertSame('John Kurt', $user->first_name);
        $this->assertSame('Sdfsdf Sdfsdfs', $user->last_name);
        $this->assertSame('John Kurt Sdfsdf Sdfsdfs', $user->name);
    }

    public function test_user_lowercase_surname_particles_are_preserved_after_the_first_word(): void
    {
        $user = User::create([
            'first_name' => 'Juan',
            'last_name' => 'De la cruz',
            'email' => 'particle.user@example.test',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->assertSame('Juan De la Cruz', $user->fresh()->name);
    }

    public function test_user_apostrophe_name_is_title_cased(): void
    {
        $user = User::create([
            'first_name' => 'shaun',
            'last_name' => "o'connor",
            'email' => 'apostrophe.user@example.test',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->assertSame("Shaun O'Connor", $user->fresh()->name);
    }

    public function test_user_lowercase_initials_are_title_cased(): void
    {
        $user = User::create([
            'first_name' => 'j.p.',
            'last_name' => 'rizal',
            'email' => 'initials.user@example.test',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->assertSame('J.P. Rizal', $user->fresh()->name);
    }

    public function test_user_manual_name_flow_is_preserved_when_only_name_is_submitted(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Old',
            'last_name' => 'Parts',
            'name' => 'Old Parts',
            'is_active' => true,
        ]);

        $user->update(['name' => 'Updated Display Name']);

        $this->assertSame('Updated Display Name', $user->fresh()->name);
    }

    private function branch(): Branch
    {
        return Branch::create([
            'branch_code' => 'BR001',
            'branch_name' => 'Main Branch',
            'address' => 'Test Address',
            'is_active' => true,
        ]);
    }
}
