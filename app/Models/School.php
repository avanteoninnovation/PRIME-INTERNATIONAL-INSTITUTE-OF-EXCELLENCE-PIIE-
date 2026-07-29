<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'email', 'phone', 'address', 'school_info', 'status','school_currency','currency_position',
        'school_type', 'education_level',
    ];

    /**
     * schools.school_type ('k12'/'higher_ed'/'mixed') doubles as this app's
     * "academic_structure" configuration — the column is reused as-is
     * (rather than adding a new one) to avoid an unnecessary migration.
     * This map is the single source of truth for what those column values
     * mean functionally: which academic structure (class-based vs
     * programme-based vs both) a school is configured for. Consult
     * academicStructure() rather than reading school_type directly when the
     * question is "which structure does this school use," so the mapping
     * only needs to change in one place.
     */
    public const ACADEMIC_STRUCTURE_MAP = [
        'k12' => 'class_based',
        'higher_ed' => 'programme_based',
        'mixed' => 'mixed',
    ];

    public function academicStructure(): string
    {
        return self::ACADEMIC_STRUCTURE_MAP[$this->school_type] ?? 'class_based';
    }
}
