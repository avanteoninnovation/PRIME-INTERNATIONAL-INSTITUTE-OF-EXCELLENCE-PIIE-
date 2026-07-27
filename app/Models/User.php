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
        'language',
        'school_role',
        'account_status',
        'force_password_change'
    ];

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
