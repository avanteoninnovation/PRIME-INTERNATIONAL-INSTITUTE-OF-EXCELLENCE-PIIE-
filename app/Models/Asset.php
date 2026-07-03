<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;
    protected $fillable = [
        'school_id', 'asset_tag', 'name', 'category_id', 'serial_number',
        'purchase_date', 'purchase_cost', 'current_value', 'location', 'condition', 'assigned_to'
    ];

    protected $casts = ['purchase_date' => 'date'];

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
