<?php

namespace App\Support;

final class CeremonyLocation
{
    public static function rules(): array
    {
        return [
            'funeral_ceremony_location_mode' => ['nullable', 'in:custom,wake'],
            'funeral_ceremony_location' => ['exclude_if:funeral_ceremony_location_mode,wake', 'nullable', 'string', 'max:255'],
        ];
    }

    public static function attributes(array $validated): array
    {
        if (! array_key_exists('funeral_ceremony_location_mode', $validated)
            && ! array_key_exists('funeral_ceremony_location', $validated)) {
            return [];
        }

        $sameAsWake = ($validated['funeral_ceremony_location_mode'] ?? null) === 'wake';
        $location = trim((string) ($validated['funeral_ceremony_location'] ?? ''));

        return [
            'ceremony_at_wake_location' => $sameAsWake,
            'funeral_ceremony_location' => $sameAsWake || $location === '' ? null : $location,
        ];
    }
}
