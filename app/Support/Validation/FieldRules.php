<?php

namespace App\Support\Validation;

class FieldRules
{
    public static function personName(bool $required = true): array
    {
        $rules = ['string', 'max:255', "regex:/^[A-Za-z\\s.'-]+$/"];
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
        return ['nullable', 'string', 'max:100', "regex:/^[A-Za-z\\s.'-]+$/"];
    }
}
