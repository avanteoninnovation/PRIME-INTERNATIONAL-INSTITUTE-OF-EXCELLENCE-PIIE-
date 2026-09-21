<?php

namespace App\Support\OnlineExams;

/** Canonical answer-key conversion shared by authoring, import, readiness and marking. */
final class AnswerKey
{
    public static function normalize(?string $type, $value, array $options = []): ?string
    {
        $type = strtolower(trim((string) $type));
        $type = $type === 'multiple_choice' ? 'mcq' : $type;
        $type = $type === 'truefalse' ? 'true_false' : $type;
        $value = trim((string) $value);
        if ($value === '') return null;

        if ($type === 'true_false') {
            $key = strtolower($value);
            return in_array($key, ['true', 'false'], true) ? $key : null;
        }

        if ($type !== 'mcq') return $value;

        $key = strtolower($value);
        if (in_array($key, ['a', 'b', 'c', 'd'], true) && trim((string) ($options[$key] ?? '')) !== '') {
            return $key;
        }

        foreach (['a', 'b', 'c', 'd'] as $candidate) {
            $option = trim((string) ($options[$candidate] ?? ''));
            if ($option !== '' && strcasecmp($option, $value) === 0) {
                return $candidate;
            }
        }

        return null;
    }

    public static function forQuestion(object $question): ?string
    {
        return self::normalize($question->normalized_type ?? $question->type ?? null, $question->correct_ans ?? null, [
            'a' => $question->option_a ?? null,
            'b' => $question->option_b ?? null,
            'c' => $question->option_c ?? null,
            'd' => $question->option_d ?? null,
        ]);
    }
}
