<?php

namespace Tests\Feature;

use App\Models\LiveClassMeetGuest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Support\LiveClassTestHelper;
use Tests\TestCase;

/**
 * Covers the admin-managed Google Meet "attendee" guest list
 * (LiveClassMeetGuest, LiveClassController::meetGuests/storeMeetGuest/
 * destroyMeetGuest) and its wiring into createGoogleMeetUrl() — added
 * because Google Meet events this app created never invited anyone, so
 * every joiner (including the teacher running the class) was an anonymous
 * link-holder to Google and had to "Ask to join" regardless of which
 * Google account they were signed into.
 *
 * Uses the same dedicated hand-rolled sqlite schema as
 * LiveClassAttendanceMaterialsRemindersTest, deliberately NOT
 * LiveClassModuleTest's real-database pattern.
 */
class LiveClassMeetGuestsTest extends TestCase
{
    use LiveClassTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootLiveClassTestSchema();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_admin_can_add_and_list_a_meet_guest_email(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeStaffUser($schoolId, 2);

        $response = $this->actingAs($admin)->post(route('admin.live_classes.meet_guests.store'), [
            'email' => 'teacher@gmail.com',
            'label' => 'Head Teacher',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('live_class_meet_guests', [
            'school_id' => $schoolId,
            'email' => 'teacher@gmail.com',
            'label' => 'Head Teacher',
        ]);

        $this->actingAs($admin)->get(route('admin.live_classes.meet_guests'))
            ->assertOk()
            ->assertSee('teacher@gmail.com');
    }

    public function test_duplicate_guest_email_for_the_same_school_is_rejected(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeStaffUser($schoolId, 2);

        LiveClassMeetGuest::create(['school_id' => $schoolId, 'email' => 'dup@gmail.com']);

        $this->actingAs($admin)->post(route('admin.live_classes.meet_guests.store'), [
            'email' => 'dup@gmail.com',
        ])->assertRedirect();

        $this->assertSame(1, LiveClassMeetGuest::where('school_id', $schoolId)->where('email', 'dup@gmail.com')->count());
    }

    public function test_admin_can_remove_a_meet_guest_email(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeStaffUser($schoolId, 2);
        $guest = LiveClassMeetGuest::create(['school_id' => $schoolId, 'email' => 'remove-me@gmail.com']);

        $this->actingAs($admin)->delete(route('admin.live_classes.meet_guests.destroy', $guest->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('live_class_meet_guests', ['id' => $guest->id]);
    }

    public function test_teacher_cannot_access_meet_guests_management(): void
    {
        $schoolId = $this->makeSchool();
        $teacher = $this->makeStaffUser($schoolId, 3);

        $this->actingAs($teacher)->get(route('admin.live_classes.meet_guests'))->assertStatus(403);
        $this->actingAs($teacher)->post(route('admin.live_classes.meet_guests.store'), ['email' => 'x@gmail.com'])->assertStatus(403);
    }

    public function test_admin_from_another_school_only_sees_their_own_guests(): void
    {
        $schoolA = $this->makeSchool();
        $schoolB = $this->makeSchool();
        $adminA = $this->makeStaffUser($schoolA, 2);
        LiveClassMeetGuest::create(['school_id' => $schoolB, 'email' => 'other-school@gmail.com']);

        $this->actingAs($adminA)->get(route('admin.live_classes.meet_guests'))
            ->assertOk()
            ->assertDontSee('other-school@gmail.com');
    }

    public function test_google_meet_event_creation_includes_configured_guests_as_attendees(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeStaffUser($schoolId, 2);
        $classId = $this->makeClass($schoolId);

        LiveClassMeetGuest::create(['school_id' => $schoolId, 'email' => 'guest-one@gmail.com']);
        LiveClassMeetGuest::create(['school_id' => $schoolId, 'email' => 'guest-two@gmail.com']);

        Config::set('services.google_meet.client_id', 'test-client-id');
        Config::set('services.google_meet.client_secret', 'test-client-secret');
        Config::set('services.google_meet.refresh_token', 'test-refresh-token');
        Config::set('services.google_meet.calendar_id', 'primary');

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-access-token'], 200),
            'www.googleapis.com/calendar/v3/*' => Http::response([
                'hangoutLink' => 'https://meet.google.com/fake-room',
            ], 200),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.live_classes.store'), [
            'title' => 'Guest-Invited Class',
            'class_id' => $classId,
            'platform' => 'google_meet',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'timezone' => 'UTC',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('live_classes', [
            'title' => 'Guest-Invited Class',
            'meeting_url' => 'https://meet.google.com/fake-room',
        ]);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), 'www.googleapis.com/calendar/v3/calendars/primary/events')) {
                return true; // not the request we care about — don't fail the assertion on it
            }

            $emails = collect($request['attendees'] ?? [])->pluck('email')->all();

            return str_contains($request->url(), 'sendUpdates=all')
                && in_array('guest-one@gmail.com', $emails, true)
                && in_array('guest-two@gmail.com', $emails, true);
        });
    }

    public function test_google_meet_event_creation_works_with_no_guests_configured(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeStaffUser($schoolId, 2);
        $classId = $this->makeClass($schoolId);

        Config::set('services.google_meet.client_id', 'test-client-id');
        Config::set('services.google_meet.client_secret', 'test-client-secret');
        Config::set('services.google_meet.refresh_token', 'test-refresh-token');
        Config::set('services.google_meet.calendar_id', 'primary');

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-access-token'], 200),
            'www.googleapis.com/calendar/v3/*' => Http::response([
                'hangoutLink' => 'https://meet.google.com/no-guests-room',
            ], 200),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.live_classes.store'), [
            'title' => 'No Guests Class',
            'class_id' => $classId,
            'platform' => 'google_meet',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'timezone' => 'UTC',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('live_classes', [
            'title' => 'No Guests Class',
            'meeting_url' => 'https://meet.google.com/no-guests-room',
        ]);
    }
}
