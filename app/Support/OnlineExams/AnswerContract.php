<?php

namespace App\Support\OnlineExams;

use App\Models\OnlineExamQuestion;
use InvalidArgumentException;

/** Validates structured answers against the server-side question snapshot. */
final class AnswerContract
{
    public const STRUCTURED_VERSION = 2;

    public static function fromStored(object $answer, OnlineExamQuestion $question): array
    {
        if ($answer->answer_schema_version === null) {
            return self::fromLegacy($answer, $question);
        }
        if ((int) $answer->answer_schema_version !== self::STRUCTURED_VERSION) {
            throw new InvalidArgumentException('Unsupported answer schema version.');
        }

        $payload = QuestionContract::decode($answer->answer_payload, 'answer_payload');
        return self::validateStructured($payload, $question);
    }

    public static function fromRequest(array $input, OnlineExamQuestion $question): array
    {
        if (array_key_exists('answer_payload', $input) && $input['answer_payload'] !== null && $input['answer_payload'] !== '') {
            $payload = is_array($input['answer_payload'])
                ? $input['answer_payload']
                : QuestionContract::decode($input['answer_payload'], 'answer_payload');
            return self::validateStructured($payload, $question);
        }
        if ($question->question_schema_version !== null && QuestionContract::normalize($question)['type'] === 'fill_blank') {
            if (trim((string) ($input['answer_text'] ?? '')) !== '') {
                throw new InvalidArgumentException('Structured Fill Blank answers must use the blanks payload.');
            }
            return ['schema_version' => self::STRUCTURED_VERSION, 'type' => 'fill_blank', 'blanks' => []];
        }
        return self::fromLegacy((object) [
            'selected_option' => $input['selected_option'] ?? null,
            'answer_text' => $input['answer_text'] ?? null,
        ], $question);
    }

    public static function encode(array $payload): string
    {
        return QuestionContract::encode($payload, 'answer_payload');
    }

    private static function fromLegacy(object $answer, OnlineExamQuestion $question): array
    {
        $type = $question->question_schema_version === null
            ? $question->normalized_type
            : QuestionContract::normalize($question)['type'];
        $selected = trim((string) ($answer->selected_option ?? ''));
        $text = (string) ($answer->answer_text ?? '');

        if ($type === 'multiple_choice' && $selected !== '') {
            $valid = array_keys(QuestionContract::publicProjection($question)['options']);
            $valid = array_map(fn ($index) => QuestionContract::publicProjection($question)['options'][$index]['id'], $valid);
            $canonicalSelected = strtolower($selected);
            if (!in_array($canonicalSelected, $valid, true)) {
                throw new InvalidArgumentException('Selected option does not belong to this question.');
            }
            return ['schema_version' => null, 'type' => 'single_choice', 'selected_option_id' => $canonicalSelected];
        }
        if ($type === 'true_false' && $selected !== '') {
            if (!in_array(strtolower($selected), ['true', 'false'], true)) {
                throw new InvalidArgumentException('True/False answer is invalid.');
            }
            return ['schema_version' => null, 'type' => 'true_false', 'value' => strtolower($selected) === 'true'];
        }
        if (in_array($type, ['short_answer', 'essay', 'fill_blank'], true)) {
            if (strlen($text) > 65536) {
                throw new InvalidArgumentException('Answer text exceeds the payload limit.');
            }
            return ['schema_version' => null, 'type' => $type, 'text' => $text];
        }
        if ($selected === '' && $text === '') {
            return ['schema_version' => null, 'type' => $type, 'blank' => true];
        }
        throw new InvalidArgumentException('Answer is incompatible with the question type.');
    }

