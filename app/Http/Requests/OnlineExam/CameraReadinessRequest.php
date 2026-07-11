<?php

namespace App\Http\Requests\OnlineExam;

use App\Models\OnlineExamSubmission;
use Illuminate\Foundation\Http\FormRequest;

class CameraReadinessRequest extends FormRequest
{
    private ?OnlineExamSubmission $submission = null;

    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user || (int) $user->role_id !== 7) {
            return false;
        }

        $submissionId = (int) ($this->route('submission') ?? $this->input('submission_id') ?? 0);
        $this->submission = OnlineExamSubmission::with('exam')->find($submissionId);

        return $this->submission
            && (int) $this->submission->student_id === (int) $user->id
            && (int) $this->submission->school_id === (int) $user->school_id;
    }

    public function rules(): array
    {
        return [
            'submission_id' => ['required', 'integer', 'exists:online_exam_submissions,id'],
            'consent_accepted' => ['required', 'boolean'],
            'permission_granted' => ['required', 'boolean'],
            'camera_ready' => ['required', 'boolean'],
            'camera_stream_state' => ['nullable', 'string', 'max:50'],
            'image' => ['prohibited'],
            'snapshot' => ['prohibited'],
            'file' => ['prohibited'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->submission || !$this->submission->exam) {
                return;
            }

            if (!$this->submission->exam->webcam_required) {
                $validator->errors()->add('submission_id', 'This exam does not require webcam readiness.');
                return;
            }

            if (!$this->boolean('consent_accepted')) {
                $validator->errors()->add('consent_accepted', 'Consent is required.');
            }

            if (!$this->boolean('permission_granted')) {
                $validator->errors()->add('permission_granted', 'Camera permission must be granted.');
            }

            if (!$this->boolean('camera_ready')) {
                $validator->errors()->add('camera_ready', 'Camera readiness must be true to proceed.');
            }
        });
    }
}
