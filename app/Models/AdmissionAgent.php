<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionAgent extends Model
{
    use HasFactory;
    protected $table = 'admissions_agents';
    protected $fillable = ['school_id', 'name', 'email', 'phone', 'commission_pct', 'is_active'];
}
