<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;

class AccountDirectory
{
    /**
     * @return array<string, class-string<Account>>
     */
    public static function guardModelMap(): array
    {
        return [
            'admin' => Admin::class,
            'staff' => Staff::class,
            'customer' => Customer::class,
        ];
    }

    /**
     * @return array<class-string<Account>>
     */
    public static function modelClasses(): array
    {
        return array_values(self::guardModelMap());
    }

    public static function guardFor(Account $account): string
    {
        return match (true) {
            $account instanceof Admin => 'admin',
            $account instanceof Staff => 'staff',
            default => 'customer',
        };
    }

    public static function findByEmail(string $email): ?Account
    {
        $normalizedEmail = strtolower(trim($email));
        if ($normalizedEmail === '') {
            return null;
        }

        foreach (self::guardModelMap() as $modelClass) {
            $account = $modelClass::query()
                ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
                ->first();

            if ($account) {
                return $account;
            }
        }

        return null;
    }

    public static function findByGoogleId(string $googleId): ?Account
    {
        $normalizedGoogleId = trim($googleId);
        if ($normalizedGoogleId === '') {
            return null;
        }

        return Customer::query()
            ->where('google_id', $normalizedGoogleId)
            ->first();
    }

    public static function findCustomerByEmail(string $email): ?Customer
    {
        $normalizedEmail = strtolower(trim($email));
        if ($normalizedEmail === '') {
            return null;
        }

        return Customer::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();
    }

    public static function emailExists(string $email, ?Account $ignoreAccount = null): bool
    {
        $normalizedEmail = strtolower(trim($email));
        if ($normalizedEmail === '') {
            return false;
        }

        foreach (self::guardModelMap() as $modelClass) {
            $exists = $modelClass::query()
                ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
                ->when(
                    $ignoreAccount instanceof $modelClass,
                    static fn (Builder $query) => $query->whereKeyNot($ignoreAccount->getKey())
                )
                ->exists();

            if ($exists) {
                return true;
            }
        }

        return false;
    }
}
