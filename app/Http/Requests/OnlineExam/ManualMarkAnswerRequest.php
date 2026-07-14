<?php

namespace App\Http\Requests\OnlineExam;

use App\Models\OnlineExamAnswer;
use App\Support\Permissions\OnlineExamAuthorizer;
use Illuminate\Foundation\Http\FormRequest;

class ManualMarkAnswerRequest extends FormRequest
{
    private ?OnlineExamAnswer $answer = null;

    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        $answerParam = $this->route('answer');
        $answerId = (int) ((is_object($answerParam) ? ($answerParam->id ?? 0) : $answerParam) ?? $this->input('answer_id') ?? 0);
        $this->answer = OnlineExamAnswer::with(['question', 'submission.exam'])->find($answerId);

        return $this->answer && app(OnlineExamAuthorizer::class)->canMarkAnswer($user, $this->answer);
    }

    public function rules(): array
    {
        return [
            'answer_id' => ['required', 'integer', 'exists:online_exam_answers,id'],
            'awarded_marks' => ['required', 'numeric', 'min:0'],
            'teacher_comment' => ['nullable', 'string'],
            'allow_objective_override' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->answer || !$this->answer->question) {
                return;
            }

            $question = $this->answer->question;
            $maxMarks = (float) $question->marks;
            $awarded = (float) $this->input('awarded_marks', 0);

            if ($awarded < 0 || $awarded > $maxMarks) {
                $validator->errors()->add('awarded_marks', 'Awarded marks must be between 0 and question marks.');
            }

            $objectiveTypes = ['multiple_choice', 'true_false'];
            $normalizedType = $question->normalized_type;
            if (in_array($normalizedType, $objectiveTypes, true) && !$this->boolean('allow_objective_override')) {
                $validator->errors()->add('awarded_marks', 'Objective questions cannot be manually marked unless override is explicitly allowed.');
            }
        });
    }
}
