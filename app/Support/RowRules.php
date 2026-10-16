<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * Checks long lists of plain rows (what the sync service sends) with a small set of
 * rules: required, nullable, string, integer, numeric, min:N, max:N, date_format:Y-m-d.
 *
 * Laravel's validator is built for forms: "list.*.field" rules slow down sharply as
 * the list grows, and even one validator per row costs too much with tens of thousands
 * of rows. These checks give the same answers and the usual error keys
 * ("list.index.field") in a fraction of the time.
 */
class RowRules
{
    /**
     * @param  array<string, list<string>>  $rules  field => rules, like a Laravel rule array
     * @return list<array> the rows with only the fields that have rules
     *
     * @throws ValidationException on the first wrong row
     */
    public static function validate(array $rows, string $key, array $rules): array
    {
        $compiled = array_map(self::compile(...), $rules);
        $valid = [];

        foreach (array_values($rows) as $index => $row) {
            if (! is_array($row)) {
                self::fail("$key.$index", 'array');
            }

            $clean = [];
            foreach ($compiled as $field => $rule) {
                $present = array_key_exists($field, $row);
                $value = $present ? $row[$field] : null;

                if ($value === null || $value === '') {
                    if ($rule['required']) {
                        self::fail("$key.$index.$field", 'required');
                    }
                    if ($present) {
                        $clean[$field] = $value;
                    }

                    continue;
                }

                self::check("$key.$index.$field", $value, $rule);
                $clean[$field] = $value;
            }

            $valid[] = $clean;
        }

        return $valid;
    }

    private static function compile(array $rules): array
    {
        $compiled = ['required' => false, 'type' => null, 'min' => null, 'max' => null, 'date' => false];

        foreach ($rules as $rule) {
            [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
            match ($name) {
                'required' => $compiled['required'] = true,
                'nullable' => null,
                'string', 'integer', 'numeric' => $compiled['type'] = $name,
                'min' => $compiled['min'] = (float) $parameter,
                'max' => $compiled['max'] = (float) $parameter,
                'date_format' => $compiled['date'] = $parameter === 'Y-m-d'
                    ? true
                    : throw new \InvalidArgumentException("Only date_format:Y-m-d is supported, got $rule"),
                default => throw new \InvalidArgumentException("Unsupported rule $rule"),
            };
        }

        return $compiled;
    }

    private static function check(string $attribute, mixed $value, array $rule): void
    {
        $size = null;

        switch ($rule['type']) {
            case 'string':
                if (! is_string($value)) {
                    self::fail($attribute, 'string');
                }
                $size = mb_strlen($value);
                break;
            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    self::fail($attribute, 'integer');
                }
                $size = (int) $value;
                break;
            case 'numeric':
                if (! is_numeric($value)) {
                    self::fail($attribute, 'numeric');
                }
                $size = (float) $value;
                break;
        }

        $kind = $rule['type'] === 'string' ? 'string' : 'numeric';
        if ($rule['min'] !== null && $size < $rule['min']) {
            self::fail($attribute, "min.$kind", ['min' => $rule['min']]);
        }
        if ($rule['max'] !== null && $size > $rule['max']) {
            self::fail($attribute, "max.$kind", ['max' => $rule['max']]);
        }

        if ($rule['date']) {
            $date = is_string($value) ? \DateTime::createFromFormat('!Y-m-d', $value) : false;
            if ($date === false || $date->format('Y-m-d') !== $value) {
                self::fail($attribute, 'date_format', ['format' => 'Y-m-d']);
            }
        }
    }

    private static function fail(string $attribute, string $rule, array $replace = []): never
    {
        throw ValidationException::withMessages([
            $attribute => __("validation.$rule", ['attribute' => $attribute, ...$replace]),
        ]);
    }
}
