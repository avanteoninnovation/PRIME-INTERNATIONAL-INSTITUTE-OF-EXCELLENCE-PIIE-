<?php

namespace App\Http\Requests\OnlineExam;

use App\Models\OnlineExamQuestion;
use App\Support\Permissions\OnlineExamAuthorizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOnlineExamQuestionRequest extends FormRequest
{
    private ?OnlineExamQuestion $question = null;

    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        $id = (int) ($this->route('question_id') ?? $this->route('id') ?? 0);
        $this->question = OnlineExamQuestion::with('exam')->find($id);
        if (!$this->question) {
            return false;
        }

        return app(OnlineExamAuthorizer::class)->canManageQuestion($user, $this->question);
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string'],
            'type' => ['required', Rule::in(['multiple_choice', 'true_false', 'fill_blank', 'short_answer', 'essay', 'mcq', 'short'])],
            'option_a' => ['nullable', 'string'],
            'option_b' => ['nullable', 'string'],
            'option_c' => ['nullable', 'string'],
            'option_d' => ['nullable', 'string'],
            'correct_ans' => ['nullable', 'string', 'max:255'],
            'correct_answer' => ['nullable', 'string', 'max:255'],
            'marks' => ['required', 'numeric', 'gt:0'],
            'auto_grade_fill_blank' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $type = (string) $this->input('type');
        if ($type === 'short') {
            $type = 'short_answer';
        }

        if ($type === 'mcq') {
            $type = 'multiple_choice';
        }

        $correctAns = $this->input('correct_ans', $this->input('correct_answer'));

        $this->merge([
            'type' => $type,
            'correct_ans' => $correctAns,
            'auto_grade_fill_blank' => $this->boolean('auto_grade_fill_blank'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->question || !$this->question->exam) {
                return;
            }

            if ($this->question->exam->isStructurallyLocked()) {
                $validator->errors()->add('question', 'Questions cannot be modified after attempts have started.');
                return;
            }

            $type = (string) $this->input('type');
            $options = collect([
                $this->input('option_a'),
                $this->input('option_b'),
                $this->input('option_c'),
                $this->input('option_d'),
            ])->filter(fn($v) => !is_null($v) && trim((string) $v) !== '');

            $correctAns = (string) $this->input('correct_ans', '');

            if ($type === 'multiple_choice') {
                if ($options->count() < 2) {
                    $validator->errors()->add('option_a', 'Multiple choice requires at least two options.');
                }

                if (trim($correctAns) === '') {
                    $validator->errors()->add('correct_ans', 'Multiple choice requires at least one correct option.');
                }
            }

            if ($type === 'true_false' && trim($correctAns) === '') {
                $validator->errors()->add('correct_ans', 'True/false question requires a correct answer.');
            }

            if ($type === 'fill_blank' && $this->boolean('auto_grade_fill_blank') && trim($correctAns) === '') {
                $validator->errors()->add('correct_ans', 'Fill blank requires an answer key when auto grading is enabled.');
            }
        });
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        $dbTypeMap = [
            'multiple_choice' => 'mcq',
            'true_false' => 'true_false',
            'fill_blank' => 'fill_blank',
            'short_answer' => 'short',
            'essay' => 'essay',
        ];

        $type = (string) ($data['type'] ?? 'multiple_choice');
        $data['type'] = $dbTypeMap[$type] ?? $type;

        return $key ? ($data[$key] ?? $default) : $data;
    }
}
