<?php

namespace App\Support\OnlineExams;

use App\Models\OnlineExamAnswer;
use App\Models\OnlineExamQuestion;
use App\Models\OnlineExamSubmission;

/** Existing deterministic marking rules; future settings belong at this boundary. */
class OnlineExamMarking
{
    public static function isAutomatic(OnlineExamQuestion $question): bool
    {
        if ($question->question_schema_version !== null) {
            $type = QuestionContract::normalize($question, true)['type'];
            return in_array($type, ['multiple_select', 'numeric', 'fill_blank', 'matching', 'ordering'], true)
                && isset(QuestionContract::normalize($question, true)['marking']);
        }
        if (!in_array($question->type, ['mcq', 'multiple_choice', 'true_false'], true)) return false;
        $key = AnswerKey::forQuestion($question);
        if ($question->normalized_type === 'true_false') {
            return $key !== null;
        }
        return $question->normalized_type === 'multiple_choice'
            && $key !== null;
    }

    public static function manualQuestions($query): void
    {
        $query->where(function ($q) {
            $q->whereNotIn('type', ['mcq', 'multiple_choice', 'true_false'])
                ->orWhereNull('correct_ans')
                ->orWhere(function ($tf) {
                    $tf->where('type', 'true_false')->whereRaw("LOWER(TRIM(correct_ans)) NOT IN ('true', 'false')");
                })->orWhere(function ($mcq) {
                    $mcq->whereIn('type', ['mcq', 'multiple_choice'])->where(function ($invalid) {
                        $invalid->where(function ($notCanonical) {
                            $notCanonical->whereRaw("LOWER(TRIM(correct_ans)) NOT IN ('a', 'b', 'c', 'd')");
                            foreach (['a', 'b', 'c', 'd'] as $key) {
                                $notCanonical->orWhere(function ($option) use ($key) {
                                    $option->whereRaw('LOWER(TRIM(correct_ans)) = ?', [$key])
                                        ->whereRaw("TRIM(COALESCE(option_{$key}, '')) = ''");
                                });
                            }
                        })->where(function ($notLegacyText) {
                            foreach (['a', 'b', 'c', 'd'] as $key) {
                                $notLegacyText->whereRaw("(TRIM(COALESCE(option_{$key}, '')) = '' OR LOWER(TRIM(correct_ans)) <> LOWER(TRIM(option_{$key})))");
                            }
                        });
                    });
                });
        })->where(function ($q) {
            // Structured Batch 2 objective types are auto-marked. Structured
            // manual types are not enabled yet, so they must not enter the
            // legacy manual-marking queue.
            $q->whereNull('question_schema_version')
                ->orWhere('question_schema_version', '<>', QuestionContract::STRUCTURED_VERSION);
        });
    }

    public static function responses($query): void
    {
        $query->where(function ($q) {
            $q->whereRaw("TRIM(COALESCE(answer_text, '')) <> ''")
                ->orWhereRaw("TRIM(COALESCE(selected_option, '')) <> ''")
                ->orWhereRaw("TRIM(COALESCE(answer_payload, '')) <> ''");
        });
    }

    public static function unmarked($query): void
    {
        $query->where(function ($q) {
            $q->whereNull('awarded_marks')->orWhereNull('marked_at')->orWhereNull('marked_by');
        });
    }

    public static function hasResponse(?OnlineExamAnswer $answer): bool
    {
        return $answer && (trim((string) $answer->answer_text) !== ''
            || trim((string) $answer->selected_option) !== ''
            || ($answer->answer_payload !== null && trim((string) $answer->answer_payload) !== ''));
    }

    public static function isManuallyMarked(OnlineExamAnswer $answer): bool
    {
        return $answer->awarded_marks !== null && $answer->marked_at !== null && $answer->marked_by !== null;
    }

    /** Read-only summary. Callers performing writes must hold the submission lock. */
    public static function summary(OnlineExamSubmission $submission): array
    {
        $submission->loadMissing(['exam.questions', 'answerRows']);
        $answers = $submission->answerRows->keyBy('question_id');
        $objectiveCents = 0;
        $manualCents = 0;
        $pending = 0;
        foreach ($submission->exam->questions as $question) {
            $answer = $answers->get($question->id);
            if (!self::hasResponse($answer)) {
                continue;
            }
            if (self::isAutomatic($question)) {
                $objectiveCents += (int) round((float) $answer->awarded_marks * 100);
            } elseif (self::isManuallyMarked($answer)) {
                $manualCents += (int) round((float) $answer->awarded_marks * 100);
            } else {
                $pending++;
            }
        }
        return ['objective_score' => $objectiveCents / 100, 'manual_score' => $manualCents / 100,
            'score' => ($objectiveCents + $manualCents) / 100, 'pending' => $pending];
    }
}
