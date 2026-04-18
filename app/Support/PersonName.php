<?php

namespace App\Support;

class PersonName
{
    /**
     * @return array{first_name: string, last_name: string}
     */
    public static function split(?string $fullName): array
    {
        $normalized = preg_replace('/\s+/', ' ', trim((string) $fullName));
        if ($normalized === null || $normalized === '') {
            return [
                'first_name' => '',
                'last_name' => '',
            ];
        }

        $parts = explode(' ', $normalized, 2);

        return [
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
        ];
    }

    public static function combine(?string $firstName, ?string $lastName): string
    {
        return trim(implode(' ', array_filter([
            trim((string) $firstName),
            trim((string) $lastName),
        ], static fn (?string $value): bool => $value !== null && $value !== '')));
    }
}
