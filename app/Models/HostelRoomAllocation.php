<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostelRoomAllocation extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_id', 'room_id', 'allocated_on', 'vacated_on', 'status', 'school_id',
    ];
    public function room()
    {
        return $this->belongsTo(HostelRoom::class);
    }
    public function student()
    {
        return $this->belongsTo(User::class);
    }

}
