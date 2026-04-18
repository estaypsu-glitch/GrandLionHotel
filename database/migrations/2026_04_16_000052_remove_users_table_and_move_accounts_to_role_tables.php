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
        foreach (['admins', 'staff', 'customers'] as $tableName) {
            DB::statement("
                UPDATE {$tableName} role_table
                INNER JOIN users u ON u.id = role_table.user_id
                SET
                    role_table.name = u.name,
                    role_table.email = u.email,
                    role_table.google_id = u.google_id,
                    role_table.phone = u.phone,
                    role_table.address_line = u.address_line,
                    role_table.city = u.city,
                    role_table.province = u.province,
                    role_table.country = u.country,
                    role_table.email_verified_at = u.email_verified_at,
                    role_table.password = u.password,
                    role_table.password_changed_at = u.password_changed_at,
                    role_table.remember_token = u.remember_token
                WHERE role_table.user_id IS NOT NULL
            ");
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
        DB::statement("
            UPDATE bookings b
            INNER JOIN customers c ON c.user_id = b.user_id
            SET b.customer_id = c.id
            WHERE b.user_id IS NOT NULL
        ");

        DB::statement("
            UPDATE bookings b
            INNER JOIN staff s ON s.user_id = b.handled_by_staff_id
            SET b.handled_by_staff_ref = s.id
            WHERE b.handled_by_staff_id IS NOT NULL
        ");

        DB::statement("
            UPDATE bookings b
            INNER JOIN staff s ON s.user_id = b.assigned_staff_id
            SET b.assigned_staff_ref = s.id
            WHERE b.assigned_staff_id IS NOT NULL
        ");

        DB::statement("
            UPDATE booking_guest_details bgd
            INNER JOIN staff s ON s.user_id = bgd.created_by_staff_id
            SET bgd.created_by_staff_ref = s.id
            WHERE bgd.created_by_staff_id IS NOT NULL
        ");

        DB::statement("
            UPDATE payments p
            INNER JOIN staff s ON s.user_id = p.verified_by_staff_id
            SET p.verified_by_staff_ref = s.id
            WHERE p.verified_by_staff_id IS NOT NULL
        ");

        DB::statement("
            UPDATE rooms r
            INNER JOIN staff s ON s.user_id = r.last_cleaned_by
            SET r.last_cleaned_by_ref = s.id
            WHERE r.last_cleaned_by IS NOT NULL
        ");
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
        Schema::table('admins', function (Blueprint $table): void {
            if (Schema::hasColumn('admins', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });

        Schema::table('staff', function (Blueprint $table): void {
            if (Schema::hasColumn('staff', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });

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
            Schema::table('password_reset_tokens', function (Blueprint $table): void {
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasColumn('registration_verifications', 'user_id')) {
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
};
