<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClubMember extends Model
{
    protected $fillable = ['club_id', 'student_id', 'status'];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
    

    public function club()
    {
        return $this->belongsTo(Club::class);
    }
}

