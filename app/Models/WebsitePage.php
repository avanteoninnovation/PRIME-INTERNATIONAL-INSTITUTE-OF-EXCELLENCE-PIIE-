<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsitePage extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_key',
        'title',
        'slug',
        'status',
        'sort_order',
        'created_by',
        'updated_by',
    ];
}
