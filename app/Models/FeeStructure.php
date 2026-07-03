<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use HasFactory;
    protected $fillable = [
        'school_id', 'name', 'fee_type', 'amount', 'is_mandatory',
        'per_semester', 'class_id', 'programme_id', 'session_id'
    ];
}
