<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionStatusEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'admission_id', 'from_status', 'to_status', 'title',
        'note', 'actor_type', 'actor_id', 'actor_name', 'is_visible_to_applicant',
    ];

    protected $casts = [
        'is_visible_to_applicant' => 'boolean',
    ];

    public function admission()
    {
        return $this->belongsTo(Admission::class, 'admission_id');
    }
}
