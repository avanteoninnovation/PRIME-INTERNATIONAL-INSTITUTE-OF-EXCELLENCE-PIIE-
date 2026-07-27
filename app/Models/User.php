<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'users';

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'role_id',
        'parent_id',
        'school_id',
        'code',
        'user_information',
        'student_info',
        'documents',
        'status',
        'department_id',
        'designation',
        'designation_id',
        'employment_type',
        'staff_status',
        'language',
        'school_role',
        'account_status',
        'force_password_change'
    ];

    /** Valid values for staff_status — the Staff Module's own employment status, separate from account_status. */
    public const STAFF_STATUSES = ['active', 'suspended', 'inactive'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'force_password_change' => 'boolean',
    ];

    public function checkEnrollment()
    {
        return $this->hasMany(Enrollment::class, 'user_id');
    }
    public function getTomoNameAttribute()
    {
        return $this->checkEnrollment()->class_id;
    }

    public function joinedClubs()
    {
        return $this->hasMany(ClubMember::class, 'student_id');
    }
    public function enrollment()
{
    return $this->hasOne(Enrollment::class, 'user_id');
}

    public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class, 'user_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function designationRecord()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    /**
     * Staff whose employment status is suspended/inactive must not access
     * the staff portal, mirroring how account_status='disable' already
     * works — kept as a separate field/check per the client's explicit
     * instruction not to conflate the two.
     */
    public function isStaffPortalBlocked(): bool
    {
        return in_array($this->staff_status, ['suspended', 'inactive'], true);
    }

        public function liveClassesAsLecturer()
        {
            return $this->hasMany(LiveClass::class, 'teacher_id');
        }

        public function createdLiveClasses()
        {
            return $this->hasMany(LiveClass::class, 'created_by');
        }

    /** Human-readable label for audit log entries (see AuditableObserver). */
    public function auditLabel(): string
    {
        $role = AuditLog::roleName($this->role_id);

        return trim(($role ? "{$role} " : '') . ($this->name ?? "User #{$this->id}"));
    }
}
