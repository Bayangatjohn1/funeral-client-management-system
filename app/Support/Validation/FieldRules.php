<?php

namespace App\Support\Validation;

class FieldRules
{
    /**
     * Unicode-safe regex for name fields.
     * Allows Unicode letters (\pL), combining marks (\pM), spaces, apostrophes, dots, and hyphens.
     * Supports Filipino/international names: Niño, José, Dela Cruz, O'Connor, Anne-Marie.
     */
    private const NAME_REGEX = "regex:/^[\\pL\\pM\\s.'\\-]+$/u";

    /** Readable message to pair with NAME_REGEX violations. */
    public static function nameRegexMessage(string $field = 'This field'): string
    {
        return "{$field} may only contain letters (including accented characters like Ñ and É), spaces, apostrophes, dots, and hyphens.";
    }

    public static function personName(bool $required = true): array
    {
        $rules = ['string', 'max:255', self::NAME_REGEX];
        array_unshift($rules, $required ? 'required' : 'nullable');

        return $rules;
    }

    /** Single name component (first_name, last_name, middle_name, suffix). */
    public static function namePart(bool $required = true, int $min = 0): array
    {
        $rules = ['string', 'max:100', self::NAME_REGEX];
        if ($min > 0) {
            $rules[] = 'min:' . $min;
        }
        array_unshift($rules, $required ? 'required' : 'nullable');

        return $rules;
    }

    public static function contactNumber(bool $required = false): array
    {
        $rules = ['string', 'max:50', 'regex:/^[0-9+\\-\\s()]+$/'];
        array_unshift($rules, $required ? 'required' : 'nullable');

        return $rules;
    }

    public static function searchName(): array
    {
        return ['nullable', 'string', 'max:100', "regex:/^[\\pL\\pM\\s.'\\-]+$/u"];
    }
}
