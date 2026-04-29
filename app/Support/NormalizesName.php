<?php

namespace App\Support;

/**
 * Shared name normalization for models that keep both split name fields and a
 * legacy display/search column such as full_name.
 */
trait NormalizesName
{
    public static function cleanNamePart(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', $value));

        return $value === '' ? null : $value;
    }

    public static function buildFullName(
        ?string $firstName,
        ?string $middleName = null,
        ?string $lastName = null,
        ?string $suffix = null
    ): string {
        return implode(' ', array_filter([
            static::cleanNamePart($firstName),
            static::cleanNamePart($middleName),
            static::cleanNamePart($lastName),
            static::cleanNamePart($suffix),
        ], static fn (?string $part): bool => $part !== null));
    }

    public static function normalizedNameParts(array $parts): array
    {
        foreach (['first_name', 'middle_name', 'last_name', 'suffix'] as $field) {
            $parts[$field] = static::cleanNamePart($parts[$field] ?? null);
        }

        return $parts;
    }

    public static function parseFullName(string $full): array
    {
        return static::parseName($full);
    }

    protected function normalizeNameFields(): void
    {
        foreach (['first_name', 'middle_name', 'last_name', 'suffix', 'full_name'] as $col) {
            if (\array_key_exists($col, $this->attributes)) {
                $this->attributes[$col] = static::cleanNamePart($this->attributes[$col]);
            }
        }

        $first = $this->attributes['first_name'] ?? null;
        $last = $this->attributes['last_name'] ?? null;

        if ($first || $last) {
            $this->attributes['full_name'] = static::buildFullName(
                $first,
                $this->attributes['middle_name'] ?? null,
                $last,
                $this->attributes['suffix'] ?? null,
            );
        } elseif (! empty($this->attributes['full_name'])) {
            foreach (static::parseName($this->attributes['full_name']) as $key => $val) {
                $this->attributes[$key] = $val;
            }
        }
    }

    /**
     * Returns split parts, parsing full_name only as a legacy fallback for edit
     * forms that need pre-populated normalized fields.
     */
    public function splitName(): array
    {
        $first = $this->attributes['first_name'] ?? null;
        $last = $this->attributes['last_name'] ?? null;

        if ($first || $last) {
            return [
                'first_name' => $first,
                'middle_name' => $this->attributes['middle_name'] ?? null,
                'last_name' => $last,
                'suffix' => $this->attributes['suffix'] ?? null,
            ];
        }

        $full = $this->attributes['full_name'] ?? '';

        return $full
            ? static::parseName($full)
            : ['first_name' => null, 'middle_name' => null, 'last_name' => null, 'suffix' => null];
    }

    public function getFullNameAttribute(?string $value): string
    {
        $first = $this->attributes['first_name'] ?? null;

        if (! empty($first)) {
            return static::buildFullName(
                $first,
                $this->attributes['middle_name'] ?? null,
                $this->attributes['last_name'] ?? null,
                $this->attributes['suffix'] ?? null,
            );
        }

        return $value ?? '';
    }

    public function setFullNameAttribute(string $value): void
    {
        $this->attributes['full_name'] = $value;
    }

    /**
     * Best-effort parse of a full-name string into four parts.
     * Format: [First] [Middle...] [Last] [Suffix?]
     */
    private static function parseName(string $full): array
    {
        static $knownSuffixes = ['jr', 'jr.', 'sr', 'sr.', 'ii', 'iii', 'iv', 'v'];

        $parts = preg_split('/\s+/', trim($full), -1, PREG_SPLIT_NO_EMPTY);

        $suffix = null;
        if (count($parts) > 1 && in_array(strtolower(end($parts)), $knownSuffixes, true)) {
            $suffix = array_pop($parts);
        }

        $first = count($parts) ? array_shift($parts) : null;
        $last = count($parts) ? array_pop($parts) : null;
        $middle = count($parts) ? implode(' ', $parts) : null;

        return ['first_name' => $first, 'middle_name' => $middle, 'last_name' => $last, 'suffix' => $suffix];
    }
}
