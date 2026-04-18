<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Admin extends Account
{
    protected $table = 'admins';

    protected $primaryKey = 'admin_id';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'password_changed_at',
        'remember_token',
    ];

    public function createdStaff(): HasMany
    {
        return $this->hasMany(Staff::class, 'admin_id', 'admin_id');
    }

    public function statusUpdatedRooms(): HasMany
    {
        return $this->hasMany(Room::class, 'admin_id', 'admin_id');
    }
}
