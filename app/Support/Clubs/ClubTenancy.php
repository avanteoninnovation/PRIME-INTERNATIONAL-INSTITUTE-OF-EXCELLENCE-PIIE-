<?php

namespace App\Support\Clubs;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\ClubNotice;
use Illuminate\Database\Eloquent\Builder;

/**
 * Security Phase 2H: a club belongs to exactly one school (clubs.school_id),
 * and its members and notices belong to that school through the club.
 *
 * Every club, member or notice reached by an id — from the route, a bound
 * model or the request — goes through here, so a user of one school gets a
 * 404 for another school's club rather than its data. The caller's own
 * school_id is always authoritative; a submitted school_id is never read.
 */
class ClubTenancy
{
    public static function schoolId(): int
    {
        return (int) auth()->user()->school_id;
    }

    /** Clubs of the current user's school. */
    public static function clubs(): Builder
    {
        return Club::where('school_id', self::schoolId());
    }

    public static function findClubOrFail($id): Club
    {
        return self::clubs()->findOrFail($id);
    }

    /** Creates a club owned by the current user's school (school_id is not mass-assignable). */
    public static function createClub(array $attributes): Club
    {
        $club = new Club($attributes);
        $club->school_id = self::schoolId();
        $club->save();

        return $club;
    }

    /** For route-model-bound clubs, which Laravel resolves without a school filter. */
    public static function assertOwned(Club $club): Club
    {
        abort_unless((int) $club->school_id === self::schoolId(), 404);

        return $club;
    }

    public static function findMemberOrFail($id): ClubMember
    {
        return ClubMember::whereHas('club', fn ($q) => $q->where('school_id', self::schoolId()))->findOrFail($id);
    }

    public static function findNoticeOrFail($id): ClubNotice
    {
        return ClubNotice::whereHas('club', fn ($q) => $q->where('school_id', self::schoolId()))->findOrFail($id);
    }

    /** Validation rule: a user id (advisor / member) must belong to the current user's school. */
    public static function schoolUserRule(): string
    {
        return 'exists:users,id,school_id,' . self::schoolId();
    }
}
