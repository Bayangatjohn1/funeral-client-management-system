<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\FuneralCase;
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
            'added_from' => 'nullable|date',
            'added_to' => 'nullable|date|after_or_equal:added_from',
            'type_filter' => 'nullable|in:all,needs_attention,recent,with_balance',
            'date_range' => 'nullable|in:any,today,7d,30d,this_month,custom',
            'sort' => 'nullable|in:newest,oldest,name_asc,name_desc',
        ], [
            'q.regex' => 'Search may contain letters, spaces, apostrophes, periods, and hyphens only.',
            'added_to.after_or_equal' => 'Date Added (to) must be on or after Date Added (from).',
        ]);

        $query = Client::query()
            ->select([
                'id',
                'branch_id',
                'full_name',
                'relationship_to_deceased',
                'contact_number',
                'address',
                'created_at',
            ])
            ->where('branch_id', $mainBranchId)
            ->addSelect([
                'latest_deceased_name' => FuneralCase::query()
                    ->withoutGlobalScopes()
                    ->from('funeral_cases as fc')
                    ->join('deceased as d', 'd.id', '=', 'fc.deceased_id')
                    ->whereColumn('fc.client_id', 'clients.id')
                    ->where('fc.branch_id', $mainBranchId)
                    ->orderByDesc('fc.created_at')
                    ->select('d.full_name')
                    ->limit(1),
                'latest_service_package' => FuneralCase::query()
                    ->withoutGlobalScopes()
                    ->from('funeral_cases as fc')
                    ->whereColumn('fc.client_id', 'clients.id')
                    ->where('fc.branch_id', $mainBranchId)
                    ->orderByDesc('fc.created_at')
                    ->select('fc.service_package')
                    ->limit(1),
                'latest_case_status' => FuneralCase::query()
                    ->withoutGlobalScopes()
                    ->from('funeral_cases as fc')
                    ->whereColumn('fc.client_id', 'clients.id')
                    ->where('fc.branch_id', $mainBranchId)
                    ->orderByDesc('fc.created_at')
                    ->select('fc.case_status')
                    ->limit(1),
                'latest_payment_status' => FuneralCase::query()
                    ->withoutGlobalScopes()
                    ->from('funeral_cases as fc')
                    ->whereColumn('fc.client_id', 'clients.id')
                    ->where('fc.branch_id', $mainBranchId)
                    ->orderByDesc('fc.created_at')
                    ->select('fc.payment_status')
                    ->limit(1),
            ]);

        // Optional search
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where('full_name', 'like', "%{$q}%");
        }

        $dateRange = $request->string('date_range', 'any')->toString();
        $usesCustomDate = $dateRange === 'custom'
            || (!$request->filled('date_range') && ($request->filled('added_from') || $request->filled('added_to')));

        if ($usesCustomDate) {
            if ($request->filled('added_from')) {
                $query->whereDate('created_at', '>=', $request->string('added_from')->toString());
            }
            if ($request->filled('added_to')) {
                $query->whereDate('created_at', '<=', $request->string('added_to')->toString());
            }
        } elseif ($dateRange !== 'any') {
            $today = now()->startOfDay();
            if ($dateRange === 'today') {
                $query->whereDate('created_at', $today->toDateString());
            } elseif ($dateRange === '7d') {
                $query->where('created_at', '>=', now()->subDays(7)->startOfDay());
            } elseif ($dateRange === '30d') {
                $query->where('created_at', '>=', now()->subDays(30)->startOfDay());
            } elseif ($dateRange === 'this_month') {
                $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
            }
        }

        $typeFilter = $request->string('type_filter', 'all')->toString();
        if ($typeFilter === 'needs_attention' || $typeFilter === 'with_balance') {
            $query->whereHas('funeralCases', function ($caseQuery) {
                $caseQuery->whereIn('payment_status', ['UNPAID', 'PARTIAL']);
            });
        } elseif ($typeFilter === 'recent') {
            $query->where('created_at', '>=', now()->subDays(30)->startOfDay());
        }

        $sort = $request->string('sort', 'newest')->toString();
        if ($sort === 'oldest') {
            $query->orderBy('created_at');
        } elseif ($sort === 'name_asc') {
            $query->orderBy('full_name');
        } elseif ($sort === 'name_desc') {
            $query->orderByDesc('full_name');
        } else {
            $query->orderByDesc('created_at');
        }

        $clients = $query->paginate(20)->withQueryString();

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
        if (auth()->user()?->role === 'staff') {
            return redirect()->route('clients.index')->with('warning', 'Need permission from the admin.');
        }

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
        if (auth()->user()?->role === 'staff') {
            return redirect()->route('clients.index')->with('warning', 'Need permission from the admin.');
        }

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
        if (!$user || !$user->branch_id) {
            abort(403);
        }

        return (int) $user->branch_id;
    }
}
