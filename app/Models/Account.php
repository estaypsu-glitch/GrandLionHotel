<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

abstract class Account extends Authenticatable
{
    use HasLegacyIdAttribute;
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address_line',
        'city',
        'province',
        'country',
        'email_verified_at',
        'password_changed_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this instanceof Admin;
    }

    public function isStaff(): bool
    {
        return $this instanceof Staff;
    }

    public function isCustomer(): bool
    {
        return $this instanceof Customer;
    }

    public function canManageBookings(): bool
    {
        return $this->isAdmin() || $this->isStaff();
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
