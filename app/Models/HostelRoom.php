<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostelRoom extends Model
{
    use HasFactory;
    protected $fillable = [
        'room_no', 'seat_fee', 'capacity', 'hostel_id', 'occupied', 'description', 'status', 'school_id',
    ];
    public function hostel()
    {
        return $this->belongsTo(Hostel::class);
    }

    public function allocations()
    {
        return $this->hasMany(HostelRoomAllocation::class);
    }
}
