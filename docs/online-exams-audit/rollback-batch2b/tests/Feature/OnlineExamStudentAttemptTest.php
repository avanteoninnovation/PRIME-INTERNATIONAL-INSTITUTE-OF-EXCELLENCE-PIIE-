<?php

namespace Tests\Feature;

use Tests\Feature\Support\OnlineExamTestHelper;
use Tests\TestCase;

class OnlineExamStudentAttemptTest extends TestCase
{
    use OnlineExamTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootOnlineExamTestSchema();
    }

    public function test_student_cannot_access_draft_exam(): void
    {
        $student = $this->makeUser(7, 1);
        $classId = $this->makeClass(1);
        $subjectId = $this->makeSubject(1, $classId);
        $this->enrollStudent($student->id, 1, $classId);

        $examId = $this->makeExam([
            'school_id' => 1,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'is_published' => 0,
            'workflow_state' => 'draft',
        ]);

        $this->actingAs($student)
            ->get(route('student.online_exam.take', $examId))
            ->assertStatus(404);
    }

    public function test_student_cannot_access_unassigned_exam(): void
    {
        $student = $this->makeUser(7, 1);
        $studentClass = $this->makeClass(1);
        $otherClass = $this->makeClass(1);
        $subjectId = $this->makeSubject(1, $otherClass);
        $this->enrollStudent($student->id, 1, $studentClass);

        $examId = $this->makeExam([
            'school_id' => 1,
            'class_id' => $otherClass,
            'subject_id' => $subjectId,
            'workflow_state' => 'published',
            'is_published' => 1,
        ]);

        $this->actingAs($student)
            ->get(route('student.online_exam.take', $examId))
            ->assertStatus(404);
    }

    public function test_student_cannot_start_before_start_time_or_after_end_time(): void
    {
        $student = $this->makeUser(7, 1);
        $classId = $this->makeClass(1);
        $subjectId = $this->makeSubject(1, $classId);
        $this->enrollStudent($student->id, 1, $classId);

        $futureExam = $this->makeExam([
            'school_id' => 1,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'start_datetime' => now()->addHour(),
            'end_datetime' => now()->addHours(2),
        ]);

        $pastExam = $this->makeExam([
            'school_id' => 1,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'start_datetime' => now()->subHours(2),
            'end_datetime' => now()->subHour(),
        ]);

        $this->actingAs($student)
            ->postJson(route('student.online_exam.start', $futureExam), [])
            ->assertStatus(422);

        $this->actingAs($student)
            ->postJson(route('student.online_exam.start', $pastExam), [])
            ->assertStatus(422);
    }

    public function test_student_cannot_exceed_max_attempts_and_duplicate_active_attempt_is_blocked(): void
    {
        $student = $this->makeUser(7, 1);
        $classId = $this->makeClass(1);
        $subjectId = $this->makeSubject(1, $classId);
        $this->enrollStudent($student->id, 1, $classId);

        $examId = $this->makeExam([
            'school_id' => 1,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'max_attempts' => 1,
        ]);

        $this->makeSubmission([
            'online_exam_id' => $examId,
            'student_id' => $student->id,
            'school_id' => 1,
            'attempt_no' => 1,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($student)
            ->postJson(route('student.online_exam.start', $examId), [])
            ->assertStatus(422);

        $examId2 = $this->makeExam([
            'school_id' => 1,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'max_attempts' => 2,
        ]);

        $this->makeSubmission([
            'online_exam_id' => $examId2,
            'student_id' => $student->id,
            'school_id' => 1,
            'attempt_no' => 1,
            'status' => 'in_progress',
        ]);

        $this->actingAs($student)
            ->postJson(route('student.online_exam.start', $examId2), [])
            ->assertStatus(422);
    }

    public function test_student_cannot_access_another_students_submission_and_result_route_uses_submission_id(): void
    {
        $studentA = $this->makeUser(7, 1);
        $studentB = $this->makeUser(7, 1);

        $classId = $this->makeClass(1);
        $subjectId = $this->makeSubject(1, $classId);
        $this->enrollStudent($studentA->id, 1, $classId);
        $this->enrollStudent($studentB->id, 1, $classId);

        $examId = $this->makeExam([
            'school_id' => 1,
            'class_id' => $classId,
            'subject_id' => $subjectId,
        ]);

        $submissionA = $this->makeSubmission([
            'online_exam_id' => $examId,
            'student_id' => $studentA->id,
            'school_id' => 1,
            'status' => 'finalized',
            'submitted_at' => now(),
        ]);

        $this->actingAs($studentB)
            ->get(route('student.online_exam.result', $submissionA))
            ->assertStatus(404);

        $this->actingAs($studentA)
            ->get(route('student.online_exam.result', 999999))
            ->assertStatus(404);
    }
}
