<?php

namespace App\Support\CourseOffering;

use App\Models\CourseOfferingLecturerAllocation;
use DomainException;

class CourseOfferingLecturerAllocationConflict extends DomainException
{
    public function __construct(
        public readonly CourseOfferingLecturerAllocation $conflictingAllocation,
        public readonly bool $primaryConflict
    ) {
        parent::__construct($primaryConflict
            ? 'A Primary Lecturer allocation overlaps these dates.'
            : 'This lecturer already has an allocation overlapping these dates.');
    }
}
