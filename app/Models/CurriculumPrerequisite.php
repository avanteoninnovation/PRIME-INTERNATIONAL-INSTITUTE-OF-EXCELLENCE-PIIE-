<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;

class CurriculumPrerequisite extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;
    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::saving(function (self $edge): void {
            if ((int) $edge->membership_id === (int) $edge->prerequisite_membership_id) {
                throw new DomainException('A Curriculum membership cannot be its own prerequisite.');
            }

            if (Curriculum::where('school_id', $edge->school_id)->whereKey($edge->curriculum_id)->value('status') !== 'draft') {
                throw new DomainException('Prerequisites may only be changed while their Curriculum is draft.');
            }
        });

        static::deleting(function (self $edge): void {
            if (Curriculum::where('school_id', $edge->school_id)->whereKey($edge->curriculum_id)->value('status') !== 'draft') {
                throw new DomainException('Prerequisites may only be changed while their Curriculum is draft.');
            }
        });
    }

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function membership()
    {
        return $this->belongsTo(CurriculumMembership::class, 'membership_id');
    }

    public function prerequisiteMembership()
    {
        return $this->belongsTo(CurriculumMembership::class, 'prerequisite_membership_id');
    }
}
