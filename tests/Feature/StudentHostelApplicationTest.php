<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * Security Phase 2I — student hostel applications.
 *
 * The student application list already offers Edit and Delete while an
 * application is pending (status 0), and edit.blade.php posts to the update
 * route and loads rooms from student/hostel-applications/get-rooms/{hostel};
 * none of those four controller actions existed. They are restored with
 * exactly that behaviour: own application, own school, pending only, with
 * the same validation as applicationStore(). Once staff approve or reject,
 * the application is staff-controlled and the student can no longer change it.
 */
class StudentHostelApplicationTest extends TestCase
{
    use StaffModuleTestHelper;

    private array $A;
    private array $B;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        foreach ([
            'hostels' => ['school_id', 'name', 'type', 'address', 'warden_id', 'fee'],
            'hostel_rooms' => ['school_id', 'hostel_id', 'room_no', 'capacity', 'occupied', 'seat_fee', 'description', 'status'],
            'hostel_applications' => ['school_id', 'student_id', 'hostel_id', 'room_id', 'status', 'note', 'accepted_at'],
            'hostel_room_allocations' => ['school_id', 'student_id', 'room_id', 'allocated_on', 'vacated_on', 'status'],
        ] as $table => $columns) {
            if (!Schema::hasTable($table)) {
                Schema::create($table, function (Blueprint $t) use ($columns) {
                    $t->id();
                    foreach ($columns as $c) $t->text($c)->nullable();
                    $t->timestamps();
                });
            }
        }
        $this->A = $this->world('A');
        $this->B = $this->world('B');
    }

    private function world(string $tag): array
    {
        $school = $this->makeSchool(['title' => "School {$tag}", 'status' => 1]);
        $hostel = DB::table('hostels')->insertGetId(['school_id' => $school, 'name' => "Hostel{$tag} Zq", 'type' => 1]);
        $room = fn (string $no) => DB::table('hostel_rooms')->insertGetId(['school_id' => $school, 'hostel_id' => $hostel, 'room_no' => "{$tag}{$no}", 'capacity' => 4, 'occupied' => 0, 'seat_fee' => 100, 'status' => 1]);
        $w = ['school' => $school, 'hostel' => $hostel, 'room' => $room('1'), 'room2' => $room('2'),
              'student' => User::factory()->create(['role_id' => 7, 'school_id' => $school, 'account_status' => 'active', 'name' => "Pupil{$tag}"])];
        $w['application'] = $this->application($w, 0);

        return $w;
    }

    private function application(array $w, int $status, ?User $student = null): int
    {
        return (int) DB::table('hostel_applications')->insertGetId(['school_id' => $w['school'], 'student_id' => ($student ?? $w['student'])->id,
            'hostel_id' => $w['hostel'], 'room_id' => $w['room'], 'status' => $status, 'note' => 'original']);
    }

    private function row(int $id): array
    {
        return (array) DB::table('hostel_applications')->where('id', $id)->first();
    }

    public function test_a_student_can_edit_update_and_delete_their_own_pending_application(): void
    {
        $student = $this->A['student'];
        $id = $this->A['application'];

        $edit = $this->actingAs($student)->get(route('student.hostel.applications.edit', $id));
        $edit->assertOk();
        $edit->assertSee('HostelA Zq');
        $edit->assertDontSee('HostelB Zq');

        $rooms = $this->actingAs($student)->getJson(url('student/hostel-applications/get-rooms/' . $this->A['hostel']));
        $rooms->assertOk();
        $this->assertEqualsCanonicalizing([$this->A['room'], $this->A['room2']], collect($rooms->json())->pluck('id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(['id', 'room_no', 'capacity', 'occupied', 'seat_fee'], array_keys($rooms->json()[0]));

        $this->actingAs($student)->post(route('student.hostel.applications.update', $id), ['hostel_id' => $this->A['hostel'], 'room_id' => $this->A['room2'], 'note' => 'changed'])
            ->assertSessionHasNoErrors();
        $this->assertEquals($this->A['room2'], $this->row($id)['room_id']);
        $this->assertSame('changed', $this->row($id)['note']);
        $this->assertEquals(0, $this->row($id)['status'], 'editing keeps the application pending');

        $this->actingAs($student)->get(route('student.hostel.applications.delete', $id))->assertRedirect();
        $this->assertNull(DB::table('hostel_applications')->where('id', $id)->first());
    }

    public function test_approved_or_rejected_applications_are_staff_controlled(): void
    {
        foreach ([1, 2] as $status) {
            $id = $this->application($this->A, $status);
            $before = $this->row($id);

            $this->actingAs($this->A['student'])->get(route('student.hostel.applications.edit', $id))->assertNotFound();
            $this->actingAs($this->A['student'])->post(route('student.hostel.applications.update', $id), ['hostel_id' => $this->A['hostel'], 'room_id' => $this->A['room2'], 'note' => 'x'])->assertNotFound();
            $this->actingAs($this->A['student'])->get(route('student.hostel.applications.delete', $id))->assertNotFound();

            $this->assertEquals($before, $this->row($id), "status {$status} application changed by student");
        }
    }

    public function test_a_student_cannot_touch_another_students_or_another_schools_application(): void
    {
        $classmate = User::factory()->create(['role_id' => 7, 'school_id' => $this->A['school'], 'account_status' => 'active']);
        foreach ([$this->application($this->A, 0, $classmate), $this->B['application']] as $id) {
            $before = $this->row($id);

            $this->actingAs($this->A['student'])->get(route('student.hostel.applications.edit', $id))->assertNotFound();
            $this->actingAs($this->A['student'])->post(route('student.hostel.applications.update', $id), ['hostel_id' => $this->A['hostel'], 'room_id' => $this->A['room2'], 'note' => 'x'])->assertNotFound();
            $this->actingAs($this->A['student'])->get(route('student.hostel.applications.delete', $id))->assertNotFound();

            $this->assertEquals($before, $this->row($id));
        }
    }

    public function test_an_update_cannot_move_an_application_to_another_school_or_a_full_room(): void
    {
        $id = $this->A['application'];
        $before = $this->row($id);

        // School B hostel/room.
        $this->actingAs($this->A['student'])->post(route('student.hostel.applications.update', $id), ['hostel_id' => $this->B['hostel'], 'room_id' => $this->B['room'], 'note' => 'x'])
            ->assertSessionHasErrors(['hostel_id', 'room_id']);
        // Room from a different hostel than the one chosen.
        $otherHostel = DB::table('hostels')->insertGetId(['school_id' => $this->A['school'], 'name' => 'Annex', 'type' => 1]);
        $this->actingAs($this->A['student'])->post(route('student.hostel.applications.update', $id), ['hostel_id' => $otherHostel, 'room_id' => $this->A['room2'], 'note' => 'x']);
        // Full room.
        DB::table('hostel_rooms')->where('id', $this->A['room2'])->update(['occupied' => 4]);
        $this->actingAs($this->A['student'])->post(route('student.hostel.applications.update', $id), ['hostel_id' => $this->A['hostel'], 'room_id' => $this->A['room2'], 'note' => 'x']);

        $this->assertEquals($before, $this->row($id));

        // Rooms of another school's hostel are not listed.
        $this->actingAs($this->A['student'])->getJson(url('student/hostel-applications/get-rooms/' . $this->B['hostel']))->assertOk()->assertExactJson([]);
    }

    public function test_the_application_form_only_offers_the_students_own_school(): void
    {
        $response = $this->actingAs($this->A['student'])->get(route('student.hostel.applications.create'));

        $response->assertOk();
        $response->assertSee('HostelA Zq');
        $response->assertDontSee('HostelB Zq');
    }
}
