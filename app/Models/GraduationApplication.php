<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GraduationApplication extends Model
{
    use HasFactory;
    protected $table = 'graduation_applications';
    protected $fillable = [
        'school_id', 'student_id', 'programme_id', 'cgpa', 'classification',
        'fees_cleared', 'academics_cleared', 'ceremony_year', 'status', 'reviewed_by'
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class, 'programme_id');
    }
}
