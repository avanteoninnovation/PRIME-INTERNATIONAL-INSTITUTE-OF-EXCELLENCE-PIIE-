<?php

namespace App\Http\Requests\OnlineExam;

use App\Models\OnlineExam;
use App\Models\Subject;
use App\Support\Permissions\OnlineExamPermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOnlineExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        if ((int) $user->role_id === 7) {
            return false;
        }

        return app(OnlineExamPermissionService::class)->has($user, 'create_online_exams');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')->where(fn($q) => $q->where('school_id', $this->user()->school_id)),
            ],
            'class_id' => [
                'nullable',
                'integer',
                Rule::exists('classes', 'id')->where(fn($q) => $q->where('school_id', $this->user()->school_id)),
            ],
            'exam_type' => ['required', 'string', Rule::in(['cat', 'midterm', 'final', 'quiz', 'assignment'])],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'duration_mins' => ['nullable', 'integer', 'min:1'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'total_marks' => ['required', 'integer', 'min:1'],
            'pass_mark' => ['required', 'integer', 'min:0'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
            'allow_previous_navigation' => ['nullable', 'boolean'],
            'result_release_policy' => ['required', Rule::in(['immediate', 'after_exam_end', 'manual'])],
            'webcam_required' => ['nullable', 'boolean'],
            'fullscreen_required' => ['nullable', 'boolean'],
            'auto_submit' => ['nullable', 'boolean'],
            'workflow_state' => ['nullable', Rule::in(['draft', 'pending_review', 'published', 'cancelled'])],
        ];
    }

    public function messages(): array
    {
        return [
            'end_datetime.after' => 'End datetime must be after start datetime.',
            'duration_mins.min' => 'Duration must be greater than zero.',
            'duration_minutes.min' => 'Duration must be greater than zero.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $duration = $this->input('duration_mins', $this->input('duration_minutes'));

        $this->merge([
            'duration_mins' => $duration,
            'shuffle_questions' => $this->boolean('shuffle_questions'),
            'shuffle_options' => $this->boolean('shuffle_options'),
            'allow_previous_navigation' => $this->has('allow_previous_navigation')
                ? $this->boolean('allow_previous_navigation')
                : true,
            'webcam_required' => $this->boolean('webcam_required'),
            'fullscreen_required' => $this->boolean('fullscreen_required'),
            'auto_submit' => $this->has('auto_submit') ? $this->boolean('auto_submit') : true,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            if (!$user) {
                return;
            }

            $duration = (int) $this->input('duration_mins');
            $start = strtotime((string) $this->input('start_datetime'));
            $end = strtotime((string) $this->input('end_datetime'));

            if ($start && $end && $duration > 0) {
                $windowMins = (int) floor(($end - $start) / 60);
                if ($windowMins <= 0 || $duration > $windowMins) {
                    $validator->errors()->add('duration_mins', 'Duration must fit inside the exam window.');
                }
            }

            if ((int) $this->input('pass_mark') > (int) $this->input('total_marks')) {
                $validator->errors()->add('pass_mark', 'Pass mark cannot exceed total marks.');
            }

            $permissionService = app(OnlineExamPermissionService::class);
            if ((int) $user->role_id === 3 && !$permissionService->teacherCanUseSubject($user, (int) $this->input('subject_id'))) {
                $validator->errors()->add('subject_id', 'You are not assigned to the selected subject/class.');
            }

            if ($this->filled('workflow_state') && !$permissionService->has($user, 'publish_online_exams')) {
                $requestedState = (string) $this->input('workflow_state');
                if (in_array($requestedState, ['published', 'cancelled'], true)) {
                    $validator->errors()->add('workflow_state', 'You are not authorized to set this workflow state.');
                }
            }

            if ($this->filled('class_id') && $this->filled('subject_id')) {
                $subject = Subject::where('id', (int) $this->input('subject_id'))
                    ->where('school_id', $user->school_id)
                    ->first();

                if ($subject && (int) $subject->class_id !== (int) $this->input('class_id')) {
                    $validator->errors()->add('class_id', 'Selected class does not match selected subject.');
                }
            }
        });
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        if (!isset($data['workflow_state'])) {
            $user = $this->user();
            $canPublish = $user ? app(OnlineExamPermissionService::class)->has($user, 'publish_online_exams') : false;
            $data['workflow_state'] = $canPublish ? 'draft' : 'pending_review';
        }

        $data['duration_mins'] = (int) ($data['duration_mins'] ?? 0);

        return $key ? ($data[$key] ?? $default) : $data;
    }
}
