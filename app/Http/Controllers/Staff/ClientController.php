<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Support\AuditLogger;
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
            ->with([
                'latestFuneralCase' => fn ($q) => $q->select([
                    'funeral_cases.id',
                    'funeral_cases.client_id',
                    'funeral_cases.deceased_id',
                    'funeral_cases.service_package',
                    'funeral_cases.case_status',
                    'funeral_cases.payment_status',
                    'funeral_cases.created_at',
                ]),
                'latestFuneralCase.deceased:id,full_name',
            ])
            ->where('branch_id', $mainBranchId);

        // Optional search
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($nameQuery) use ($q) {
                $nameQuery->where('full_name', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('middle_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%");
            });
        }

        $dateRange = $request->string('date_range', 'any')->toString();
        $usesCustomDate = $dateRange === 'custom'
            || (!$request->filled('date_range') && ($request->filled('added_from') || $request->filled('added_to')));

        if ($usesCustomDate) {
            [$dateStart, $dateEnd] = $this->parseDateBounds(
                $request->filled('added_from') ? $request->string('added_from')->toString() : null,
                $request->filled('added_to') ? $request->string('added_to')->toString() : null,
            );
            if ($dateStart) {
                $query->where('created_at', '>=', $dateStart);
            }
            if ($dateEnd) {
                $query->where('created_at', '<=', $dateEnd);
            }
        } elseif ($dateRange !== 'any') {
            $today = now()->startOfDay();
            if ($dateRange === 'today') {
                $query->whereBetween('created_at', [$today, $today->copy()->endOfDay()]);
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
            'first_name' => FieldRules::namePart(min: 2),
            'last_name'  => FieldRules::namePart(min: 2),
            'middle_name' => FieldRules::namePart(false),
            'suffix'      => FieldRules::namePart(false),
            'contact_number' => FieldRules::contactNumber(),
            'address' => 'nullable|string|max:255',
        ], [
            'first_name.regex'  => FieldRules::nameRegexMessage('First name'),
            'last_name.regex'   => FieldRules::nameRegexMessage('Last name'),
            'middle_name.regex' => FieldRules::nameRegexMessage('Middle name'),
            'contact_number.regex' => 'Contact number format is invalid.',
        ]);
        $validated = $this->normalizeValidatedNameParts($validated);
        if ($response = $this->rejectDuplicateNameParts($validated)) {
            return $response;
        }

        $fullName = Client::buildFullName(
            $validated['first_name'],
            $validated['middle_name'] ?? null,
            $validated['last_name'],
            $validated['suffix'] ?? null,
        );

        $duplicateClient = Client::query()
            ->where('branch_id', $mainBranchId)
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower(trim($fullName))])
            ->whereRaw('COALESCE(contact_number, "") = ?', [trim((string) ($validated['contact_number'] ?? ''))])
            ->first();

        if ($duplicateClient) {
            return back()->withErrors([
                'first_name' => 'Client record already exists in this branch (same name and contact number).',
            ])->withInput();
        }

        $client = Client::create([
            'branch_id'  => $mainBranchId,
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'suffix'      => $validated['suffix'] ?? null,
            'relationship_to_deceased' => 'Other',
            'contact_number' => $validated['contact_number'] ?? null,
            'valid_id_type' => 'Legacy Record',
            'valid_id_number' => 'LEGACY-' . strtoupper((string) \Illuminate\Support\Str::ulid()),
            'address' => $validated['address'] ?? null,
        ]);

        AuditLogger::log(
            action: 'client.created',
            actionType: 'create',
            entityType: 'client',
            entityId: $client->id,
            metadata: [
                'full_name' => $client->full_name,
                'contact_number' => $client->contact_number,
                'branch_id' => $client->branch_id,
            ],
            branchId: $client->branch_id
        );

        return redirect()->route('clients.index')->with('success', 'Client added successfully.');
    }

    public function edit(Client $client)
    {
        if (auth()->user()?->role === 'staff') {
            return redirect()->route('clients.index')->with('warning', 'Need permission from the admin.');
        }

        $this->ensureClientBranchAccessible($client);

        if ($this->wantsModalFragment(request())) {
            return view('staff.clients.partials.edit-form', compact('client'));
        }

        return view('staff.clients.edit', compact('client'));
    }

    public function show(Client $client)
    {
        $this->ensureClientBranchAccessible($client);

        $client->load([
            'deceaseds:id,client_id,full_name,address,born,age,died,date_of_death,interment,place_of_cemetery,created_at',
            'funeralCases' => function ($query) {
                $query->select([
                    'id',
                    'client_id',
                    'deceased_id',
                    'case_code',
                    'service_package',
                    'total_amount',
                    'payment_status',
                    'paid_at',
                    'case_status',
                    'created_at',
                ])->latest('created_at');
            },
            'funeralCases.deceased:id,full_name',
            'funeralCases.payments:id,funeral_case_id,method,amount,paid_at,paid_date',
        ]);

        if ($this->wantsModalFragment(request())) {
            return view('staff.clients.partials.show-content', compact('client'));
        }

        return view('staff.clients.show', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        if (auth()->user()?->role === 'staff') {
            return redirect()->route('clients.index')->with('warning', 'Need permission from the admin.');
        }

        $this->ensureClientBranchAccessible($client);

        $validated = $request->validate([
            'first_name'  => FieldRules::namePart(min: 2),
            'last_name'   => FieldRules::namePart(min: 2),
            'middle_name' => FieldRules::namePart(false),
            'suffix'      => FieldRules::namePart(false),
            'contact_number' => FieldRules::contactNumber(),
            'address' => 'nullable|string|max:255',
        ], [
            'first_name.regex'  => FieldRules::nameRegexMessage('First name'),
            'last_name.regex'   => FieldRules::nameRegexMessage('Last name'),
            'middle_name.regex' => FieldRules::nameRegexMessage('Middle name'),
            'contact_number.regex' => 'Contact number format is invalid.',
        ]);
        $validated = $this->normalizeValidatedNameParts($validated);
        if ($response = $this->rejectDuplicateNameParts($validated)) {
            return $response;
        }

        $fullName = Client::buildFullName(
            $validated['first_name'],
            $validated['middle_name'] ?? null,
            $validated['last_name'],
            $validated['suffix'] ?? null,
        );

        $duplicateClient = Client::query()
            ->where('branch_id', $client->branch_id)
            ->whereKeyNot($client->id)
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower(trim($fullName))])
            ->whereRaw('COALESCE(contact_number, "") = ?', [trim((string) ($validated['contact_number'] ?? ''))])
            ->first();

        if ($duplicateClient) {
            return back()->withErrors([
                'first_name' => 'Another client with the same name and contact number already exists.',
            ])->withInput();
        }

        $before = [
            'full_name' => $client->full_name,
            'contact_number' => $client->contact_number,
            'address' => $client->address,
        ];

        $client->update([
            'first_name'  => $validated['first_name'],
            'last_name'   => $validated['last_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'suffix'      => $validated['suffix'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'address'     => $validated['address'] ?? null,
        ]);

        AuditLogger::log(
            action: 'client.updated',
            actionType: 'update',
            entityType: 'client',
            entityId: $client->id,
            metadata: [
                'before' => $before,
                'after' => [
                    'full_name' => $client->full_name,
                    'contact_number' => $client->contact_number,
                    'address' => $client->address,
                ],
            ],
            branchId: $client->branch_id
        );

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $this->ensureClientBranchAccessible($client);

        if ($client->deceaseds()->exists() || $client->funeralCases()->exists()) {
            return back()->withErrors([
                'client' => 'This client has linked deceased or case records and cannot be deleted.',
            ]);
        }

        $clientId = $client->id;
        $clientName = $client->full_name;
        $clientBranchId = $client->branch_id;

        $client->delete();

        AuditLogger::log(
            action: 'client.deleted',
            actionType: 'delete',
            entityType: 'client',
            entityId: $clientId,
            metadata: [
                'full_name' => $clientName,
                'branch_id' => $clientBranchId,
            ],
            branchId: $clientBranchId
        );

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

    private function ensureClientBranchAccessible(Client $client): void
    {
        $user = auth()->user();
        $allowedBranchIds = method_exists($user, 'branchScopeIds')
            ? array_map('intval', $user->branchScopeIds())
            : [(int) ($user?->branch_id ?? 0)];

        if (!in_array((int) $client->branch_id, $allowedBranchIds, true)) {
            abort(403);
        }
    }

    private function wantsModalFragment(Request $request): bool
    {
        return $request->query('fragment') === 'modal'
            || $request->headers->get('X-Client-Fragment') === 'modal';
    }

    private function normalizeValidatedNameParts(array $validated): array
    {
        return array_replace($validated, Client::normalizedNameParts($validated));
    }

    private function rejectDuplicateNameParts(array $validated): ?\Illuminate\Http\RedirectResponse
    {
        $parts = array_filter([
            'first_name' => $validated['first_name'] ?? null,
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'suffix' => $validated['suffix'] ?? null,
        ], static fn (?string $value): bool => $value !== null);

        $seen = [];
        foreach ($parts as $field => $value) {
            $key = mb_strtolower($value);
            if (isset($seen[$key])) {
                return back()->withErrors([
                    $field => 'Name parts must not repeat exactly.',
                ])->withInput();
            }
            $seen[$key] = true;
        }

        return null;
    }
}
