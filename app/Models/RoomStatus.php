<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Model;

class RoomStatus extends Model
{
    use HasLegacyIdAttribute;

    protected $table = 'room_status';

    protected $primaryKey = 'room_status_id';
}
