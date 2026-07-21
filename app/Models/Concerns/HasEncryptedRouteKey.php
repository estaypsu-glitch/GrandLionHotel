<?php

namespace App\Models\Concerns;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

trait HasEncryptedRouteKey
{
    public function getRouteKey(): mixed
    {
        return self::encryptRouteKey((string) $this->getKey());
    }

    public function resolveRouteBinding($value, $field = null): mixed
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        $key = self::decryptRouteKey((string) $value);

        if ($key === null) {
            throw (new ModelNotFoundException())->setModel(static::class, [$value]);
        }

        return $this->where($this->getRouteKeyName(), $key)->firstOrFail();
    }

    public static function encryptRouteKey(string|int $key): string
    {
        return rtrim(strtr(base64_encode(Crypt::encryptString((string) $key)), '+/', '-_'), '=');
    }

    public static function decryptRouteKey(string $value): ?string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $payload = base64_decode(strtr($value, '-_', '+/'), true);
        if ($payload === false || !Str::startsWith($payload, 'eyJpdiI6')) {
            return null;
        }

        try {
            $key = Crypt::decryptString($payload);
        } catch (DecryptException) {
            return null;
        }

        return ctype_digit($key) ? $key : null;
    }
}
