<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Account
{
    protected $table = 'customers';

    protected $primaryKey = 'customer_id';

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
        'email_verified_at',
        'password_changed_at',
        'remember_token',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id', 'customer_id');
    }
}
