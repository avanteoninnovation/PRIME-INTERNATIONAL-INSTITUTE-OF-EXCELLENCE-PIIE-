<?php

namespace App\Support\OnlineExams;

use InvalidArgumentException;

/**
 * Boundary between the legacy four-column question format and the
 * versioned structured question format. A null version is legacy; version 2
 * is the first structured contract. No caller should fall back to legacy if
 * a row declares a structured version but contains invalid JSON.
 */
final class QuestionContract
{
    public const STRUCTURED_VERSION = 2;
    public const MAX_OPTIONS = 8;
    public const MAX_BLANKS = 16;
    public const MAX_ACCEPTED_ANSWERS = 8;
    public const MAX_MATCHING_ITEMS = 16;
    public const MAX_ORDERING_ITEMS = 16;

    private const TYPES = [
        'single_choice', 'multiple_choice', 'true_false', 'short_answer', 'essay', 'fill_blank',
        'numeric', 'matching', 'ordering', 'multiple_select',
    ];

    public static function normalize(object $question, bool $includeMarking = false): array
    {
        $version = $question->question_schema_version;
        if ($version === null) {
            return self::legacy($question, $includeMarking);
        }

        if ((int) $version !== self::STRUCTURED_VERSION) {
            throw new InvalidArgumentException('Unsupported question schema version.');
        }

        $config = self::decode($question->question_config, 'question_config');
        $marking = self::decode($question->marking_config, 'marking_config');
        self::validateConfig($config);
        self::validateMarking($marking);
        if (($config['type'] ?? null) === 'fill_blank') {
            self::validateFillBlankShape($config, $marking);
        }
        if (($config['type'] ?? null) === 'matching') self::validateMatchingShape($config, $marking);
        if (($config['type'] ?? null) === 'ordering') self::validateOrderingShape($config, $marking);

        $result = [
            'schema_version' => self::STRUCTURED_VERSION,
            'type' => $config['type'],
            'prompt' => $config['prompt'],
            'options' => $config['options'] ?? [],
            'blanks' => $config['blanks'] ?? [],
            'left_items' => $config['left_items'] ?? [],
            'right_items' => $config['right_items'] ?? [],
            'items' => $config['items'] ?? [],
            'presentation' => $config['presentation'] ?? [],
            'marks' => $config['marks'] ?? (int) ($question->marks ?? 0),
        ];

        if ($includeMarking) {
            $result['marking'] = $marking;
        }

        return $result;
    }

    public static function publicProjection(object $question): array
    {
        return self::normalize($question, false);
    }

    public static function legacy(object $question, bool $includeMarking = false): array
    {
        $type = $question->normalized_type ?? self::legacyType($question->type ?? null);
        $options = [];
        foreach (['a', 'b', 'c', 'd'] as $key) {
            $value = $question->{'option_' . $key} ?? null;
            if ($value !== null && trim((string) $value) !== '') {
                $options[] = ['id' => $key, 'label' => (string) $value];
            }
        }

        $result = [
            'schema_version' => null,
            'type' => $type,
            'prompt' => (string) ($question->question ?? ''),
            'options' => $options,
            'presentation' => [],
            'marks' => (int) ($question->marks ?? 0),
        ];

        if ($includeMarking) {
            $result['marking'] = [
                'mode' => in_array($type, ['multiple_choice', 'true_false'], true)
                    && AnswerKey::forQuestion($question) !== null ? 'automatic' : 'manual',
                'correct_answer' => AnswerKey::forQuestion($question),
                'max_marks' => (int) ($question->marks ?? 0),
            ];
        }

        return $result;
    }

