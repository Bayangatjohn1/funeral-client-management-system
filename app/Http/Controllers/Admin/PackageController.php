<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::orderBy('name')->get();

        return view('admin.packages.index', compact('packages'));
    }

    public function create()
    {
        return view('admin.packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'coffin_type' => 'nullable|string|max:150',
            'price' => 'required|numeric|min:0.01',
            'inclusions' => 'nullable|string|max:3000',
            'freebies' => 'nullable|string|max:3000',
            'promo_label' => 'nullable|string|max:120',
            'promo_value_type' => 'nullable|in:AMOUNT,PERCENT',
            'promo_value' => 'nullable|numeric|min:0',
            'promo_starts_at' => 'nullable|date',
            'promo_ends_at' => 'nullable|date|after_or_equal:promo_starts_at',
            'promo_is_active' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $promoPayload = $this->resolvePromoPayload($request, $validated);

        $package = Package::create([
            'name' => $validated['name'],
            'coffin_type' => $validated['coffin_type'] ?? null,
            'price' => $validated['price'],
            'inclusions' => $validated['inclusions'] ?? null,
            'freebies' => $validated['freebies'] ?? null,
            'promo_label' => $promoPayload['promo_label'],
            'promo_value_type' => $promoPayload['promo_value_type'],
            'promo_value' => $promoPayload['promo_value'],
            'promo_starts_at' => $promoPayload['promo_starts_at'],
            'promo_ends_at' => $promoPayload['promo_ends_at'],
            'promo_is_active' => $promoPayload['promo_is_active'],
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLogger::log(
            'package.created',
            'create',
            'package',
            $package->id,
            [
                'price' => $package->price,
                'promo_label' => $package->promo_label,
            ],
            null,
            null,
            'success',
            null,
            'Package created'
        );

        return redirect()->route('admin.packages.index')->with('success', 'Package created successfully.');
    }

    public function edit(Package $package)
    {
        return view('admin.packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'coffin_type' => 'nullable|string|max:150',
            'price' => 'required|numeric|min:0.01',
            'inclusions' => 'nullable|string|max:3000',
            'freebies' => 'nullable|string|max:3000',
            'promo_label' => 'nullable|string|max:120',
            'promo_value_type' => 'nullable|in:AMOUNT,PERCENT',
            'promo_value' => 'nullable|numeric|min:0',
            'promo_starts_at' => 'nullable|date',
            'promo_ends_at' => 'nullable|date|after_or_equal:promo_starts_at',
            'promo_is_active' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $promoPayload = $this->resolvePromoPayload($request, $validated);

        $before = [
            'price' => $package->price,
            'promo_label' => $package->promo_label,
            'promo_is_active' => $package->promo_is_active,
        ];

        $package->update([
            'name' => $validated['name'],
            'coffin_type' => $validated['coffin_type'] ?? null,
            'price' => $validated['price'],
            'inclusions' => $validated['inclusions'] ?? null,
            'freebies' => $validated['freebies'] ?? null,
            'promo_label' => $promoPayload['promo_label'],
            'promo_value_type' => $promoPayload['promo_value_type'],
            'promo_value' => $promoPayload['promo_value'],
            'promo_starts_at' => $promoPayload['promo_starts_at'],
            'promo_ends_at' => $promoPayload['promo_ends_at'],
            'promo_is_active' => $promoPayload['promo_is_active'],
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLogger::log(
            'package.updated',
            'update',
            'package',
            $package->id,
            [
                'price' => $package->price,
                'promo_label' => $package->promo_label,
                'promo_active' => $package->promo_is_active,
                'changes' => [
                    ['field' => 'price', 'before' => $before['price'], 'after' => $package->price],
                    ['field' => 'promo_label', 'before' => $before['promo_label'], 'after' => $package->promo_label],
                    ['field' => 'promo_is_active', 'before' => $before['promo_is_active'], 'after' => $package->promo_is_active],
                ],
            ],
            null,
            null,
            'success',
            'Package updated',
            'Package updated'
        );

        return redirect()->route('admin.packages.index')->with('success', 'Package updated successfully.');
    }

    public function quickUpdatePrice(Request $request, Package $package)
    {
        $validated = $request->validate([
            'price' => 'required|numeric|min:0.01',
        ]);

        $beforePrice = $package->price;

        $package->update([
            'price' => $validated['price'],
        ]);

        AuditLogger::log(
            'package.price_changed',
            'update',
            'package',
            $package->id,
            [
                'price' => $validated['price'],
                'changes' => [
                    ['field' => 'price', 'before' => $beforePrice, 'after' => $validated['price']],
                ],
            ],
            null,
            null,
            'success',
            'Package price updated',
            'Package price updated'
        );

        return redirect()->route('admin.packages.index')->with('success', 'Package price updated.');
    }

    private function resolvePromoPayload(Request $request, array $validated): array
    {
        $promoActive = $request->boolean('promo_is_active');
        $hasPromoValue = isset($validated['promo_value']) && (float) $validated['promo_value'] > 0;
        $hasPromoType = !empty($validated['promo_value_type']);

        if (!$promoActive || !$hasPromoValue || !$hasPromoType) {
            return [
                'promo_label' => null,
                'promo_value_type' => null,
                'promo_value' => null,
                'promo_starts_at' => null,
                'promo_ends_at' => null,
                'promo_is_active' => false,
            ];
        }

        return [
            'promo_label' => $validated['promo_label'] ?? null,
            'promo_value_type' => $validated['promo_value_type'],
            'promo_value' => (float) $validated['promo_value'],
            'promo_starts_at' => $validated['promo_starts_at'] ?? null,
            'promo_ends_at' => $validated['promo_ends_at'] ?? null,
            'promo_is_active' => true,
        ];
    }
}
