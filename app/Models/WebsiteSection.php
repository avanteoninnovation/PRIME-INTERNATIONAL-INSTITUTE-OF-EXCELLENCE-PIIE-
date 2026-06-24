<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_key',
        'section_key',
        'title',
        'subtitle',
        'content',
        'extra_json',
        'image',
        'status',
        'sort_order',
    ];
}
