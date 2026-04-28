<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Support\AuditLogger;
use App\Support\Validation\FieldRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeceasedController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Deceased::class, 'deceased');
    }

    public function index(Request $request)
    {
        $mainBranchId = $this->mainBranchIdForDirectory();

        $request->validate([
            'q' => FieldRules::searchName(),
            'died_from' => 'nullable|date',
            'died_to' => 'nullable|date|after_or_equal:died_from',
            'interment_from' => 'nullable|date',
            'interment_to' => 'nullable|date|after_or_equal:interment_from',
            'type_filter' => 'nullable|in:all,active,completed,with_balance,needs_attention,recent',
            'date_range' => 'nullable|in:any,today,7d,30d,this_month,custom',
            'sort' => 'nullable|in:newest,oldest,name_asc,name_desc,death_recent,death_oldest',
        ], [
            'q.regex' => 'Search may contain letters, spaces, apostrophes, periods, and hyphens only.',
            'died_to.after_or_equal' => 'Date of death (to) must be on or after date of death (from).',
            'interment_to.after_or_equal' => 'Interment date (to) must be on or after interment date (from).',
        ]);

        $query = Deceased::query()
            ->select([
                'id',
                'branch_id',
                'client_id',
                'full_name',
                'age',
                'died',
                'date_of_death',
                'interment',
                'interment_at',
                'created_at',
            ])
            ->with([
                'client:id,full_name',
                'funeralCase:id,deceased_id,case_code,case_status,payment_status',
            ])
            ->where('branch_id', $mainBranchId);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where('full_name', 'like', "%{$q}%");
        }

        $dateRange = $request->string('date_range', 'any')->toString();
        $usesCustomDate = $dateRange === 'custom'
            || (!$request->filled('date_range') && ($request->filled('died_from') || $request->filled('died_to')));

        if ($usesCustomDate && $request->filled('died_from')) {
            $from = $request->string('died_from')->toString();
            $query->where(function ($diedQuery) use ($from) {
                $diedQuery->whereDate('died', '>=', $from)
                    ->orWhere(function ($legacyQuery) use ($from) {
                        $legacyQuery->whereNull('died')
                            ->whereDate('date_of_death', '>=', $from);
                    });
            });
        }

        if ($usesCustomDate && $request->filled('died_to')) {
            $to = $request->string('died_to')->toString();
            $query->where(function ($diedQuery) use ($to) {
                $diedQuery->whereDate('died', '<=', $to)
                    ->orWhere(function ($legacyQuery) use ($to) {
                        $legacyQuery->whereNull('died')
                            ->whereDate('date_of_death', '<=', $to);
                    });
            });
        }

        if ($request->filled('interment_from')) {
            $from = $request->string('interment_from')->toString();
            $query->where(function ($intermentQuery) use ($from) {
                $intermentQuery->whereDate('interment_at', '>=', $from)
                    ->orWhere(function ($legacyQuery) use ($from) {
                        $legacyQuery->whereNull('interment_at')
                            ->whereDate('interment', '>=', $from);
                    });
            });
        }

        if ($request->filled('interment_to')) {
            $to = $request->string('interment_to')->toString();
            $query->where(function ($intermentQuery) use ($to) {
                $intermentQuery->whereDate('interment_at', '<=', $to)
                    ->orWhere(function ($legacyQuery) use ($to) {
                        $legacyQuery->whereNull('interment_at')
                            ->whereDate('interment', '<=', $to);
                    });
            });
        }

        if (!$usesCustomDate && $dateRange !== 'any') {
            if ($dateRange === 'today') {
                $date = now()->toDateString();
                $query->where(function ($dateQuery) use ($date) {
                    $dateQuery->whereDate('died', $date)
                        ->orWhere(function ($legacyQuery) use ($date) {
                            $legacyQuery->whereNull('died')->whereDate('date_of_death', $date);
                        });
                });
            } elseif ($dateRange === '7d') {
                $from = now()->subDays(7)->toDateString();
                $query->where(function ($dateQuery) use ($from) {
                    $dateQuery->whereDate('died', '>=', $from)
                        ->orWhere(function ($legacyQuery) use ($from) {
                            $legacyQuery->whereNull('died')->whereDate('date_of_death', '>=', $from);
                        });
                });
            } elseif ($dateRange === '30d') {
                $from = now()->subDays(30)->toDateString();
                $query->where(function ($dateQuery) use ($from) {
                    $dateQuery->whereDate('died', '>=', $from)
                        ->orWhere(function ($legacyQuery) use ($from) {
                            $legacyQuery->whereNull('died')->whereDate('date_of_death', '>=', $from);
                        });
                });
            } elseif ($dateRange === 'this_month') {
                $from = now()->startOfMonth()->toDateString();
                $to = now()->endOfMonth()->toDateString();
                $query->where(function ($dateQuery) use ($from, $to) {
                    $dateQuery->whereBetween(DB::raw('DATE(died)'), [$from, $to])
                        ->orWhere(function ($legacyQuery) use ($from, $to) {
                            $legacyQuery->whereNull('died')->whereBetween(DB::raw('DATE(date_of_death)'), [$from, $to]);
                        });
                });
            }
        }

        $typeFilter = $request->string('type_filter', 'all')->toString();
        if ($typeFilter === 'active') {
            $query->whereHas('funeralCase', fn($caseQuery) => $caseQuery->where('case_status', 'ACTIVE'));
        } elseif ($typeFilter === 'completed') {
            $query->whereHas('funeralCase', fn($caseQuery) => $caseQuery->where('case_status', 'COMPLETED'));
        } elseif ($typeFilter === 'with_balance' || $typeFilter === 'needs_attention') {
            $query->whereHas('funeralCase', fn($caseQuery) => $caseQuery->whereIn('payment_status', ['UNPAID', 'PARTIAL']));
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
        } elseif ($sort === 'death_recent') {
            $query->orderByRaw('COALESCE(died, date_of_death) DESC');
        } elseif ($sort === 'death_oldest') {
            $query->orderByRaw('COALESCE(died, date_of_death) ASC');
        } else {
            $query->orderByDesc('created_at');
        }

        $deceaseds = $query->paginate(20)->withQueryString();

        return view('staff.deceased.index', compact('deceaseds'));
    }

    public function create()
    {
        $clients = Client::where('branch_id', $this->mainBranchIdForDirectory())
            ->orderBy('full_name')
            ->get();

        return view('staff.deceased.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $mainBranchId = $this->mainBranchIdForDirectory();

        $validated = $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'address'     => 'nullable|string|max:255',
            'first_name'  => FieldRules::namePart(),
            'last_name'   => FieldRules::namePart(),
            'middle_name' => FieldRules::namePart(false),
            'suffix'      => FieldRules::namePart(false),
            'born' => 'nullable|date',
            'died' => 'nullable|date|after_or_equal:born|before_or_equal:today|required_with:interment_at',
            'age' => 'nullable|integer|min:0|max:150',
            'interment_at' => 'nullable|date|after:died|before_or_equal:today',
            'wake_days' => 'nullable|integer|min:1|max:30',
            'place_of_cemetery' => 'nullable|string|max:255',
            'coffin_length_cm' => 'nullable|numeric|min:30|max:300',
            'coffin_size' => 'nullable|in:SMALL,MEDIUM,LARGE,XL,CUSTOM',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
        ], [
            'first_name.regex'  => FieldRules::nameRegexMessage('First name'),
            'last_name.regex'   => FieldRules::nameRegexMessage('Last name'),
            'middle_name.regex' => FieldRules::nameRegexMessage('Middle name'),
            'died.after_or_equal' => 'Died date must be on or after born date.',
            'died.required_with' => 'Date of death is required when interment datetime is set.',
            'died.before_or_equal' => 'Date of death cannot be in the future.',
            'interment_at.after' => 'Interment date/time must be after the date of death.',
            'interment_at.before_or_equal' => 'Interment date cannot be in the future.',
        ]);

        if (!$this->isIntermentAfterDeathDate($validated['died'] ?? null, $validated['interment_at'] ?? null)) {
            return back()->withErrors([
                'interment_at' => 'Interment date must be after the date of death.',
            ])->withInput();
        }

        $client = Client::find($validated['client_id']);
        if (!$client || (int) $client->branch_id !== $mainBranchId) {
            abort(403);
        }

        $deceasedFullName = implode(' ', array_filter([
            $validated['first_name'],
            $validated['middle_name'] ?? null,
            $validated['last_name'],
            $validated['suffix'] ?? null,
        ]));

        $duplicateDeceased = Deceased::query()
            ->where('branch_id', $mainBranchId)
            ->where('client_id', $validated['client_id'])
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower(trim($deceasedFullName))]);

        if (!empty($validated['died'])) {
            $duplicateDeceased->whereDate('died', $validated['died']);
        } else {
            $duplicateDeceased->whereNull('died')->whereNull('date_of_death');
        }

        if ($duplicateDeceased->exists()) {
            return back()->withErrors([
                'first_name' => 'Deceased record already exists for this client (same name and date of death).',
            ])->withInput();
        }

        $wakeDays = $this->resolveWakeDays(
            $validated['wake_days'] ?? null,
            $validated['died'] ?? null,
            $validated['interment_at'] ?? null
        );
        $resolvedAge = $this->resolveAge(
            $validated['born'] ?? null,
            $validated['died'] ?? null,
            $validated['age'] ?? null
        );

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('deceased-photos', 'public');
        }

        $intermentAt = !empty($validated['interment_at'])
            ? \Carbon\Carbon::parse($validated['interment_at'])
            : null;

        $deceased = Deceased::create([
            'branch_id'   => $mainBranchId,
            'client_id'   => $validated['client_id'],
            'address'     => $validated['address'] ?? null,
            'first_name'  => $validated['first_name'],
            'last_name'   => $validated['last_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'suffix'      => $validated['suffix'] ?? null,
            'born' => $validated['born'] ?? null,
            'died' => $validated['died'] ?? null,
            'date_of_death' => $validated['died'] ?? null,
            'age' => $resolvedAge,
            'interment' => $intermentAt?->toDateString(),
            'interment_at' => $intermentAt,
            'wake_days' => $wakeDays,
            'place_of_cemetery' => $validated['place_of_cemetery'] ?? null,
            'coffin_length_cm' => $validated['coffin_length_cm'] ?? null,
            'coffin_size' => $validated['coffin_size'] ?? null,
            'photo_path' => $photoPath,
        ]);

        AuditLogger::log(
            action: 'deceased.created',
            actionType: 'create',
            entityType: 'deceased',
            entityId: $deceased->id,
            metadata: [
                'full_name' => $deceased->full_name,
                'died' => $deceased->died,
                'client_id' => $deceased->client_id,
                'branch_id' => $deceased->branch_id,
            ],
            branchId: $deceased->branch_id
        );

        return redirect()->route('deceased.index')->with('success', 'Deceased record added successfully.');
    }

    public function edit(Deceased $deceased)
    {
        if (auth()->user()?->role === 'staff') {
            return redirect()->route('deceased.index')->with('warning', 'Need permission from the admin.');
        }

        $mainBranchId = $this->mainBranchIdForDirectory();
        if ((int) $deceased->branch_id !== $mainBranchId) {
            abort(403);
        }

        $clients = Client::where('branch_id', $mainBranchId)
            ->orderBy('full_name')
            ->get();

        return view('staff.deceased.edit', compact('deceased', 'clients'));
    }

    public function show(Deceased $deceased)
    {
        $mainBranchId = $this->mainBranchIdForDirectory();
        if ((int) $deceased->branch_id !== $mainBranchId) {
            abort(403);
        }

        $deceased->load(['client', 'branch', 'funeralCase']);

        return view('staff.deceased.show', compact('deceased'));
    }

    public function update(Request $request, Deceased $deceased)
    {
        if (auth()->user()?->role === 'staff') {
            return redirect()->route('deceased.index')->with('warning', 'Need permission from the admin.');
        }

        $mainBranchId = $this->mainBranchIdForDirectory();
        if ((int) $deceased->branch_id !== $mainBranchId) {
            abort(403);
        }

        $validated = $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'address'     => 'nullable|string|max:255',
            'first_name'  => FieldRules::namePart(),
            'last_name'   => FieldRules::namePart(),
            'middle_name' => FieldRules::namePart(false),
            'suffix'      => FieldRules::namePart(false),
            'born' => 'nullable|date',
            'died' => 'nullable|date|after_or_equal:born|before_or_equal:today|required_with:interment_at',
            'age' => 'nullable|integer|min:0|max:150',
            'interment_at' => 'nullable|date|after:died|before_or_equal:today',
            'wake_days' => 'nullable|integer|min:1|max:30',
            'place_of_cemetery' => 'nullable|string|max:255',
            'coffin_length_cm' => 'nullable|numeric|min:30|max:300',
            'coffin_size' => 'nullable|in:SMALL,MEDIUM,LARGE,XL,CUSTOM',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'remove_photo' => 'nullable|boolean',
        ], [
            'first_name.regex'  => FieldRules::nameRegexMessage('First name'),
            'last_name.regex'   => FieldRules::nameRegexMessage('Last name'),
            'middle_name.regex' => FieldRules::nameRegexMessage('Middle name'),
            'died.after_or_equal' => 'Died date must be on or after born date.',
            'died.required_with' => 'Date of death is required when interment datetime is set.',
            'died.before_or_equal' => 'Date of death cannot be in the future.',
            'interment_at.after' => 'Interment date/time must be after the date of death.',
            'interment_at.before_or_equal' => 'Interment date cannot be in the future.',
        ]);

        if (!$this->isIntermentAfterDeathDate($validated['died'] ?? null, $validated['interment_at'] ?? null)) {
            return back()->withErrors([
                'interment_at' => 'Interment date must be after the date of death.',
            ])->withInput();
        }

        $client = Client::find($validated['client_id']);
        if (!$client || (int) $client->branch_id !== $mainBranchId) {
            abort(403);
        }

        $deceasedFullName = implode(' ', array_filter([
            $validated['first_name'],
            $validated['middle_name'] ?? null,
            $validated['last_name'],
            $validated['suffix'] ?? null,
        ]));

        $duplicateDeceased = Deceased::query()
            ->where('branch_id', $deceased->branch_id)
            ->where('client_id', $validated['client_id'])
            ->whereKeyNot($deceased->id)
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower(trim($deceasedFullName))]);

        if (!empty($validated['died'])) {
            $duplicateDeceased->whereDate('died', $validated['died']);
        } else {
            $duplicateDeceased->whereNull('died')->whereNull('date_of_death');
        }

        if ($duplicateDeceased->exists()) {
            return back()->withErrors([
                'first_name' => 'Another deceased record with the same name and date of death already exists for this client.',
            ])->withInput();
        }

        $wakeDays = $this->resolveWakeDays(
            $validated['wake_days'] ?? null,
            $validated['died'] ?? null,
            $validated['interment_at'] ?? null
        );
        $resolvedAge = $this->resolveAge(
            $validated['born'] ?? null,
            $validated['died'] ?? null,
            $validated['age'] ?? null
        );

        $photoPath = $deceased->photo_path;
        $removePhoto = $request->boolean('remove_photo');
        if ($removePhoto && $photoPath) {
            Storage::disk('public')->delete($photoPath);
            $photoPath = null;
        }

        if ($request->hasFile('photo')) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $request->file('photo')->store('deceased-photos', 'public');
        }

        $intermentAt = !empty($validated['interment_at'])
            ? \Carbon\Carbon::parse($validated['interment_at'])
            : null;

        $before = [
            'full_name' => $deceased->full_name,
            'died' => $deceased->died,
            'interment_at' => $deceased->interment_at,
            'place_of_cemetery' => $deceased->place_of_cemetery,
            'client_id' => $deceased->client_id,
        ];

        $deceased->update([
            'client_id'   => $validated['client_id'],
            'address'     => $validated['address'] ?? null,
            'first_name'  => $validated['first_name'],
            'last_name'   => $validated['last_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'suffix'      => $validated['suffix'] ?? null,
            'born' => $validated['born'] ?? null,
            'died' => $validated['died'] ?? null,
            'date_of_death' => $validated['died'] ?? null,
            'age' => $resolvedAge,
            'interment' => $intermentAt?->toDateString(),
            'interment_at' => $intermentAt,
            'wake_days' => $wakeDays,
            'place_of_cemetery' => $validated['place_of_cemetery'] ?? null,
            'coffin_length_cm' => $validated['coffin_length_cm'] ?? null,
            'coffin_size' => $validated['coffin_size'] ?? null,
            'photo_path' => $photoPath,
        ]);

        AuditLogger::log(
            action: 'deceased.updated',
            actionType: 'update',
            entityType: 'deceased',
            entityId: $deceased->id,
            metadata: [
                'before' => $before,
                'after' => [
                    'full_name' => $deceased->full_name,
                    'died' => $deceased->died,
                    'interment_at' => $deceased->interment_at,
                    'place_of_cemetery' => $deceased->place_of_cemetery,
                    'client_id' => $deceased->client_id,
                ],
            ],
            branchId: $deceased->branch_id
        );

        return redirect()->route('deceased.index')->with('success', 'Deceased record updated successfully.');
    }

    public function destroy(Deceased $deceased)
    {
        if ((int) $deceased->branch_id !== $this->mainBranchIdForDirectory()) {
            abort(403);
        }

        if (FuneralCase::where('deceased_id', $deceased->id)->exists()) {
            return back()->withErrors([
                'deceased' => 'This deceased record has linked case records and cannot be deleted.',
            ]);
        }

        $deceasedId = $deceased->id;
        $deceasedName = $deceased->full_name;
        $deceasedBranchId = $deceased->branch_id;

        if ($deceased->photo_path) {
            Storage::disk('public')->delete($deceased->photo_path);
        }

        $deceased->delete();

        AuditLogger::log(
            action: 'deceased.deleted',
            actionType: 'delete',
            entityType: 'deceased',
            entityId: $deceasedId,
            metadata: [
                'full_name' => $deceasedName,
                'branch_id' => $deceasedBranchId,
            ],
            branchId: $deceasedBranchId
        );

        return back()->with('success', 'Deceased record deleted.');
    }

    private function mainBranchIdForDirectory(): int
    {
        $user = auth()->user();
        if (!$user || !$user->branch_id) {
            abort(403);
        }

        return (int) $user->branch_id;
    }

    private function resolveAge(?string $born, ?string $died, ?int $fallbackAge = null): ?int
    {
        if (!$born || !$died) {
            return $fallbackAge;
        }

        try {
            $bornDate = \Carbon\Carbon::parse($born);
            $diedDate = \Carbon\Carbon::parse($died);
            if ($diedDate->lessThan($bornDate)) {
                return $fallbackAge;
            }

            return $bornDate->diffInYears($diedDate);
        } catch (\Throwable $e) {
            return $fallbackAge;
        }
    }

    private function isIntermentAfterDeathDate(?string $died, ?string $intermentAt): bool
    {
        if (!$died || !$intermentAt) {
            return true;
        }

        try {
            $diedDate = \Carbon\Carbon::parse($died)->startOfDay();
            $intermentDate = \Carbon\Carbon::parse($intermentAt)->startOfDay();

            return $intermentDate->greaterThan($diedDate);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function resolveWakeDays(?int $wakeDays, ?string $died, ?string $intermentAt): ?int
    {
        if ($wakeDays !== null) {
            return max(1, min(30, (int) $wakeDays));
        }

        if (!$died || !$intermentAt) {
            return null;
        }

        try {
            $diedDate = \Carbon\Carbon::parse($died)->startOfDay();
            $intermentDate = \Carbon\Carbon::parse($intermentAt)->startOfDay();
            if ($intermentDate->lessThanOrEqualTo($diedDate)) {
                return null;
            }

            return max(1, min(30, $diedDate->diffInDays($intermentDate)));
        } catch (\Throwable $e) {
            return null;
        }
    }

}
