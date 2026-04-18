<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Account
{
    protected $table = 'staff';

    protected $primaryKey = 'staff_id';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'password_changed_at',
        'remember_token',
        'admin_id',
    ];

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }

    public function assignedBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'staff_id', 'staff_id');
    }
}
