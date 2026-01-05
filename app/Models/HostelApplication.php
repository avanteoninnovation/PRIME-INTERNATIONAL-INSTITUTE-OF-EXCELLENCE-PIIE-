<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostelApplication extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_id', 'hostel_id', 'room_id', 'status', 'note', 'school_id',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function hostel()
    {
        return $this->belongsTo(Hostel::class);
    }

    public function room()
    {
        return $this->belongsTo(HostelRoom::class, 'room_id');
    }

    protected $casts = [
        'accepted_at' => 'datetime',
    ];
}
