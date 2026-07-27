<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Programme extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'code', 'name', 'level', 'duration', 'mode',
        'tuition_fee', 'department_id', 'is_active'
    ];

    /**
     * Client-preferred Level/Mode option lists, shown first in dropdowns.
     * LEGACY_* values are kept selectable (never removed from the DB enum)
     * so existing programmes using them don't lose data — see migration
     * 2026_07_27_050000_normalize_programme_levels_modes_and_code_uniqueness.
     */
    public const LEVELS = ['Certificate', 'Diploma', 'Bachelors', 'PGD', 'Masters', 'Short Course'];
    public const LEVELS_LEGACY = ['Degree', 'PhD'];
    public const MODES = ['ODEL', 'Full Time', 'Weekend'];
    public const MODES_LEGACY = ['fulltime', 'parttime', 'online', 'blended'];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class, 'programme_id');
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class, 'programme_id');
    }

    public function liveClasses()
    {
        return $this->hasMany(LiveClass::class, 'programme_id');
    }

    /**
     * The one reliable "currently enrolled" signal — see StudentProfile,
     * which is kept in sync at student-creation/enrollment time (unlike
     * Admission.programme_id, which reflects the original application and
     * is never updated afterward).
     */
    public function studentProfiles()
    {
        return $this->hasMany(StudentProfile::class, 'programme_id');
    }

    public function activeStudentCount(): int
    {
        return $this->studentProfiles()->where('status', 'active')->count();
    }
}
