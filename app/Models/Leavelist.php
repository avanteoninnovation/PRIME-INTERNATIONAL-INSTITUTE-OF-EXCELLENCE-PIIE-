<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leavelist extends Model
{
    use HasFactory;
    protected $table = 'leavelists';
    protected $fillable = [
        'school_id', 'user_id', 'leave_type_id', 'leave_type',
        'from_date', 'to_date', 'days', 'reason', 'status', 'approved_by', 'admin_comment'
    ];

    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_RETURNED = 'returned';
    const STATUS_REJECTED = 'rejected';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
