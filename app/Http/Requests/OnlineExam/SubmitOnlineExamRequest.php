<?php

namespace App\Http\Requests\OnlineExam;

use App\Models\OnlineExamSubmission;
use Illuminate\Foundation\Http\FormRequest;

class SubmitOnlineExamRequest extends FormRequest
{
    private ?OnlineExamSubmission $submission = null;

    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user || (int) $user->role_id !== 7) {
            return false;
        }

        $submissionId = (int) ($this->route('submission') ?? $this->input('submission_id') ?? 0);
        $this->submission = OnlineExamSubmission::find($submissionId);

        return $this->submission
            && (int) $this->submission->student_id === (int) $user->id
            && (int) $this->submission->school_id === (int) $user->school_id;
    }

    public function rules(): array
    {
        return [
            'submission_id' => ['required', 'integer', 'exists:online_exam_submissions,id'],
            'answers' => ['nullable', 'array'],
            'score' => ['prohibited'],
            'passed' => ['prohibited'],
            'total_marks_snapshot' => ['prohibited'],
            'objective_score' => ['prohibited'],
            'manual_score' => ['prohibited'],
            'submitted_via' => ['nullable', 'in:manual,timeout,administrator'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->submission) {
                return;
            }

            if ($this->submission->status !== OnlineExamSubmission::STATUS_IN_PROGRESS) {
                $validator->errors()->add('submission_id', 'Submission is not in progress or was already submitted.');
                return;
            }

            if (!empty($this->submission->submitted_at)) {
                $validator->errors()->add('submission_id', 'Duplicate submission is not allowed.');
            }
        });
    }
}