    public static function encode(array $value, string $field): string
    {
        self::assertPayloadSize($value, $field);
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException("Invalid {$field}.", 0, $e);
        }
    }

    public static function authoring(string $type, string $prompt, array $options, array $marking, $marks): array
    {
        $type = strtolower(trim($type));
        if (!in_array($type, ['multiple_select', 'numeric', 'fill_blank', 'matching', 'ordering'], true)) {
            throw new InvalidArgumentException('Unsupported structured question type.');
        }
        if (trim($prompt) === '') throw new InvalidArgumentException('Question prompt is required.');
        if (!is_numeric($marks) || (float) $marks < 1) throw new InvalidArgumentException('Question marks are invalid.');

        if ($type === 'multiple_select') {
            // Authoring forms expose spare option rows; empty rows are not
            // options and are ignored before validating the active set.
            $options = array_values(array_filter($options, static function ($option) {
                return trim((string) ($option['label'] ?? '')) !== '';
            }));
            if (count($options) < 2 || count($options) > self::MAX_OPTIONS) {
                throw new InvalidArgumentException('Multiple Select requires between 2 and 8 options.');
            }
            $seen = [];
            $publicOptions = [];
            foreach ($options as $option) {
                $id = (string) ($option['id'] ?? '');
                $label = trim((string) ($option['label'] ?? ''));
                if (!preg_match('/^[a-z][a-z0-9_-]{0,31}$/i', $id) || $label === '') {
                    throw new InvalidArgumentException('Multiple Select options are invalid.');
                }
                if (isset($seen[$id])) throw new InvalidArgumentException('Multiple Select option IDs must be unique.');
                $seen[$id] = true;
                $publicOptions[] = ['id' => $id, 'label' => $label];
            }
            $correct = array_values(array_unique(array_map('strval', $marking['correct_option_ids'] ?? [])));
            if (count($correct) < 2 || array_diff($correct, array_keys($seen))) {
                throw new InvalidArgumentException('Select at least two valid correct options.');
            }
            return [
                'schema_version' => self::STRUCTURED_VERSION,
                'storage_type' => 'mcq',
                'question_config' => self::encode(['type' => $type, 'prompt' => $prompt, 'options' => $publicOptions, 'marks' => (float) $marks], 'question_config'),
                'marking_config' => self::encode(['mode' => 'all_or_nothing', 'max_marks' => (float) $marks, 'correct_option_ids' => $correct], 'marking_config'),
            ];
        }

        if ($type === 'fill_blank') {
            return self::authorFillBlank($prompt, $options, $marking, $marks);
        }

        if ($type === 'matching') {
            return self::authorMatching($prompt, $options, $marks);
        }
        if ($type === 'ordering') {
            return self::authorOrdering($prompt, $options, $marks);
        }

        $target = $marking['target'] ?? null;
        $tolerance = $marking['tolerance'] ?? 0;
        if (!is_numeric($target) || !is_finite((float) $target) || abs((float) $target) > 1e15) {
            throw new InvalidArgumentException('Numerical target is invalid.');
        }
        if ($tolerance === '' || $tolerance === null) $tolerance = 0;
        if (!is_numeric($tolerance) || !is_finite((float) $tolerance) || (float) $tolerance < 0 || (float) $tolerance > 1e15) {
            throw new InvalidArgumentException('Numerical tolerance is invalid.');
        }
        return [
            'schema_version' => self::STRUCTURED_VERSION,
            'storage_type' => 'short',
            'question_config' => self::encode(['type' => $type, 'prompt' => $prompt, 'options' => [], 'marks' => (float) $marks], 'question_config'),
            'marking_config' => self::encode(['mode' => 'absolute_tolerance', 'max_marks' => (float) $marks, 'target' => (float) $target, 'tolerance' => (float) $tolerance, 'comparison' => 'absolute'], 'marking_config'),
        ];
    }

    public static function decode($value, string $field): array
    {
        if (is_array($value)) {
            self::assertPayloadSize($value, $field);
            return $value;
        }
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("Structured {$field} is missing.");
        }

        try {
            $decoded = json_decode($value, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException("Structured {$field} is malformed.", 0, $e);
        }
        if (!is_array($decoded)) {
            throw new InvalidArgumentException("Structured {$field} must be an object.");
        }
        self::assertPayloadSize($decoded, $field);
        return $decoded;
    }

    private static function validateConfig(array $config): void
    {
        if (!isset($config['type']) || !in_array($config['type'], self::TYPES, true)) {
            throw new InvalidArgumentException('Structured question type is unsupported.');
        }
        if (!isset($config['prompt']) || !is_string($config['prompt'])) {
            throw new InvalidArgumentException('Structured question prompt is invalid.');
        }
        if (isset($config['options'])) {
            if (!is_array($config['options']) || count($config['options']) > 100) {
                throw new InvalidArgumentException('Structured question options are invalid.');
            }
            $ids = [];
            foreach ($config['options'] as $option) {
                if (!is_array($option) || !isset($option['id'], $option['label'])
                    || !is_string($option['id']) || !is_string($option['label'])) {
                    throw new InvalidArgumentException('Structured question option is invalid.');
                }
                if (isset($ids[$option['id']])) {
                    throw new InvalidArgumentException('Structured question option IDs must be unique.');
                }
                $ids[$option['id']] = true;
            }
        }
        if (array_key_exists('marking', $config) || array_key_exists('correct_answer', $config)) {
            throw new InvalidArgumentException('Answer keys cannot be stored in the public question configuration.');
        }
        if ($config['type'] === 'fill_blank') {
            self::validateFillBlankShape($config, null);
        }
        if ($config['type'] === 'matching') self::validateMatchingShape($config, null);
        if ($config['type'] === 'ordering') self::validateOrderingShape($config, null);
    }

    private static function validateMarking(array $marking): void
    {
        if (isset($marking['mode']) && !is_string($marking['mode'])) {
            throw new InvalidArgumentException('Marking mode is invalid.');
        }
        if (isset($marking['max_marks']) && (!is_int($marking['max_marks']) && !is_float($marking['max_marks']))) {
            throw new InvalidArgumentException('Maximum marks are invalid.');
        }
    }

    private static function authorMatching(string $prompt, array $rows, $marks): array
    {
        $rows = array_values(array_filter($rows, fn ($r) => is_array($r) && trim((string)($r['left_text'] ?? '')) !== ''));
        if (count($rows) < 2 || count($rows) > self::MAX_MATCHING_ITEMS) throw new InvalidArgumentException('Matching requires between 2 and 16 pairs.');
        $left=[]; $right=[]; $pairs=[]; $seenL=[]; $seenR=[];
        foreach ($rows as $row) {
            $l=(string)($row['left_id']??''); $r=(string)($row['right_id']??''); $lt=trim((string)($row['left_text']??'')); $rt=trim((string)($row['right_text']??''));
            if (!preg_match('/^[a-z][a-z0-9_-]{0,31}$/i',$l) || !preg_match('/^[a-z][a-z0-9_-]{0,31}$/i',$r) || $lt==='' || $rt==='' || mb_strlen($lt)>1000 || mb_strlen($rt)>1000 || isset($seenL[$l]) || isset($seenR[$r])) throw new InvalidArgumentException('Matching items are invalid.');
            $seenL[$l]=true; $seenR[$r]=true; $left[]=['id'=>$l,'text'=>$lt]; $right[]=['id'=>$r,'text'=>$rt]; $pairs[$l]=$r;
        }
        return ['schema_version'=>self::STRUCTURED_VERSION,'storage_type'=>'short',
            'question_config'=>self::encode(['type'=>'matching','prompt'=>$prompt,'left_items'=>$left,'right_items'=>$right,'marks'=>(float)$marks],'question_config'),
            'marking_config'=>self::encode(['mode'=>'all_or_nothing','max_marks'=>(float)$marks,'pairs'=>$pairs],'marking_config')];
    }

    private static function authorOrdering(string $prompt, array $rows, $marks): array
    {
        $rows = array_values(array_filter($rows, fn ($r) => is_array($r) && trim((string)($r['text'] ?? '')) !== ''));
        if (count($rows) < 2 || count($rows) > self::MAX_ORDERING_ITEMS) throw new InvalidArgumentException('Ordering requires between 2 and 16 items.');
        $items=[]; $order=[]; $seen=[];
        foreach ($rows as $row) { $id=(string)($row['id']??''); $text=trim((string)($row['text']??'')); if(!preg_match('/^[a-z][a-z0-9_-]{0,31}$/i',$id)||$text===''||mb_strlen($text)>1000||isset($seen[$id])) throw new InvalidArgumentException('Ordering items are invalid.'); $seen[$id]=true; $items[]=['id'=>$id,'text'=>$text]; $order[]=$id; }
        return ['schema_version'=>self::STRUCTURED_VERSION,'storage_type'=>'short',
            'question_config'=>self::encode(['type'=>'ordering','prompt'=>$prompt,'items'=>$items,'marks'=>(float)$marks],'question_config'),
            'marking_config'=>self::encode(['mode'=>'all_or_nothing','max_marks'=>(float)$marks,'correct_order'=>$order],'marking_config')];
    }

    private static function validateMatchingShape(array $config, ?array $marking): void
    {
        $left=$config['left_items']??null; $right=$config['right_items']??null;
        if(!is_array($left)||!is_array($right)||count($left)<2||count($left)>self::MAX_MATCHING_ITEMS||count($left)!==count($right)) throw new InvalidArgumentException('Structured Matching items are invalid.');
        $ls=[];$rs=[]; foreach($left as $i){if(!is_array($i)||!isset($i['id'],$i['text'])||isset($ls[$i['id']]))throw new InvalidArgumentException('Structured Matching left items are invalid.');$ls[$i['id']]=1;} foreach($right as $i){if(!is_array($i)||!isset($i['id'],$i['text'])||isset($rs[$i['id']]))throw new InvalidArgumentException('Structured Matching right items are invalid.');$rs[$i['id']]=1;}
        if($marking!==null){$pairs=$marking['pairs']??null;if(!is_array($pairs)||count($pairs)!==count($ls)||array_diff_key($pairs,$ls)||array_diff(array_values($pairs),array_keys($rs))||count(array_unique(array_values($pairs)))!==count($pairs))throw new InvalidArgumentException('Structured Matching marking configuration is invalid.');}
    }

    private static function validateOrderingShape(array $config, ?array $marking): void
    {
        $items=$config['items']??null; if(!is_array($items)||count($items)<2||count($items)>self::MAX_ORDERING_ITEMS)throw new InvalidArgumentException('Structured Ordering items are invalid.'); $ids=[]; foreach($items as $i){if(!is_array($i)||!isset($i['id'],$i['text'])||isset($ids[$i['id']]))throw new InvalidArgumentException('Structured Ordering items are invalid.');$ids[$i['id']]=1;} if($marking!==null){$order=$marking['correct_order']??null;if(!is_array($order)||count($order)!==count($ids)||array_diff($order,array_keys($ids))||count(array_unique($order))!==count($order))throw new InvalidArgumentException('Structured Ordering marking configuration is invalid.');}
    }

    public static function matchingCorrect(array $contract, array $answer): bool { $expected=$contract['marking']['pairs']??[]; $given=$answer['pairs']??[]; return is_array($given)&&count($given)===count($expected)&&$given===$expected; }
    public static function orderingCorrect(array $contract, array $answer): bool { return ($answer['ordered_ids']??[])===($contract['marking']['correct_order']??[]); }

    private static function authorFillBlank(string $prompt, array $blanks, array $marking, $marks): array
    {
        $blanks = array_values(array_filter($blanks, static function ($blank) {
            return is_array($blank) && collect((array) ($blank['accepted_answers'] ?? []))->contains(fn ($value) => trim((string) $value) !== '');
        }));
        if (count($blanks) < 1 || count($blanks) > self::MAX_BLANKS) {
            throw new InvalidArgumentException('Fill Blank requires between 1 and 16 blanks.');
        }
        preg_match_all('/\[\[([a-z][a-z0-9_-]{0,31})\]\]/i', $prompt, $matches);
        $rawReferenced = array_map('strval', $matches[1] ?? []);
        $referenced = array_values(array_unique($rawReferenced));
        if (count($rawReferenced) !== count($blanks) || count($referenced) !== count($blanks)) {
            throw new InvalidArgumentException('Every configured blank must appear exactly once in the prompt.');
        }
        $publicBlanks = [];
        $serverBlanks = [];
        $seen = [];
        foreach ($blanks as $blank) {
            $id = (string) ($blank['id'] ?? '');
            if (!preg_match('/^[a-z][a-z0-9_-]{0,31}$/i', $id) || isset($seen[$id]) || !in_array($id, $referenced, true)) {
                throw new InvalidArgumentException('Fill Blank IDs are invalid or do not match the prompt.');
            }
            $seen[$id] = true;
            $accepted = $blank['accepted_answers'] ?? [];
            if (!is_array($accepted) || count($accepted) < 1 || count($accepted) > self::MAX_ACCEPTED_ANSWERS) {
                throw new InvalidArgumentException('Each blank must have between 1 and 8 accepted answers.');
            }
            $values = [];
            foreach ($accepted as $rawValue) {
                foreach (explode(',', (string) $rawValue) as $value) {
                    $value = trim($value);
                    if ($value === '' || mb_strlen($value) > 255) {
                        throw new InvalidArgumentException('Accepted blank answers are invalid.');
                    }
                    $values[] = $value;
                }
            }
            $publicBlanks[] = ['id' => $id];
            $serverBlanks[$id] = ['accepted_answers' => array_values(array_unique($values))];
        }
        if (array_diff($referenced, array_keys($seen))) {
            throw new InvalidArgumentException('Prompt contains an unknown blank ID.');
        }
        return [
            'schema_version' => self::STRUCTURED_VERSION,
            'storage_type' => 'fill_blank',
            'question_config' => self::encode(['type' => 'fill_blank', 'prompt' => $prompt, 'blanks' => $publicBlanks, 'marks' => (float) $marks], 'question_config'),
            'marking_config' => self::encode([
                'mode' => 'all_or_nothing',
                'max_marks' => (float) $marks,
                'case_sensitive' => (bool) ($marking['case_sensitive'] ?? false),
                'trim_whitespace' => array_key_exists('trim_whitespace', $marking) ? (bool) $marking['trim_whitespace'] : true,
                'blanks' => $serverBlanks,
            ], 'marking_config'),
        ];
    }

    private static function validateFillBlankShape(array $config, ?array $marking): void
    {
        $blanks = $config['blanks'] ?? null;
        if (!is_array($blanks) || count($blanks) < 1 || count($blanks) > self::MAX_BLANKS) {
            throw new InvalidArgumentException('Structured Fill Blank blanks are invalid.');
        }
        preg_match_all('/\[\[([a-z][a-z0-9_-]{0,31})\]\]/i', (string) $config['prompt'], $matches);
        $rawReferenced = array_map('strval', $matches[1] ?? []);
        $referenced = array_values(array_unique($rawReferenced));
        $ids = [];
        foreach ($blanks as $blank) {
            if (!is_array($blank) || !isset($blank['id']) || !is_string($blank['id']) || isset($ids[$blank['id']])) {
                throw new InvalidArgumentException('Structured Fill Blank IDs are invalid.');
            }
            $ids[$blank['id']] = true;
        }
        if (count($rawReferenced) !== count($ids) || count($referenced) !== count($ids) || array_diff($referenced, array_keys($ids))) {
            throw new InvalidArgumentException('Structured Fill Blank prompt and IDs do not match.');
        }
        if ($marking !== null) {
            $configured = $marking['blanks'] ?? null;
            if (!is_array($configured) || array_diff(array_keys($configured), array_keys($ids)) || array_diff(array_keys($ids), array_keys($configured))) {
                throw new InvalidArgumentException('Structured Fill Blank marking configuration is invalid.');
            }
            foreach ($configured as $item) {
                $accepted = $item['accepted_answers'] ?? null;
                if (!is_array($accepted) || count($accepted) < 1 || count($accepted) > self::MAX_ACCEPTED_ANSWERS) {
                    throw new InvalidArgumentException('Structured Fill Blank accepted answers are invalid.');
                }
            }
        }
    }

    public static function fillBlankCorrect(array $contract, array $answer): bool
    {
        $marking = $contract['marking'] ?? [];
        $answers = $answer['blanks'] ?? [];
        if (!is_array($answers)) return false;
        foreach (($marking['blanks'] ?? []) as $id => $config) {
            if (!array_key_exists($id, $answers) || !is_string($answers[$id])) return false;
            $value = self::normalizeBlankValue($answers[$id], (bool) ($marking['case_sensitive'] ?? false), (bool) ($marking['trim_whitespace'] ?? true));
            $accepted = array_map(fn ($item) => self::normalizeBlankValue((string) $item, (bool) ($marking['case_sensitive'] ?? false), (bool) ($marking['trim_whitespace'] ?? true)), (array) ($config['accepted_answers'] ?? []));
            if (!in_array($value, $accepted, true)) return false;
        }
        return count($answers) === count($marking['blanks'] ?? []);
    }

    private static function normalizeBlankValue(string $value, bool $caseSensitive, bool $trim): string
    {
        $value = $trim ? trim($value) : $value;
        return $caseSensitive ? $value : mb_strtolower($value, 'UTF-8');
    }

    private static function legacyType($type): string
    {
        return [
            'mcq' => 'multiple_choice',
            'true_false' => 'true_false',
            'short' => 'short_answer',
            'essay' => 'essay',
            'fill_blank' => 'fill_blank',
        ][$type] ?? 'multiple_choice';
    }

    private static function assertPayloadSize(array $value, string $field): void
    {
        $encoded = json_encode($value);
        if ($encoded === false || strlen($encoded) > 65536) {
            throw new InvalidArgumentException("Structured {$field} exceeds the payload limit.");
        }
    }
}