    private static function validateStructured(array $payload, OnlineExamQuestion $question): array
    {
        // Structured payloads are stored with the envelope schema version.
        // Treat it as contract metadata (and validate it strictly) so a
        // payload written by saveAnswer can be read again during submission.
        if (array_key_exists('schema_version', $payload)
            && (int) $payload['schema_version'] !== self::STRUCTURED_VERSION) {
            throw new InvalidArgumentException('Unsupported answer schema version.');
        }
        if (!isset($payload['type']) || !is_string($payload['type'])) {
            throw new InvalidArgumentException('Structured answer type is required.');
        }
        $type = $question->question_schema_version === null
            ? $question->normalized_type
            : QuestionContract::normalize($question)['type'];
        $expected = $type === 'multiple_choice' ? 'single_choice' : $type;
        if ($payload['type'] !== $expected && !($type === 'fill_blank' && $payload['type'] === 'fill_blank')) {
            throw new InvalidArgumentException('Answer type does not match the question.');
        }

        $allowed = match ($payload['type']) {
            'single_choice' => ['schema_version', 'type', 'selected_option_id'],
            'multiple_select' => ['schema_version', 'type', 'selected_option_ids'],
            'true_false' => ['schema_version', 'type', 'value'],
            'numeric' => ['schema_version', 'type', 'value'],
            'matching' => ['schema_version', 'type', 'pairs'],
            'ordering' => ['schema_version', 'type', 'ordered_ids'],
            'fill_blank' => ['schema_version', 'type', 'blanks'],
            default => ['schema_version', 'type', 'text', 'blank'],
        };
        foreach (array_keys($payload) as $key) {
            if (!in_array($key, $allowed, true)) {
                throw new InvalidArgumentException('Structured answer contains unsupported fields.');
            }
        }

        $options = QuestionContract::publicProjection($question)['options'];
        $ids = array_map(fn ($option) => $option['id'], $options);

        if ($payload['type'] === 'single_choice') {
            $id = $payload['selected_option_id'] ?? null;
            if (!is_string($id)) throw new InvalidArgumentException('Selected option is invalid.');
            if (!in_array($id, $ids, true)) throw new InvalidArgumentException('Selected option is not valid for this question.');
        } elseif ($payload['type'] === 'multiple_select') {
            $selected = $payload['selected_option_ids'] ?? null;
            if (!is_array($selected) || count($selected) > QuestionContract::MAX_OPTIONS) throw new InvalidArgumentException('Selected options are invalid.');
            $seen = [];
            foreach ($selected as $id) {
                if (!is_string($id) || !in_array($id, $ids, true)) throw new InvalidArgumentException('Selected option is not valid for this question.');
                if (isset($seen[$id])) throw new InvalidArgumentException('Duplicate selected options are invalid.');
                $seen[$id] = true;
            }
            sort($selected, SORT_STRING);
            $payload['selected_option_ids'] = array_values($selected);
        } elseif ($payload['type'] === 'true_false') {
            if (!is_bool($payload['value'] ?? null)) throw new InvalidArgumentException('True/False value is invalid.');
        } elseif ($payload['type'] === 'numeric') {
            $value = $payload['value'] ?? null;
            if ((is_array($value) || is_object($value) || (!is_int($value) && !is_float($value) && !is_string($value)))
                || !is_numeric($value) || !is_finite((float) $value) || abs((float) $value) > 1e15) {
                throw new InvalidArgumentException('Numeric answer is invalid.');
            }
            $payload['value'] = (float) $value;
        } elseif ($payload['type'] === 'fill_blank') {
            $blanks = $payload['blanks'] ?? null;
            if (!is_array($blanks) || count($blanks) > QuestionContract::MAX_BLANKS) {
                throw new InvalidArgumentException('Fill Blank answers are invalid.');
            }
            $configured = QuestionContract::normalize($question, true)['marking']['blanks'] ?? [];
            foreach ($blanks as $id => $value) {
                if (!is_string($id) || !array_key_exists($id, $configured) || !is_string($value) || mb_strlen($value) > 4096) {
                    throw new InvalidArgumentException('Fill Blank answer is invalid.');
                }
            }
            $ordered = [];
            foreach (array_keys($configured) as $id) {
                if (array_key_exists($id, $blanks)) $ordered[$id] = $blanks[$id];
            }
            $payload['blanks'] = $ordered;
        } elseif ($payload['type'] === 'matching') {
            $pairs=$payload['pairs']??null; if(!is_array($pairs)||count($pairs)>QuestionContract::MAX_MATCHING_ITEMS) throw new InvalidArgumentException('Matching answer is invalid.');
            $contract=QuestionContract::normalize($question,true); $left=array_column($contract['left_items'],'id'); $right=array_column($contract['right_items'],'id'); $seen=[];
            foreach($pairs as $l=>$r){if(!is_string($l)||!is_string($r)||!in_array($l,$left,true)||!in_array($r,$right,true)||isset($seen[$r])) throw new InvalidArgumentException('Matching pair is invalid.'); $seen[$r]=true;}
            $ordered=[]; foreach($left as $l) if(array_key_exists($l,$pairs)) $ordered[$l]=$pairs[$l]; $payload['pairs']=$ordered;
        } elseif ($payload['type'] === 'ordering') {
            $ids=$payload['ordered_ids']??null; if(!is_array($ids)||count($ids)>QuestionContract::MAX_ORDERING_ITEMS) throw new InvalidArgumentException('Ordering answer is invalid.');
            $valid=array_column(QuestionContract::normalize($question)['items'],'id'); $seen=[]; foreach($ids as $id){if(!is_string($id)||!in_array($id,$valid,true)||isset($seen[$id])) throw new InvalidArgumentException('Ordering item is invalid.');$seen[$id]=true;} $payload['ordered_ids']=array_values($ids);
        } elseif (in_array($payload['type'], ['short_answer', 'essay', 'fill_blank'], true)) {
            if (isset($payload['text']) && (!is_string($payload['text']) || strlen($payload['text']) > 65536)) {
                throw new InvalidArgumentException('Answer text is invalid.');
            }
        }
        return ['schema_version' => self::STRUCTURED_VERSION] + $payload;
    }
}
