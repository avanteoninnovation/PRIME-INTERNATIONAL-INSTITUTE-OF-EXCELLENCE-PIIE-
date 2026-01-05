<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostelFee extends Model
{
    use HasFactory;
    protected $fillable = [
        'hostel_id',
        'school_id',
        'student_id',
        'title',
        'amount',
        'status',
        'document_image',
        'fee_payment_date',
    ];

    public function student()
    {
        return $this->belongsTo(User::class);
    }

    public function hostel()
    {
        return $this->belongsTo(Hostel::class);
    }

    public function room()
    {
        return $this->belongsTo(HostelRoom::class);
    }
    public function getTimestampAttribute()
    {
        return strtotime($this->created_at);
    }

    public function getTotalAmountAttribute()
    {
        return $this->amount;
    }
}
