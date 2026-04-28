<?php

namespace App\Support;

/**
 * Bidirectional name normalization for models that carry both a legacy
 * full_name column and the Phase-1 split fields (first_name, middle_name,
 * last_name, suffix).
 *
 * Call normalizeNameFields() inside the model's saving observer.
 * splitName() provides legacy-safe read access for edit forms.
 * The accessor / mutator pair keeps all callers consistent.
 *
 * Sync rules (applied in order inside normalizeNameFields):
 *  1. Trim + collapse whitespace on every name column.
 *  2. If split fields are present  → rebuild full_name from them.
 *  3. If only full_name is present → parse it into split fields.
 */
trait NormalizesName
{
    // ─── saving observer helper ───────────────────────────────────────────────

    protected function normalizeNameFields(): void
    {
        $clean = static function (?string $v): ?string {
            if ($v === null) {
                return null;
            }
            $v = trim(preg_replace('/\s+/', ' ', $v));
            return $v === '' ? null : $v;
        };

        foreach (['first_name', 'middle_name', 'last_name', 'suffix', 'full_name'] as $col) {
            if (\array_key_exists($col, $this->attributes)) {
                $this->attributes[$col] = $clean($this->attributes[$col]);
            }
        }

        $first = $this->attributes['first_name'] ?? null;
        $last  = $this->attributes['last_name']  ?? null;

        if ($first || $last) {
            // Split fields are the source of truth — rebuild full_name.
            $this->attributes['full_name'] = implode(' ', array_filter([
                $first,
                $this->attributes['middle_name'] ?? null,
                $last,
                $this->attributes['suffix']      ?? null,
            ]));
        } elseif (! empty($this->attributes['full_name'])) {
            // Only full_name present (legacy record / legacy write path) — parse it.
            foreach (static::parseName($this->attributes['full_name']) as $key => $val) {
                $this->attributes[$key] = $val;
            }
        }
    }

    // ─── Read-side split helper (used by edit forms) ──────────────────────────

    /**
     * Returns the four name parts as an associative array.
     * If split fields are not stored (legacy record), falls back to parsing
     * full_name on the fly so edit forms always show pre-populated fields.
     */
    public function splitName(): array
    {
        $first = $this->attributes['first_name'] ?? null;
        $last  = $this->attributes['last_name']  ?? null;

        if ($first || $last) {
            return [
                'first_name'  => $first,
                'middle_name' => $this->attributes['middle_name'] ?? null,
                'last_name'   => $last,
                'suffix'      => $this->attributes['suffix']      ?? null,
            ];
        }

        $full = $this->attributes['full_name'] ?? '';
        return $full
            ? static::parseName($full)
            : ['first_name' => null, 'middle_name' => null, 'last_name' => null, 'suffix' => null];
    }

    // ─── Eloquent accessor / mutator ─────────────────────────────────────────

    /**
     * Display name — prefers split fields when populated, falls back to full_name.
     */
    public function getFullNameAttribute(?string $value): string
    {
        $first = $this->attributes['first_name'] ?? null;

        if (! empty($first)) {
            return implode(' ', array_filter([
                $first,
                $this->attributes['middle_name'] ?? null,
                $this->attributes['last_name']   ?? null,
                $this->attributes['suffix']      ?? null,
            ]));
        }

        return $value ?? '';
    }

    /**
     * Legacy write path — keeps the raw column in sync.
     * New code should set first_name / last_name directly.
     */
    public function setFullNameAttribute(string $value): void
    {
        $this->attributes['full_name'] = $value;
    }

    // ─── Shared name parser ───────────────────────────────────────────────────

    /**
     * Best-effort parse of a full-name string into four parts.
     * Format: [First] [Middle…] [Last] [Suffix?]
     */
    private static function parseName(string $full): array
    {
        static $knownSuffixes = ['jr', 'jr.', 'sr', 'sr.', 'ii', 'iii', 'iv', 'v'];

        $parts = preg_split('/\s+/', trim($full), -1, PREG_SPLIT_NO_EMPTY);

        $suffix = null;
        if (\count($parts) > 1 && \in_array(strtolower(end($parts)), $knownSuffixes, true)) {
            $suffix = array_pop($parts);
        }

        $first  = \count($parts) ? array_shift($parts) : null;
        $last   = \count($parts) ? array_pop($parts)   : null;
        $middle = \count($parts) ? implode(' ', $parts) : null;

        return ['first_name' => $first, 'middle_name' => $middle, 'last_name' => $last, 'suffix' => $suffix];
    }
}
