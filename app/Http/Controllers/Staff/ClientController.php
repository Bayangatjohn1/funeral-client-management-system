<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Support\Validation\FieldRules;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Client::class, 'client');
    }

    public function index(Request $request)
    {
        $mainBranchId = $this->mainBranchIdForDirectory();

        $request->validate([
            'q' => FieldRules::searchName(),
        ], [
            'q.regex' => 'Search may contain letters, spaces, apostrophes, periods, and hyphens only.',
        ]);

        $query = Client::with(['branch', 'funeralCases.payments'])
            ->where('branch_id', $mainBranchId)
            ->latest();

        // Optional search
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where('full_name', 'like', "%{$q}%");
        }

        $clients = $query->get();

        return view('staff.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('staff.clients.create');
    }

    public function store(Request $request)
    {
        $mainBranchId = $this->mainBranchIdForDirectory();

        $validated = $request->validate([
            'full_name' => FieldRules::personName(),
            'contact_number' => FieldRules::contactNumber(),
            'address' => 'nullable|string|max:255',
        ], [
            'full_name.regex' => 'Full name may contain letters, spaces, apostrophes, periods, and hyphens only.',
            'contact_number.regex' => 'Contact number format is invalid.',
        ]);

        $duplicateClient = Client::query()
            ->where('branch_id', $mainBranchId)
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower(trim($validated['full_name']))])
            ->whereRaw('COALESCE(contact_number, "") = ?', [trim((string) ($validated['contact_number'] ?? ''))])
            ->first();

        if ($duplicateClient) {
            return back()->withErrors([
                'full_name' => 'Client record already exists in this branch (same name and contact number).',
            ])->withInput();
        }

        Client::create([
            'branch_id' => $mainBranchId,
            'full_name' => $validated['full_name'],
            'relationship_to_deceased' => 'Other',
            'contact_number' => $validated['contact_number'] ?? null,
            'valid_id_type' => 'Legacy Record',
            'valid_id_number' => 'LEGACY-' . strtoupper((string) \Illuminate\Support\Str::ulid()),
            'address' => $validated['address'] ?? null,
        ]);

        return redirect()->route('clients.index')->with('success', 'Client added successfully.');
    }

    public function edit(Client $client)
    {
        if ((int) $client->branch_id !== $this->mainBranchIdForDirectory()) {
            abort(403);
        }

        return view('staff.clients.edit', compact('client'));
    }

    public function show(Client $client)
    {
        if ((int) $client->branch_id !== $this->mainBranchIdForDirectory()) {
            abort(403);
        }

        $client->load([
            'deceaseds',
            'funeralCases.deceased',
            'funeralCases.payments',
        ]);

        return view('staff.clients.show', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        if ((int) $client->branch_id !== $this->mainBranchIdForDirectory()) {
            abort(403);
        }

        $validated = $request->validate([
            'full_name' => FieldRules::personName(),
            'contact_number' => FieldRules::contactNumber(),
            'address' => 'nullable|string|max:255',
        ], [
            'full_name.regex' => 'Full name may contain letters, spaces, apostrophes, periods, and hyphens only.',
            'contact_number.regex' => 'Contact number format is invalid.',
        ]);

        $duplicateClient = Client::query()
            ->where('branch_id', $client->branch_id)
            ->whereKeyNot($client->id)
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower(trim($validated['full_name']))])
            ->whereRaw('COALESCE(contact_number, "") = ?', [trim((string) ($validated['contact_number'] ?? ''))])
            ->first();

        if ($duplicateClient) {
            return back()->withErrors([
                'full_name' => 'Another client with the same name and contact number already exists.',
            ])->withInput();
        }

        $client->update($validated);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        if ((int) $client->branch_id !== $this->mainBranchIdForDirectory()) {
            abort(403);
        }

        if ($client->deceaseds()->exists() || $client->funeralCases()->exists()) {
            return back()->withErrors([
                'client' => 'This client has linked deceased or case records and cannot be deleted.',
            ]);
        }

        $client->delete();

        return back()->with('success', 'Client deleted.');
    }

    private function mainBranchIdForDirectory(): int
    {
        $user = auth()->user();
        if (!$user || !$user->canEncodeAnyBranch()) {
            abort(403);
        }

        $mainBranchId = (int) Branch::where('is_active', true)
            ->where('branch_code', 'BR001')
            ->value('id');

        if ($mainBranchId <= 0) {
            abort(500, 'Main branch (BR001) is not configured.');
        }

        return $mainBranchId;
    }
}
