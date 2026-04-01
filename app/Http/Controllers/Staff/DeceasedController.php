<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Support\Validation\FieldRules;
use Illuminate\Http\Request;
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
        ], [
            'q.regex' => 'Search may contain letters, spaces, apostrophes, periods, and hyphens only.',
        ]);

        $query = Deceased::with(['client', 'branch', 'funeralCase'])
            ->where('branch_id', $mainBranchId)
            ->latest();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where('full_name', 'like', "%{$q}%");
        }

        $deceaseds = $query->get();

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
            'client_id' => 'required|exists:clients,id',
            'address' => 'nullable|string|max:255',
            'full_name' => FieldRules::personName(),
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
            'full_name.regex' => 'Name may contain letters, spaces, apostrophes, periods, and hyphens only.',
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

        $duplicateDeceased = Deceased::query()
            ->where('branch_id', $mainBranchId)
            ->where('client_id', $validated['client_id'])
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower(trim($validated['full_name']))]);

        if (!empty($validated['died'])) {
            $duplicateDeceased->whereDate('died', $validated['died']);
        } else {
            $duplicateDeceased->whereNull('died')->whereNull('date_of_death');
        }

        if ($duplicateDeceased->exists()) {
            return back()->withErrors([
                'full_name' => 'Deceased record already exists for this client (same name and date of death).',
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

        Deceased::create([
            'branch_id' => $mainBranchId,
            'client_id' => $validated['client_id'],
            'address' => $validated['address'] ?? null,
            'full_name' => $validated['full_name'],
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

        return redirect()->route('deceased.index')->with('success', 'Deceased record added successfully.');
    }

    public function edit(Deceased $deceased)
    {
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
        $mainBranchId = $this->mainBranchIdForDirectory();
        if ((int) $deceased->branch_id !== $mainBranchId) {
            abort(403);
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'address' => 'nullable|string|max:255',
            'full_name' => FieldRules::personName(),
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
            'full_name.regex' => 'Name may contain letters, spaces, apostrophes, periods, and hyphens only.',
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

        $duplicateDeceased = Deceased::query()
            ->where('branch_id', $deceased->branch_id)
            ->where('client_id', $validated['client_id'])
            ->whereKeyNot($deceased->id)
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower(trim($validated['full_name']))]);

        if (!empty($validated['died'])) {
            $duplicateDeceased->whereDate('died', $validated['died']);
        } else {
            $duplicateDeceased->whereNull('died')->whereNull('date_of_death');
        }

        if ($duplicateDeceased->exists()) {
            return back()->withErrors([
                'full_name' => 'Another deceased record with the same name and date of death already exists for this client.',
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

        $deceased->update([
            'client_id' => $validated['client_id'],
            'address' => $validated['address'] ?? null,
            'full_name' => $validated['full_name'],
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

        if ($deceased->photo_path) {
            Storage::disk('public')->delete($deceased->photo_path);
        }

        $deceased->delete();

        return back()->with('success', 'Deceased record deleted.');
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
