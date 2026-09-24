<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    protected $fillable = [
        'school_name',
        'club_name',
        'description',
        'advisor_id',
        'status'
    ];

    public function advisor()
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    public function members()
    {
        return $this->hasMany(ClubMember::class);
    }
    // school_id is deliberately not fillable: it is always set from the
    // creator's own school (Security Phase 2H), never from request input.
    public function school()
{
    return $this->belongsTo(\App\Models\School::class, 'school_id');
}

}
