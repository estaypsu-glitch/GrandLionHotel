<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addStandaloneAccountColumns();
        $this->backfillStandaloneAccountColumns();
        $this->addDirectReferenceColumns();
        $this->backfillDirectReferenceColumns();
        $this->dropUsersForeignKeys();
        $this->swapDirectReferenceColumns();
        $this->dropProfileUserLinks();
        $this->finalizeStandaloneAccountIndexes();
        $this->dropUsersTable();
    }

    public function down(): void
    {
        throw new \RuntimeException('Rollback is not supported for removing the users table.');
    }

    private function addStandaloneAccountColumns(): void
    {
        foreach (['admins', 'staff', 'customers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (!Schema::hasColumn($tableName, 'name')) {
                    $table->string('name')->nullable()->after('id');
                }
                if (!Schema::hasColumn($tableName, 'email')) {
                    $table->string('email')->nullable()->after('name');
                }
                if (!Schema::hasColumn($tableName, 'google_id')) {
                    $table->string('google_id')->nullable()->after('email');
                }
                if (!Schema::hasColumn($tableName, 'phone')) {
                    $table->string('phone', 30)->nullable()->after('google_id');
                }
                if (!Schema::hasColumn($tableName, 'address_line')) {
                    $table->string('address_line')->nullable()->after('phone');
                }
                if (!Schema::hasColumn($tableName, 'city')) {
                    $table->string('city', 120)->nullable()->after('address_line');
                }
                if (!Schema::hasColumn($tableName, 'province')) {
                    $table->string('province', 120)->nullable()->after('city');
                }
                if (!Schema::hasColumn($tableName, 'country')) {
                    $table->string('country', 120)->nullable()->after('province');
                }
                if (!Schema::hasColumn($tableName, 'email_verified_at')) {
                    $table->timestamp('email_verified_at')->nullable()->after('country');
                }
                if (!Schema::hasColumn($tableName, 'password')) {
                    $table->string('password')->nullable()->after('email_verified_at');
                }
                if (!Schema::hasColumn($tableName, 'password_changed_at')) {
                    $table->timestamp('password_changed_at')->nullable()->after('password');
                }
                if (!Schema::hasColumn($tableName, 'remember_token')) {
                    $table->string('remember_token', 100)->nullable()->after('password_changed_at');
                }
            });
        }
    }

    private function backfillStandaloneAccountColumns(): void
    {
        $usersById = DB::table('users')
            ->get([
                'id',
                'name',
                'email',
                'google_id',
                'phone',
                'address_line',
                'city',
                'province',
                'country',
                'email_verified_at',
                'password',
                'password_changed_at',
                'remember_token',
            ])
            ->keyBy('id');

        foreach (['admins', 'staff', 'customers'] as $tableName) {
            DB::table($tableName)
                ->whereNotNull('user_id')
                ->select(['id', 'user_id'])
                ->orderBy('id')
                ->chunkById(200, function ($accounts) use ($tableName, $usersById): void {
                    foreach ($accounts as $account) {
                        $user = $usersById->get($account->user_id);

                        if ($user === null) {
                            continue;
                        }

                        DB::table($tableName)
                            ->where('id', $account->id)
                            ->update([
                                'name' => $user->name,
                                'email' => $user->email,
                                'google_id' => $user->google_id,
                                'phone' => $user->phone,
                                'address_line' => $user->address_line,
                                'city' => $user->city,
                                'province' => $user->province,
                                'country' => $user->country,
                                'email_verified_at' => $user->email_verified_at,
                                'password' => $user->password,
                                'password_changed_at' => $user->password_changed_at,
                                'remember_token' => $user->remember_token,
                            ]);
                    }
                }, 'id');
        }
    }

    private function addDirectReferenceColumns(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (!Schema::hasColumn('bookings', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('bookings', 'handled_by_staff_ref')) {
                $table->foreignId('handled_by_staff_ref')->nullable()->after('handled_by_staff_id');
            }
            if (!Schema::hasColumn('bookings', 'assigned_staff_ref')) {
                $table->foreignId('assigned_staff_ref')->nullable()->after('assigned_staff_id');
            }
        });

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            if (!Schema::hasColumn('booking_guest_details', 'created_by_staff_ref')) {
                $table->foreignId('created_by_staff_ref')->nullable()->after('created_by_staff_id');
            }
        });

        Schema::table('payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('payments', 'verified_by_staff_ref')) {
                $table->foreignId('verified_by_staff_ref')->nullable()->after('verified_by_staff_id');
            }
        });

        Schema::table('rooms', function (Blueprint $table): void {
            if (!Schema::hasColumn('rooms', 'last_cleaned_by_ref')) {
                $table->foreignId('last_cleaned_by_ref')->nullable()->after('last_cleaned_by');
            }
        });
    }

    private function backfillDirectReferenceColumns(): void
    {
        $customerIdsByUserId = DB::table('customers')
            ->whereNotNull('user_id')
            ->pluck('id', 'user_id');
        $staffIdsByUserId = DB::table('staff')
            ->whereNotNull('user_id')
            ->pluck('id', 'user_id');

        DB::table('bookings')
            ->select(['id', 'user_id', 'handled_by_staff_id', 'assigned_staff_id'])
            ->orderBy('id')
            ->chunkById(200, function ($bookings) use ($customerIdsByUserId, $staffIdsByUserId): void {
                foreach ($bookings as $booking) {
                    $updates = [];

                    if ($booking->user_id !== null && isset($customerIdsByUserId[$booking->user_id])) {
                        $updates['customer_id'] = $customerIdsByUserId[$booking->user_id];
                    }

                    if ($booking->handled_by_staff_id !== null && isset($staffIdsByUserId[$booking->handled_by_staff_id])) {
                        $updates['handled_by_staff_ref'] = $staffIdsByUserId[$booking->handled_by_staff_id];
                    }

                    if ($booking->assigned_staff_id !== null && isset($staffIdsByUserId[$booking->assigned_staff_id])) {
                        $updates['assigned_staff_ref'] = $staffIdsByUserId[$booking->assigned_staff_id];
                    }

                    if ($updates !== []) {
                        DB::table('bookings')
                            ->where('id', $booking->id)
                            ->update($updates);
                    }
                }
            }, 'id');

        DB::table('booking_guest_details')
            ->select(['id', 'created_by_staff_id'])
            ->orderBy('id')
            ->chunkById(200, function ($guestDetails) use ($staffIdsByUserId): void {
                foreach ($guestDetails as $guestDetail) {
                    if ($guestDetail->created_by_staff_id === null || !isset($staffIdsByUserId[$guestDetail->created_by_staff_id])) {
                        continue;
                    }

                    DB::table('booking_guest_details')
                        ->where('id', $guestDetail->id)
                        ->update([
                            'created_by_staff_ref' => $staffIdsByUserId[$guestDetail->created_by_staff_id],
                        ]);
                }
            }, 'id');

        DB::table('payments')
            ->select(['id', 'verified_by_staff_id'])
            ->orderBy('id')
            ->chunkById(200, function ($payments) use ($staffIdsByUserId): void {
                foreach ($payments as $payment) {
                    if ($payment->verified_by_staff_id === null || !isset($staffIdsByUserId[$payment->verified_by_staff_id])) {
                        continue;
                    }

                    DB::table('payments')
                        ->where('id', $payment->id)
                        ->update([
                            'verified_by_staff_ref' => $staffIdsByUserId[$payment->verified_by_staff_id],
                        ]);
                }
            }, 'id');

        DB::table('rooms')
            ->select(['id', 'last_cleaned_by'])
            ->orderBy('id')
            ->chunkById(200, function ($rooms) use ($staffIdsByUserId): void {
                foreach ($rooms as $room) {
                    if ($room->last_cleaned_by === null || !isset($staffIdsByUserId[$room->last_cleaned_by])) {
                        continue;
                    }

                    DB::table('rooms')
                        ->where('id', $room->id)
                        ->update([
                            'last_cleaned_by_ref' => $staffIdsByUserId[$room->last_cleaned_by],
                        ]);
                }
            }, 'id');
    }

    private function dropUsersForeignKeys(): void
    {
        $this->dropForeignIfExists('bookings', ['user_id']);
        $this->dropForeignIfExists('bookings', ['handled_by_staff_id']);
        $this->dropForeignIfExists('bookings', ['assigned_staff_id']);
        $this->dropForeignIfExists('booking_guest_details', ['created_by_staff_id']);
        $this->dropForeignIfExists('payments', ['verified_by_staff_id']);
        $this->dropForeignIfExists('rooms', ['last_cleaned_by']);
        $this->dropForeignIfExists('admins', ['user_id']);
        $this->dropForeignIfExists('staff', ['user_id']);
        $this->dropForeignIfExists('customers', ['user_id']);
        $this->dropForeignIfExists('sessions', ['user_id']);

        if (Schema::hasColumn('password_reset_tokens', 'user_id')) {
            $this->dropForeignIfExists('password_reset_tokens', ['user_id']);
        }

        if (Schema::hasColumn('registration_verifications', 'user_id')) {
            $this->dropForeignIfExists('registration_verifications', ['user_id']);
        }
    }

    private function swapDirectReferenceColumns(): void
    {
        $this->dropIndexIfExists('bookings', 'bookings_assigned_staff_id_status_index');

        Schema::table('bookings', function (Blueprint $table): void {
            if (Schema::hasColumn('bookings', 'user_id')) {
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('bookings', 'handled_by_staff_id')) {
                $table->dropColumn('handled_by_staff_id');
            }
            if (Schema::hasColumn('bookings', 'assigned_staff_id')) {
                $table->dropColumn('assigned_staff_id');
            }
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->renameColumn('handled_by_staff_ref', 'handled_by_staff_id');
            $table->renameColumn('assigned_staff_ref', 'assigned_staff_id');
        });

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            if (Schema::hasColumn('booking_guest_details', 'created_by_staff_id')) {
                $table->dropColumn('created_by_staff_id');
            }
        });

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            $table->renameColumn('created_by_staff_ref', 'created_by_staff_id');
        });

        Schema::table('payments', function (Blueprint $table): void {
            if (Schema::hasColumn('payments', 'verified_by_staff_id')) {
                $table->dropColumn('verified_by_staff_id');
            }
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->renameColumn('verified_by_staff_ref', 'verified_by_staff_id');
        });

        Schema::table('rooms', function (Blueprint $table): void {
            if (Schema::hasColumn('rooms', 'last_cleaned_by')) {
                $table->dropColumn('last_cleaned_by');
            }
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->renameColumn('last_cleaned_by_ref', 'last_cleaned_by');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('handled_by_staff_id')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('assigned_staff_id')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            $table->foreign('created_by_staff_id')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->foreign('verified_by_staff_id')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->foreign('last_cleaned_by')->references('id')->on('staff')->nullOnDelete();
        });
    }

    private function dropProfileUserLinks(): void
    {
        $this->dropIndexIfExists('admins', 'admins_user_id_unique');
        Schema::table('admins', function (Blueprint $table): void {
            if (Schema::hasColumn('admins', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });

        $this->dropIndexIfExists('staff', 'staff_user_id_unique');
        Schema::table('staff', function (Blueprint $table): void {
            if (Schema::hasColumn('staff', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });

        $this->dropIndexIfExists('customers', 'customers_user_id_unique');
        Schema::table('customers', function (Blueprint $table): void {
            if (Schema::hasColumn('customers', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }

    private function finalizeStandaloneAccountIndexes(): void
    {
        foreach (['admins', 'staff', 'customers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->unique('email', $tableName.'_email_unique');
                $table->unique('google_id', $tableName.'_google_id_unique');
            });
        }
    }

    private function dropUsersTable(): void
    {
        if (Schema::hasColumn('password_reset_tokens', 'user_id')) {
            $this->dropIndexIfExists('password_reset_tokens', 'password_reset_tokens_user_id_foreign');
            Schema::table('password_reset_tokens', function (Blueprint $table): void {
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasColumn('registration_verifications', 'user_id')) {
            $this->dropIndexIfExists('registration_verifications', 'registration_verifications_user_id_foreign');
            Schema::table('registration_verifications', function (Blueprint $table): void {
                $table->dropColumn('user_id');
            });
        }

        Schema::dropIfExists('users');
    }

    private function dropForeignIfExists(string $tableName, array $columns): void
    {
        try {
            Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                $table->dropForeign($columns);
            });
        } catch (\Throwable) {
            // Ignore missing foreign keys so the migration remains resilient.
        }
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        try {
            Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                $table->dropIndex($indexName);
            });
        } catch (\Throwable) {
            // Ignore missing indexes so this remains safe across drivers/states.
        }
    }
};
