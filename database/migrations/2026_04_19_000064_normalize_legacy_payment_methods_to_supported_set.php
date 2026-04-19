<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeLegacyMethods('payments', 'method');
        $this->normalizeLegacyMethods('booking_guest_details', 'payment_preference');
    }

    public function down(): void
    {
        // Irreversible normalization.
    }

    private function normalizeLegacyMethods(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        $instapayAliases = [
            'bank_transfer',
            'gcash',
            'paymaya',
            'maya',
            'online',
            'online_transfer',
            'online_bank_transfer',
            'insta_pay',
            'insta-pay',
        ];

        $cashAliases = [
            'pay_at_hotel',
            'cash_on_arrival',
            'pay_on_arrival',
            'pay_on_checkin',
            'pay_later',
        ];

        $cardAliases = [
            'card',
            'credit_card',
            'debit_card',
            'credit card',
            'debit card',
            'credit-debit-card',
            'credit/debit card',
        ];

        $this->replaceAliases($table, $column, $instapayAliases, 'instapay');
        $this->replaceAliases($table, $column, $cashAliases, 'cash');
        $this->replaceAliases($table, $column, $cardAliases, 'credit_debit_card');
    }

    private function replaceAliases(string $table, string $column, array $aliases, string $normalizedValue): void
    {
        foreach ($aliases as $alias) {
            DB::table($table)
                ->whereRaw("LOWER(TRIM({$column})) = ?", [strtolower(trim($alias))])
                ->update([$column => $normalizedValue]);
        }
    }
};

