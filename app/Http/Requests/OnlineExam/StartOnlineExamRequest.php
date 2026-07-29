<?php

namespace App\Http\Requests\OnlineExam;

use App\Models\Enrollment;
use App\Models\OnlineExam;
use App\Models\OnlineExamSubmission;
use App\Support\Permissions\OnlineExamPermissionService;
use Illuminate\Foundation\Http\FormRequest;

class StartOnlineExamRequest extends FormRequest
{
    private ?OnlineExam $exam = null;

    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user || (int) $user->role_id !== 7) {
            return false;
        }

        if (!app(OnlineExamPermissionService::class)->has($user, 'sit_online_exams')) {
            return false;
        }

        $id = (int) ($this->route('id') ?? $this->route('exam') ?? 0);
        $this->exam = OnlineExam::find($id);

        return (bool) $this->exam;
    }

    public function rules(): array
    {
        return [
            'camera_consent_accepted' => ['nullable', 'boolean'],
            'camera_ready' => ['nullable', 'boolean'],
            'fullscreen_ready' => ['nullable', 'boolean'],
            'browser_session_token' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $exam = $this->exam;
            if (!$user || !$exam) {
                return;
            }

            if ((int) $user->school_id !== (int) $exam->school_id) {
                $validator->errors()->add('exam', 'You are not authorized for this exam school scope.');
                return;
            }

            if ($exam->workflow_state !== 'published') {
                $validator->errors()->add('exam', 'Exam is not published.');
            }

            if ($exam->workflow_state === 'cancelled') {
                $validator->errors()->add('exam', 'Exam has been cancelled.');
            }

            $now = now();
            if ($exam->start_datetime && $now->lt($exam->start_datetime)) {
                $validator->errors()->add('exam', 'Exam has not started yet.');
            }

            if ($exam->end_datetime && $now->gt($exam->end_datetime)) {
                $validator->errors()->add('exam', 'Exam has ended.');
            }

            // Only class-scoped exams require a matching Enrollment — a null
            // class_id exam is visible/sittable by any student in the school
            // (see OnlineExam::scopeVisibleToStudent()), including
            // programme-based (HEI) students who have no Enrollment row at
            // all and use StudentProfile instead.
            if (!empty($exam->class_id)) {
                $enrollment = Enrollment::where('user_id', $user->id)
                    ->where('school_id', $user->school_id)
                    ->first();

                if (!$enrollment || (int) $exam->class_id !== (int) $enrollment->class_id) {
                    $validator->errors()->add('exam', 'You are not assigned to this exam class.');
                }
            }

            $attemptCount = OnlineExamSubmission::where('online_exam_id', $exam->id)
                ->where('student_id', $user->id)
                ->count();

            if ($attemptCount >= (int) $exam->max_attempts) {
                $validator->errors()->add('exam', 'Maximum attempts exceeded.');
            }

            $activeAttemptExists = OnlineExamSubmission::where('online_exam_id', $exam->id)
                ->where('student_id', $user->id)
                ->where('status', OnlineExamSubmission::STATUS_IN_PROGRESS)
                ->exists();

            if ($activeAttemptExists) {
                $validator->errors()->add('exam', 'An active attempt already exists.');
            }

            if ($exam->webcam_required) {
                if (!$this->boolean('camera_consent_accepted')) {
                    $validator->errors()->add('camera_consent_accepted', 'Camera consent is required for this exam.');
                }

                if (!$this->boolean('camera_ready')) {
                    $validator->errors()->add('camera_ready', 'Camera readiness is required for this exam.');
                }
            }

            if ($exam->fullscreen_required && !$this->boolean('fullscreen_ready')) {
                $validator->errors()->add('fullscreen_ready', 'Fullscreen readiness is required for this exam.');
            }
        });
    }
}
