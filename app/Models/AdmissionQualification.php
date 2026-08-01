<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionQualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'admission_id', 'institution', 'award', 'subject',
        'grade', 'start_year', 'end_year', 'country',
    ];

    public function admission()
    {
        return $this->belongsTo(Admission::class, 'admission_id');
    }

    public function getPeriodAttribute(): string
    {
        if ($this->start_year && $this->end_year) {
            return "{$this->start_year} – {$this->end_year}";
        }

        return (string) ($this->end_year ?: $this->start_year ?: '');
    }
}
