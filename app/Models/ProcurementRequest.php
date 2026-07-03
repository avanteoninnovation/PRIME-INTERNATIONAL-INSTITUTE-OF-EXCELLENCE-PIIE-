<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementRequest extends Model
{
    use HasFactory;
    protected $table = 'procurement_requests';
    protected $fillable = [
        'school_id', 'requested_by', 'title', 'description', 'quantity',
        'estimated_cost', 'vendor', 'status', 'approved_by'
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
