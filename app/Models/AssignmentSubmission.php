<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    use HasFactory;
    protected $table = 'assignment_submissions';
    protected $fillable = [
        'assignment_id', 'student_id', 'submission', 'file_path',
        'link', 'submitted_at', 'marks_awarded', 'feedback', 'status'
    ];

    protected $casts = ['submitted_at' => 'datetime'];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
