<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_key',
        'item_type',
        'title',
        'subtitle',
        'description',
        'content',
        'image',
        'link',
        'button_text',
        'status',
        'sort_order',
        'meta_json',
    ];
}
