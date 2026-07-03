<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;
    protected $table = 'payroll';
    protected $fillable = [
        'school_id', 'staff_id', 'pay_period', 'basic_salary', 'allowances',
        'deductions', 'tax', 'nssf', 'net_pay', 'payment_method',
        'bank_account', 'status', 'paid_at', 'approved_by'
    ];

    protected $casts = [
        'pay_period' => 'date',
        'paid_at'    => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function salaryStructure()
    {
        return SalaryStructure::where('user_id', $this->staff_id)
            ->where('school_id', $this->school_id)
            ->orderByDesc('effective_from')
            ->first();
    }
}
