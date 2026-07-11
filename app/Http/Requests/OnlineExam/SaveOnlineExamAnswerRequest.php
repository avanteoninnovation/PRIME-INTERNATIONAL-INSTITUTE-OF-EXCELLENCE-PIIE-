<?php

namespace App\Http\Requests\OnlineExam;

use App\Models\OnlineExamQuestion;
use App\Models\OnlineExamSubmission;
use Illuminate\Foundation\Http\FormRequest;

class SaveOnlineExamAnswerRequest extends FormRequest
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
            'question_id' => ['required', 'integer', 'exists:online_exam_questions,id'],
            'selected_option' => ['nullable', 'string', 'max:10'],
            'answer_text' => ['nullable', 'string'],
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable'],
            'score' => ['prohibited'],
            'marks' => ['prohibited'],
            'correct_ans' => ['prohibited'],
            'correct_answer' => ['prohibited'],
            'passed' => ['prohibited'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->submission) {
                return;
            }

            if ($this->submission->status !== OnlineExamSubmission::STATUS_IN_PROGRESS) {
                $validator->errors()->add('submission_id', 'Submission is not in progress.');
                return;
            }

            if ($this->submission->isExpired()) {
                $validator->errors()->add('submission_id', 'Submission has expired.');
                return;
            }

            $questionId = (int) $this->input('question_id');
            $question = OnlineExamQuestion::where('id', $questionId)
                ->where('online_exam_id', $this->submission->online_exam_id)
                ->first();

            if (!$question) {
                $validator->errors()->add('question_id', 'Question does not belong to this exam attempt.');
                return;
            }

            $type = $question->normalized_type;
            $selected = trim((string) $this->input('selected_option', ''));
            $answerText = trim((string) $this->input('answer_text', ''));

            if (in_array($type, ['multiple_choice', 'true_false'], true) && $selected === '') {
                $validator->errors()->add('selected_option', 'Selected option is required for objective questions.');
            }

            if (in_array($type, ['short_answer', 'essay', 'fill_blank'], true) && $answerText === '') {
                $validator->errors()->add('answer_text', 'Answer text is required for this question type.');
            }
        });
    }
}
