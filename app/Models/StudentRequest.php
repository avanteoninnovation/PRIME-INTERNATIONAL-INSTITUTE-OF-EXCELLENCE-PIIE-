<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentRequest extends Model
{
    public const TYPE_TRANSFER = 'transfer';
    public const TYPE_COMPLAINT = 'complaint';
    public const TYPE_FEE_DISCOUNT_APPEAL = 'fee_discount_appeal';

    public const TYPES = [
        self::TYPE_TRANSFER => 'Transfer Application',
        self::TYPE_COMPLAINT => 'Complaint',
        self::TYPE_FEE_DISCOUNT_APPEAL => 'Fee Discount Appeal',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TRANSFER_TYPES = [
        'change_of_programme' => 'Change of Programme',
        'change_of_campus' => 'Change of Campus / Study Centre',
        'inter_institution' => 'Inter-Institution Transfer',
    ];

    public const TRANSFER_REASONS = [
        'relocation' => 'Relocation',
        'financial' => 'Financial Reasons',
        'career_change' => 'Change of Career Interest',
        'academic_performance' => 'Academic Performance',
        'other' => 'Other',
    ];

    protected $fillable = [
        'student_id', 'school_id', 'type', 'subject', 'details',
        'status', 'admin_response', 'reviewed_by', 'reviewed_at',
        'transfer_type', 'transfer_to_programme_id', 'transfer_reason',
        'phone_number', 'document_path',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function transferToProgramme()
    {
        return $this->belongsTo(Programme::class, 'transfer_to_programme_id');
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }
}
