<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Tests\Feature\Support\AuditTestHelper;
use Tests\TestCase;

class AuditLogControllerTest extends TestCase
{
    use AuditTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAuditTestSchema();
    }

    private function makeLog(int $schoolId, array $overrides = []): AuditLog
    {
        return AuditLog::create(array_merge([
            'school_id'   => $schoolId,
            'user_id'     => 1,
            'user_name'   => 'Someone',
            'action'      => 'CREATE',
            'event_type'  => 'DATA',
            'module'      => 'Students',
            'description' => 'Created a student',
            'ip_address'  => '203.0.113.1',
            'created_at'  => now(),
        ], $overrides));
    }

    public function test_teacher_cannot_access_audit_log_page(): void
    {
        $teacher = $this->makeUser(3, 1);

        $response = $this->actingAs($teacher)->get(route('admin.audit_log.index'));

        $response->assertStatus(403);
    }

    public function test_school_admin_cannot_view_another_schools_log_via_direct_id(): void
    {
        $adminSchool1 = $this->makeUser(2, 1);
        $foreignLog   = $this->makeLog(2, ['description' => 'Belongs to school two']);

        $response = $this->actingAs($adminSchool1)->get(route('admin.audit_log.show', $foreignLog->id));

        $response->assertStatus(403);
    }

    public function test_school_admin_can_view_own_schools_log_detail(): void
    {
        $admin = $this->makeUser(2, 1);
        $log   = $this->makeLog(1, ['description' => 'Belongs to school one']);

        $response = $this->actingAs($admin)->get(route('admin.audit_log.show', $log->id));

        $response->assertOk();
        $response->assertSee('Belongs to school one');
    }

    public function test_school_admin_index_query_is_scoped_to_own_school_only(): void
    {
        $admin = $this->makeUser(2, 1);
        $this->makeLog(1, ['description' => 'Own school entry']);
        $this->makeLog(2, ['description' => 'Other school entry']);

        $response = $this->actingAs($admin)->get(route('admin.audit_log.index'));

        $response->assertOk();
        $response->assertSee('Own school entry');
        $response->assertDontSee('Other school entry');
    }

    public function test_super_admin_sees_logs_across_all_schools_and_can_filter_by_school(): void
    {
        $superAdmin = $this->makeUser(1, 1);
        $this->makeLog(1, ['description' => 'Entry from school one']);
        $this->makeLog(2, ['description' => 'Entry from school two']);

        $all = $this->actingAs($superAdmin)->get(route('superadmin.audit_log.index'));
        $all->assertOk();
        $all->assertSee('Entry from school one');
        $all->assertSee('Entry from school two');

        $filtered = $this->actingAs($superAdmin)->get(route('superadmin.audit_log.index', ['school_id' => 2]));
        $filtered->assertOk();
        $filtered->assertSee('Entry from school two');
        $filtered->assertDontSee('Entry from school one');
    }

    public function test_super_admin_can_view_any_schools_log_detail(): void
    {
        $superAdmin = $this->makeUser(1, 1);
        $log        = $this->makeLog(2, ['description' => 'Cross tenant detail']);

        $response = $this->actingAs($superAdmin)->get(route('superadmin.audit_log.show', $log->id));

        $response->assertOk();
        $response->assertSee('Cross tenant detail');
    }
}
