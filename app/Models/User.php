<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'phone',
        'address_line',
        'city',
        'province',
        'country',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
        ];
    }


    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function adminProfile(): HasOne
    {
        return $this->hasOne(Admin::class);
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function assignedBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'staff_id');
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->whereHas('adminProfile');
    }

    public function scopeStaffMembers(Builder $query): Builder
    {
        return $query->whereHas('staffProfile');
    }

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->whereHas('customerProfile');
    }

    public function isAdmin(): bool
    {
        if ($this->relationLoaded('adminProfile')) {
            return !is_null($this->getRelation('adminProfile'));
        }

        return $this->adminProfile()->exists();
    }

    public function isStaff(): bool
    {
        if ($this->relationLoaded('staffProfile')) {
            return !is_null($this->getRelation('staffProfile'));
        }

        return $this->staffProfile()->exists();
    }

    public function isCustomer(): bool
    {
        if ($this->relationLoaded('customerProfile')) {
            return !is_null($this->getRelation('customerProfile'));
        }

        return $this->customerProfile()->exists();
    }

    public function canManageBookings(): bool
    {
        return $this->isAdmin() || $this->isStaff();
    }

    public function ensureAdminProfile(): Admin
    {
        $this->staffProfile()->delete();
        $this->customerProfile()->delete();

        $profile = $this->adminProfile()->firstOrCreate([]);
        $this->unsetRelation('staffProfile');
        $this->unsetRelation('customerProfile');
        $this->setRelation('adminProfile', $profile);

        return $profile;
    }

    public function ensureStaffProfile(?Admin $createdByAdmin = null): Staff
    {
        $this->adminProfile()->delete();
        $this->customerProfile()->delete();

        $profile = $this->staffProfile()->firstOrCreate([]);

        if ($createdByAdmin && $profile->admin_id !== $createdByAdmin->id) {
            $profile->forceFill(['admin_id' => $createdByAdmin->id])->save();
        }

        $this->unsetRelation('adminProfile');
        $this->unsetRelation('customerProfile');
        $this->setRelation('staffProfile', $profile->fresh());

        /** @var Staff $staffProfile */
        $staffProfile = $this->getRelation('staffProfile');

        return $staffProfile;
    }

    public function ensureCustomerProfile(): Customer
    {
        $this->adminProfile()->delete();
        $this->staffProfile()->delete();

        $profile = $this->customerProfile()->firstOrCreate([]);
        $this->unsetRelation('adminProfile');
        $this->unsetRelation('staffProfile');
        $this->setRelation('customerProfile', $profile);

        return $profile;
    }

    public function hasCompleteProfile(): bool
    {
        return filled($this->name)
            && filled($this->email)
            && filled($this->phone)
            && filled($this->address_line)
            && filled($this->city)
            && filled($this->province);
    }
}
