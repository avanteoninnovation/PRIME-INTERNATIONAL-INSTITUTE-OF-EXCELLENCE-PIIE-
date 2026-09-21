<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserNotification;
use App\Support\Notifications\NotificationService;
use App\Support\Permissions\RoleNavigationLayout;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers the new per-user notification inbox (UserNotification,
 * NotificationService, NotificationController, the bell partial included in
 * every role's navigation layout) — added because this app previously had
 * no way for a user to see "what happened while I was away": the
 * school-wide Noticeboard has no per-user read state, and email is silently
 * skipped whenever SMTP isn't configured, with no other channel at all.
 */
class NotificationCenterTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    public function test_notify_creates_a_single_unread_notification(): void
    {
        $schoolId = $this->makeSchool();
        $student = User::factory()->create(['role_id' => 7, 'school_id' => $schoolId]);

        NotificationService::notify($student->id, $schoolId, 'Test Title', 'Test body', '/somewhere', 'general');

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $student->id,
            'school_id' => $schoolId,
            'title' => 'Test Title',
            'body' => 'Test body',
            'url' => '/somewhere',
            'type' => 'general',
        ]);
        $this->assertNull(UserNotification::first()->read_at);
    }

    public function test_notify_many_creates_one_row_per_recipient(): void
    {
        $schoolId = $this->makeSchool();
        $students = User::factory()->count(3)->create(['role_id' => 7, 'school_id' => $schoolId]);

        $created = NotificationService::notifyMany($students->pluck('id'), $schoolId, 'Bulk Title', 'Bulk body');

        $this->assertSame(3, $created);
        $this->assertSame(3, UserNotification::where('title', 'Bulk Title')->count());
    }

    public function test_notify_many_with_no_recipients_does_nothing(): void
    {
        $schoolId = $this->makeSchool();

        $created = NotificationService::notifyMany(collect(), $schoolId, 'Nobody', null);

        $this->assertSame(0, $created);
        $this->assertSame(0, UserNotification::count());
    }

    public function test_a_user_only_sees_their_own_notifications(): void
    {
        $schoolId = $this->makeSchool();
        $studentA = User::factory()->create(['role_id' => 7, 'school_id' => $schoolId]);
        $studentB = User::factory()->create(['role_id' => 7, 'school_id' => $schoolId]);
        NotificationService::notify($studentA->id, $schoolId, 'For A', null);
        NotificationService::notify($studentB->id, $schoolId, 'For B', null);

        $response = $this->actingAs($studentA)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('For A');
        $response->assertDontSee('For B');
    }

    public function test_marking_read_updates_read_at_and_redirects_to_its_url(): void
    {
        $schoolId = $this->makeSchool();
        $student = User::factory()->create(['role_id' => 7, 'school_id' => $schoolId]);
        $notification = NotificationService::notify($student->id, $schoolId, 'Click me', null, '/target-page');

        $response = $this->actingAs($student)->post(route('notifications.read', $notification->id));

        $response->assertRedirect('/target-page');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_user_cannot_mark_another_users_notification_as_read(): void
    {
        $schoolId = $this->makeSchool();
        $studentA = User::factory()->create(['role_id' => 7, 'school_id' => $schoolId]);
        $studentB = User::factory()->create(['role_id' => 7, 'school_id' => $schoolId]);
        $notification = NotificationService::notify($studentB->id, $schoolId, 'Belongs to B', null);

        $this->actingAs($studentA)->post(route('notifications.read', $notification->id))->assertStatus(404);

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_only_affects_the_current_user(): void
    {
        $schoolId = $this->makeSchool();
        $studentA = User::factory()->create(['role_id' => 7, 'school_id' => $schoolId]);
        $studentB = User::factory()->create(['role_id' => 7, 'school_id' => $schoolId]);
        NotificationService::notify($studentA->id, $schoolId, 'A1', null);
        NotificationService::notify($studentA->id, $schoolId, 'A2', null);
        NotificationService::notify($studentB->id, $schoolId, 'B1', null);

        $this->actingAs($studentA)->post(route('notifications.read_all'));

        $this->assertSame(0, UserNotification::forUser($studentA->id)->unread()->count());
        $this->assertSame(1, UserNotification::forUser($studentB->id)->unread()->count());
    }

    public function test_unread_count_endpoint_reflects_read_state(): void
    {
        $schoolId = $this->makeSchool();
        $student = User::factory()->create(['role_id' => 7, 'school_id' => $schoolId]);
        $n1 = NotificationService::notify($student->id, $schoolId, 'One', null);
        NotificationService::notify($student->id, $schoolId, 'Two', null);

        $this->actingAs($student)->get(route('notifications.unread_count'))
            ->assertJson(['count' => 2]);

        $n1->update(['read_at' => now()]);

        $this->actingAs($student)->get(route('notifications.unread_count'))
            ->assertJson(['count' => 1]);
    }

    public function test_dropdown_endpoint_returns_recent_notifications_and_a_see_all_link(): void
    {
        $schoolId = $this->makeSchool();
        $student = User::factory()->create(['role_id' => 7, 'school_id' => $schoolId]);
        NotificationService::notify($student->id, $schoolId, 'Dropdown Item', null);

        $response = $this->actingAs($student)->get(route('notifications.dropdown'));

        $response->assertOk();
        $response->assertSee('Dropdown Item');
        $response->assertSee('See all notifications');
    }

    public function test_role_navigation_layout_maps_every_common_role_to_an_existing_view(): void
    {
        $cases = [7 => 'student.navigation', 3 => 'teacher.navigation', 2 => 'admin.navigation', 6 => 'parent.navigation'];

        foreach ($cases as $roleId => $expectedLayout) {
            $user = User::factory()->make(['role_id' => $roleId]);
            $this->assertSame($expectedLayout, RoleNavigationLayout::name($user));
            $this->assertTrue(view()->exists($expectedLayout), "View [{$expectedLayout}] must exist.");
        }
    }

    /**
     * The notifications index page itself extends the student navigation
     * layout, which @includes the shared bell partial (see
     * resources/views/notifications/_bell.blade.php) — this proves the bell
     * doesn't break the page it's embedded in for a role with zero
     * notifications, which is exactly what this page renders through.
     */
    public function test_the_notification_bell_renders_without_error_for_a_student_with_no_notifications(): void
    {
        $schoolId = $this->makeSchool();
        $student = User::factory()->create(['role_id' => 7, 'school_id' => $schoolId, 'account_status' => 'active']);

        $response = $this->actingAs($student)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('No notifications yet');
    }
}
