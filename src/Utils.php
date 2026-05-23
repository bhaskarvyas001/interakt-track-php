<?php

namespace Interakt\Track;

class Utils
{
    public static function requireType(string $name, $field, $types): void
    {
        $valid = false;
        if (is_array($types)) {
            foreach ($types as $type) {
                if ($type === 'string' && is_string($field)) {
                    $valid = true;
                    break;
                }
                if ($type === 'array' && is_array($field)) {
                    $valid = true;
                    break;
                }
                if ($type === 'int' && is_int($field)) {
                    $valid = true;
                    break;
                }
                if ($type === 'float' && is_float($field)) {
                    $valid = true;
                    break;
                }
                if ($type === 'numeric' && is_numeric($field)) {
                    $valid = true;
                    break;
                }
            }
        } else {
            $valid = self::validateType($field, $types);
        }

        if (!$valid) {
            throw new \InvalidArgumentException(sprintf('%s must have %s, got: %s', $name, is_array($types) ? implode('|', $types) : $types, gettype($field)));
        }
    }

    private static function validateType($field, string $type): bool
    {
        if ($type === 'string') {
            return is_string($field);
        }

        if ($type === 'array') {
            return is_array($field);
        }

        if ($type === 'int') {
            return is_int($field);
        }

        if ($type === 'float') {
            return is_float($field);
        }

        if ($type === 'numeric') {
            return is_numeric($field);
        }

        return false;
    }

    public static function verifyCountryCode(string $countryCode): void
    {
        $countryCode = str_replace('+', '', $countryCode);
        if ($countryCode === '' || !ctype_digit($countryCode)) {
            throw new \InvalidArgumentException(sprintf('Invalid country_code %s', $countryCode));
        }

        $numeric = (int) $countryCode;
        if ($numeric <= 0 || $numeric > 999) {
            throw new \InvalidArgumentException(sprintf('Invalid country_code %s', $countryCode));
        }
    }

    public static function removeTrailingSlash(string $host): string
    {
        return rtrim($host, '/');
    }

    public static function stringify($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return (string) $value;
    }
}
