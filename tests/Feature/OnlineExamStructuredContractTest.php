<?php

namespace Tests\Feature;

use App\Models\OnlineExamAnswer;
use App\Models\OnlineExamQuestion;
use App\Models\QuestionBank;
use App\Support\OnlineExams\AnswerContract;
use App\Support\OnlineExams\QuestionContract;
use App\Support\OnlineExams\OnlineExamMarking;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\Feature\Support\OnlineExamTestHelper;
use Tests\TestCase;

class OnlineExamStructuredContractTest extends TestCase
{
    use OnlineExamTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootOnlineExamTestSchema();
    }

    public function test_legacy_question_and_answer_contracts_remain_compatible(): void
    {
        $question = new OnlineExamQuestion([
            'type' => 'mcq', 'question' => 'Legacy prompt',
            'option_a' => 'Yes', 'option_b' => 'No', 'correct_ans' => 'a', 'marks' => 2,
        ]);

        $public = QuestionContract::publicProjection($question);
        $answer = AnswerContract::fromRequest(['selected_option' => 'A'], $question);

        $this->assertNull($public['schema_version']);
        $this->assertSame('multiple_choice', $public['type']);
        $this->assertSame('a', $answer['selected_option_id']);
        $this->assertArrayNotHasKey('marking', $public);
    }

    public function test_structured_question_projection_excludes_server_marking_configuration(): void
    {
        $question = new OnlineExamQuestion([
            'question_schema_version' => QuestionContract::STRUCTURED_VERSION,
            'question_config' => json_encode([
                'type' => 'multiple_select',
                'prompt' => 'Choose the correct options',
                'options' => [
                    ['id' => 'a', 'label' => 'One'],
                    ['id' => 'b', 'label' => 'Two'],
                ],
            ]),
            'marking_config' => json_encode(['correct_option_ids' => ['a'], 'max_marks' => 2]),
            'marks' => 2,
        ]);

        $public = QuestionContract::publicProjection($question);
        $server = QuestionContract::normalize($question, true);

        $this->assertSame('multiple_select', $public['type']);
        $this->assertArrayNotHasKey('marking', $public);
        $this->assertArrayNotHasKey('correct_option_ids', $public);
        $this->assertSame(['a'], $server['marking']['correct_option_ids']);
    }

    public function test_structured_contract_rejects_malformed_or_unsupported_data(): void
    {
        $malformed = new OnlineExamQuestion([
            'question_schema_version' => QuestionContract::STRUCTURED_VERSION,
            'question_config' => '{not-json',
            'marking_config' => '{}',
        ]);

        $this->expectException(InvalidArgumentException::class);
        QuestionContract::publicProjection($malformed);
    }

    public function test_structured_answer_rejects_forged_option_and_accepts_server_option(): void
    {
        $question = new OnlineExamQuestion([
            'question_schema_version' => QuestionContract::STRUCTURED_VERSION,
            'question_config' => json_encode([
                'type' => 'single_choice', 'prompt' => 'Prompt',
                'options' => [['id' => 'a', 'label' => 'A'], ['id' => 'b', 'label' => 'B']],
            ]),
            'marking_config' => json_encode(['correct_option_ids' => ['a']]),
        ]);

        $this->assertSame('b', AnswerContract::fromRequest([
            'answer_payload' => ['type' => 'single_choice', 'selected_option_id' => 'b'],
        ], $question)['selected_option_id']);

        $this->expectException(InvalidArgumentException::class);
        AnswerContract::fromRequest([
            'answer_payload' => ['type' => 'single_choice', 'selected_option_id' => 'forged'],
        ], $question);
    }

    public function test_structured_snapshot_is_independent_of_later_bank_edits(): void
    {
        $bank = QuestionBank::create([
            'school_id' => 1, 'question' => 'Original', 'type' => 'mcq', 'marks' => 2,
            'difficulty' => 'easy', 'question_schema_version' => QuestionContract::STRUCTURED_VERSION,
            'question_config' => json_encode(['type' => 'single_choice', 'prompt' => 'Original', 'options' => [['id' => 'a', 'label' => 'A']]]),
            'marking_config' => json_encode(['correct_option_ids' => ['a']]),
        ]);

        $snapshot = OnlineExamQuestion::create([
            'online_exam_id' => 1, 'question_bank_id' => $bank->id, 'question' => $bank->question,
            'type' => $bank->type, 'marks' => $bank->marks,
            'question_schema_version' => $bank->question_schema_version,
            'question_config' => $bank->question_config, 'marking_config' => $bank->marking_config,
        ]);

        $bank->update(['question' => 'Changed later', 'question_config' => json_encode(['type' => 'single_choice', 'prompt' => 'Changed', 'options' => [['id' => 'a', 'label' => 'Changed']]])]);

        $this->assertSame('Original', $snapshot->fresh()->question);
        $this->assertStringContainsString('Original', $snapshot->fresh()->question_config);
    }

    public function test_multiple_select_authoring_requires_two_correct_options_and_uses_stable_ids(): void
    {
        $contract = QuestionContract::authoring('multiple_select', 'Select all', [
            ['id' => 'a', 'label' => 'Alpha'], ['id' => 'b', 'label' => 'Beta'], ['id' => 'c', 'label' => 'Gamma'],
        ], ['correct_option_ids' => ['a', 'c']], 4);

        $question = new OnlineExamQuestion([
            'question_schema_version' => $contract['schema_version'],
            'question_config' => $contract['question_config'],
            'marking_config' => $contract['marking_config'],
            'marks' => 4,
        ]);
        $server = QuestionContract::normalize($question, true);
        $public = QuestionContract::publicProjection($question);
        $this->assertSame('multiple_select', $public['type']);
        $this->assertSame(['a', 'c'], $server['marking']['correct_option_ids']);
        $this->assertArrayNotHasKey('marking', $public);
    }

    public function test_multiple_select_answers_are_canonicalized_and_duplicates_are_rejected(): void
    {
        $question = new OnlineExamQuestion([
            'question_schema_version' => 2,
            'question_config' => json_encode(['type' => 'multiple_select', 'prompt' => 'Pick', 'options' => [
                ['id' => 'a', 'label' => 'A'], ['id' => 'b', 'label' => 'B'], ['id' => 'c', 'label' => 'C'],
            ]]),
            'marking_config' => json_encode(['mode' => 'all_or_nothing', 'correct_option_ids' => ['a', 'c']]),
        ]);

        $answer = AnswerContract::fromRequest(['answer_payload' => ['type' => 'multiple_select', 'selected_option_ids' => ['c', 'a']]], $question);
        $this->assertSame(['a', 'c'], $answer['selected_option_ids']);
        $this->expectException(InvalidArgumentException::class);
        AnswerContract::fromRequest(['answer_payload' => ['type' => 'multiple_select', 'selected_option_ids' => ['a', 'a']]], $question);
    }

    public function test_multiple_select_marking_is_all_or_nothing_regardless_of_selection_order(): void
    {
        $question = new OnlineExamQuestion([
            'question_schema_version' => 2,
            'question_config' => json_encode(['type' => 'multiple_select', 'prompt' => 'Pick', 'options' => [
                ['id' => 'a', 'label' => 'A'], ['id' => 'b', 'label' => 'B'], ['id' => 'c', 'label' => 'C'],
            ]]),
            'marking_config' => json_encode(['mode' => 'all_or_nothing', 'correct_option_ids' => ['a', 'c']]),
            'marks' => 5,
        ]);
        $server = QuestionContract::normalize($question, true);
        $correct = ['a', 'c']; sort($correct);
        $ordered = ['c', 'a']; sort($ordered);
        $this->assertSame($correct, $ordered);
        $this->assertNotSame($correct, ['a']);
        $this->assertNotSame($correct, ['a', 'b', 'c']);
        $this->assertSame('all_or_nothing', $server['marking']['mode']);
    }

    public function test_numeric_authoring_and_tolerance_boundaries(): void
    {
        $contract = QuestionContract::authoring('numeric', 'Value?', [], ['target' => '10', 'tolerance' => '0.5'], 3);
        $question = new OnlineExamQuestion([
            'question_schema_version' => $contract['schema_version'],
            'question_config' => $contract['question_config'],
            'marking_config' => $contract['marking_config'],
            'marks' => 3,
        ]);
        $server = QuestionContract::normalize($question, true);
        $public = QuestionContract::publicProjection($question);
        $this->assertSame('numeric', $public['type']);
        $this->assertArrayNotHasKey('marking', $public);
        $this->assertEquals(10.0, $server['marking']['target']);
        $this->assertEquals(0.5, $server['marking']['tolerance']);
        foreach ([9.5, 10.0, 10.5] as $value) {
            $this->assertTrue(abs($value - $server['marking']['target']) <= $server['marking']['tolerance'] + 1e-12);
        }
        $this->assertFalse(abs(9.49 - 10.0) <= 0.5 + 1e-12);
        $this->assertFalse(abs(10.51 - 10.0) <= 0.5 + 1e-12);
    }

    public function test_numeric_answers_normalize_and_reject_non_finite_values(): void
    {
        $question = new OnlineExamQuestion([
            'question_schema_version' => 2,
            'question_config' => json_encode(['type' => 'numeric', 'prompt' => 'Value', 'options' => []]),
            'marking_config' => json_encode(['mode' => 'absolute_tolerance', 'target' => 10, 'tolerance' => 0]),
        ]);
        $answer = AnswerContract::fromRequest(['answer_payload' => ['type' => 'numeric', 'value' => '10.5']], $question);
        $this->assertSame(10.5, $answer['value']);
        $this->expectException(InvalidArgumentException::class);
        AnswerContract::fromRequest(['answer_payload' => ['type' => 'numeric', 'value' => '1e999']], $question);
    }

    public function test_structured_answer_cannot_include_marks_or_answer_key_fields(): void
    {
        $question = new OnlineExamQuestion([
            'question_schema_version' => 2,
            'question_config' => json_encode(['type' => 'numeric', 'prompt' => 'Value', 'options' => []]),
            'marking_config' => json_encode(['mode' => 'absolute_tolerance', 'target' => 10, 'tolerance' => 0]),
        ]);
        $this->expectException(InvalidArgumentException::class);
        AnswerContract::fromRequest(['answer_payload' => ['type' => 'numeric', 'value' => 10, 'awarded_marks' => 99]], $question);
    }

    public function test_structured_answers_round_trip_from_autosave_storage_for_all_objective_types(): void
    {
        $cases = [
            ['type' => 'single_choice', 'question' => ['type' => 'single_choice', 'prompt' => 'Pick', 'options' => [['id' => 'a', 'label' => 'A']]], 'marking' => ['correct_option_ids' => ['a']], 'answer' => ['type' => 'single_choice', 'selected_option_id' => 'a']],
            ['type' => 'multiple_select', 'question' => ['type' => 'multiple_select', 'prompt' => 'Pick', 'options' => [['id' => 'a', 'label' => 'A'], ['id' => 'b', 'label' => 'B']]], 'marking' => ['correct_option_ids' => ['a', 'b']], 'answer' => ['type' => 'multiple_select', 'selected_option_ids' => ['a', 'b']]],
            ['type' => 'true_false', 'question' => ['type' => 'true_false', 'prompt' => 'True?', 'options' => []], 'marking' => ['correct_value' => true], 'answer' => ['type' => 'true_false', 'value' => true]],
            ['type' => 'numeric', 'question' => ['type' => 'numeric', 'prompt' => 'Value', 'options' => []], 'marking' => ['target' => 10, 'tolerance' => 0], 'answer' => ['type' => 'numeric', 'value' => 10]],
            ['type' => 'matching', 'question' => ['type' => 'matching', 'prompt' => 'Match', 'left_items' => [['id' => 'l1', 'text' => 'A'], ['id' => 'l2', 'text' => 'B']], 'right_items' => [['id' => 'r1', 'text' => '1'], ['id' => 'r2', 'text' => '2']]], 'marking' => ['pairs' => ['l1' => 'r1', 'l2' => 'r2']], 'answer' => ['type' => 'matching', 'pairs' => ['l1' => 'r1', 'l2' => 'r2']]],
            ['type' => 'ordering', 'question' => ['type' => 'ordering', 'prompt' => 'Order', 'items' => [['id' => 'i1', 'text' => 'A'], ['id' => 'i2', 'text' => 'B']], 'marks' => 1], 'marking' => ['correct_order' => ['i1', 'i2']], 'answer' => ['type' => 'ordering', 'ordered_ids' => ['i1', 'i2']]],
            ['type' => 'fill_blank', 'question' => ['type' => 'fill_blank', 'prompt' => 'Value [[blank_1]]', 'blanks' => [['id' => 'blank_1']]], 'marking' => ['blanks' => ['blank_1' => ['accepted_answers' => ['A']]]], 'answer' => ['type' => 'fill_blank', 'blanks' => ['blank_1' => 'A']]],
        ];

        foreach ($cases as $case) {
            $question = new OnlineExamQuestion([
                'question_schema_version' => 2,
                'question_config' => json_encode($case['question']),
                'marking_config' => json_encode($case['marking']),
            ]);
            $canonical = AnswerContract::fromRequest(['answer_payload' => $case['answer']], $question);
            $stored = new \stdClass();
            $stored->answer_schema_version = 2;
            $stored->answer_payload = AnswerContract::encode($canonical);
            $roundTrip = AnswerContract::fromStored($stored, $question);
            $this->assertSame($canonical, $roundTrip, $case['type']);
        }
    }

    public function test_advanced_fill_blank_contract_supports_multiple_blanks_and_hides_answers(): void
    {
        $contract = QuestionContract::authoring('fill_blank', 'The capital is [[blank_1]] and the country is [[blank_2]].', [
            ['id' => 'blank_1', 'accepted_answers' => ['Kampala', 'kampala']],
            ['id' => 'blank_2', 'accepted_answers' => ['Uganda', 'Republic of Uganda']],
        ], ['case_sensitive' => false, 'trim_whitespace' => true], 4);
        $question = new OnlineExamQuestion([
            'question_schema_version' => 2,
            'question_config' => $contract['question_config'],
            'marking_config' => $contract['marking_config'],
            'marks' => 4,
        ]);
        $public = QuestionContract::publicProjection($question);
        $server = QuestionContract::normalize($question, true);
        $this->assertSame(['blank_1', 'blank_2'], array_column($public['blanks'], 'id'));
        $this->assertArrayNotHasKey('marking', $public);
        $this->assertArrayNotHasKey('accepted_answers', $public);
        $this->assertSame(['Kampala', 'kampala'], $server['marking']['blanks']['blank_1']['accepted_answers']);
    }

    public function test_advanced_fill_blank_rejects_unknown_or_duplicate_prompt_blanks(): void
    {
        $this->expectException(InvalidArgumentException::class);
        QuestionContract::authoring('fill_blank', 'Value [[blank_1]] [[blank_1]].', [
            ['id' => 'blank_1', 'accepted_answers' => ['value']],
        ], [], 2);
    }

    public function test_advanced_fill_blank_answers_are_validated_and_marked_all_or_nothing(): void
    {
        $contract = QuestionContract::authoring('fill_blank', 'The answer is [[blank_1]] and [[blank_2]].', [
            ['id' => 'blank_1', 'accepted_answers' => ['DNA']],
            ['id' => 'blank_2', 'accepted_answers' => ['leaves', 'leaf']],
        ], ['case_sensitive' => true, 'trim_whitespace' => true], 4);
        $question = new OnlineExamQuestion([
            'question_schema_version' => 2,
            'question_config' => $contract['question_config'],
            'marking_config' => $contract['marking_config'],
        ]);
        $correct = AnswerContract::fromRequest(['answer_payload' => ['type' => 'fill_blank', 'blanks' => ['blank_2' => ' leaves ', 'blank_1' => 'DNA']]], $question);
        $this->assertSame(['blank_1' => 'DNA', 'blank_2' => ' leaves '], $correct['blanks']);
        $this->assertTrue(QuestionContract::fillBlankCorrect(QuestionContract::normalize($question, true), $correct));
        $this->assertFalse(QuestionContract::fillBlankCorrect(QuestionContract::normalize($question, true), ['type' => 'fill_blank', 'blanks' => ['blank_1' => 'DNA', 'blank_2' => 'leafless']]));
        $this->expectException(InvalidArgumentException::class);
        AnswerContract::fromRequest(['answer_payload' => ['type' => 'fill_blank', 'blanks' => ['forged' => 'DNA']]], $question);
    }

    public function test_advanced_fill_blank_case_insensitive_and_whitespace_rules(): void
    {
        $contract = QuestionContract::authoring('fill_blank', 'Capital: [[blank_1]]', [
            ['id' => 'blank_1', 'accepted_answers' => ['Kampala']],
        ], ['case_sensitive' => false, 'trim_whitespace' => true], 1);
        $question = new OnlineExamQuestion(['question_schema_version' => 2, 'question_config' => $contract['question_config'], 'marking_config' => $contract['marking_config']]);
        $normalized = QuestionContract::normalize($question, true);
        $this->assertTrue(QuestionContract::fillBlankCorrect($normalized, ['type' => 'fill_blank', 'blanks' => ['blank_1' => ' Kampala ']]));
        $this->assertTrue(QuestionContract::fillBlankCorrect($normalized, ['type' => 'fill_blank', 'blanks' => ['blank_1' => 'KAMPALA']]));
    }

    public function test_advanced_fill_blank_question_bank_snapshot_is_immutable(): void
    {
        $contract = QuestionContract::authoring('fill_blank', 'Capital [[blank_1]]', [
            ['id' => 'blank_1', 'accepted_answers' => ['Kampala']],
        ], [], 2);
        $bank = QuestionBank::create([
            'school_id' => 1, 'question' => 'Capital [[blank_1]]', 'type' => 'fill_blank', 'marks' => 2,
            'difficulty' => 'easy', 'question_schema_version' => 2,
            'question_config' => $contract['question_config'], 'marking_config' => $contract['marking_config'],
        ]);
        $snapshot = OnlineExamQuestion::create([
            'online_exam_id' => 1, 'question_bank_id' => $bank->id, 'question' => $bank->question,
            'type' => 'fill_blank', 'marks' => $bank->marks, 'question_schema_version' => $bank->question_schema_version,
            'question_config' => $bank->question_config, 'marking_config' => $bank->marking_config,
        ]);
        $bank->update(['question_config' => json_encode(['type' => 'fill_blank', 'prompt' => 'Changed [[blank_1]]', 'blanks' => [['id' => 'blank_1']]])]);
        $this->assertStringContainsString('Capital', $snapshot->fresh()->question_config);
    }

    public function test_matching_contract_hides_pair_map_and_marks_exact_sets(): void
    {
        $c=QuestionContract::authoring('matching','Match countries',[['left_id'=>'l1','left_text'=>'Uganda','right_id'=>'r1','right_text'=>'Kampala'],['left_id'=>'l2','left_text'=>'Kenya','right_id'=>'r2','right_text'=>'Nairobi']],[],2);
        $q=new OnlineExamQuestion(['question_schema_version'=>2,'question_config'=>$c['question_config'],'marking_config'=>$c['marking_config']]);
        $public=QuestionContract::publicProjection($q); $server=QuestionContract::normalize($q,true);
        $this->assertArrayNotHasKey('marking',$public); $this->assertArrayHasKey('left_items',$public); $this->assertSame(['l1'=>'r1','l2'=>'r2'],$server['marking']['pairs']);
        $answer=AnswerContract::fromRequest(['answer_payload'=>['type'=>'matching','pairs'=>['l2'=>'r2','l1'=>'r1']]],$q);
        $this->assertTrue(QuestionContract::matchingCorrect($server,$answer));
        $this->expectException(InvalidArgumentException::class); AnswerContract::fromRequest(['answer_payload'=>['type'=>'matching','pairs'=>['l1'=>'r1','bad'=>'r2']]],$q);
    }

    public function test_ordering_contract_hides_correct_order_and_requires_exact_sequence(): void
    {
        $c=QuestionContract::authoring('ordering','Arrange',[['id'=>'i1','text'=>'First'],['id'=>'i2','text'=>'Second'],['id'=>'i3','text'=>'Third']],[],3);
        $q=new OnlineExamQuestion(['question_schema_version'=>2,'question_config'=>$c['question_config'],'marking_config'=>$c['marking_config']]);
        $public=QuestionContract::publicProjection($q); $server=QuestionContract::normalize($q,true);
        $this->assertArrayNotHasKey('marking',$public); $this->assertArrayNotHasKey('correct_order',$public);
        $answer=AnswerContract::fromRequest(['answer_payload'=>['type'=>'ordering','ordered_ids'=>['i1','i2','i3']]],$q);
        $this->assertTrue(QuestionContract::orderingCorrect($server,$answer));
        $this->assertFalse(QuestionContract::orderingCorrect($server,['ordered_ids'=>['i2','i1','i3']]));
        $this->expectException(InvalidArgumentException::class); AnswerContract::fromRequest(['answer_payload'=>['type'=>'ordering','ordered_ids'=>['i1','i1']]],$q);
    }

    public function test_matching_partial_autosave_and_incomplete_final_mark_are_safe(): void
    {
        $c=QuestionContract::authoring('matching','Match',[['left_id'=>'l1','left_text'=>'A','right_id'=>'r1','right_text'=>'1'],['left_id'=>'l2','left_text'=>'B','right_id'=>'r2','right_text'=>'2']],[],2); $q=new OnlineExamQuestion(['question_schema_version'=>2,'question_config'=>$c['question_config'],'marking_config'=>$c['marking_config']]);
        $partial=AnswerContract::fromRequest(['answer_payload'=>['type'=>'matching','pairs'=>['l1'=>'r1']]],$q); $this->assertSame(['l1'=>'r1'],$partial['pairs']); $this->assertFalse(QuestionContract::matchingCorrect(QuestionContract::normalize($q,true),$partial));
        $this->expectException(InvalidArgumentException::class); AnswerContract::fromRequest(['answer_payload'=>['type'=>'matching','pairs'=>['l1'=>'r1','l2'=>'r1']]],$q);
    }

    public function test_ordering_partial_autosave_and_unknown_ids_are_rejected(): void
    {
        $c=QuestionContract::authoring('ordering','Order',[['id'=>'i1','text'=>'A'],['id'=>'i2','text'=>'B'],['id'=>'i3','text'=>'C']],[],2); $q=new OnlineExamQuestion(['question_schema_version'=>2,'question_config'=>$c['question_config'],'marking_config'=>$c['marking_config']]);
        $partial=AnswerContract::fromRequest(['answer_payload'=>['type'=>'ordering','ordered_ids'=>['i2']]],$q); $this->assertSame(['i2'],$partial['ordered_ids']); $this->assertFalse(QuestionContract::orderingCorrect(QuestionContract::normalize($q,true),$partial));
        $this->expectException(InvalidArgumentException::class); AnswerContract::fromRequest(['answer_payload'=>['type'=>'ordering','ordered_ids'=>['forged']]],$q);
    }

    public function test_matching_and_ordering_snapshots_keep_structured_contracts(): void
    {
        foreach (['matching','ordering'] as $type) {
            $rows=$type==='matching'?[['left_id'=>'l1','left_text'=>'A','right_id'=>'r1','right_text'=>'1'],['left_id'=>'l2','left_text'=>'B','right_id'=>'r2','right_text'=>'2']]:[['id'=>'i1','text'=>'A'],['id'=>'i2','text'=>'B']];
            $c=QuestionContract::authoring($type,'Snapshot',$rows,[],2); $q=new OnlineExamQuestion(['question_schema_version'=>2,'question_config'=>$c['question_config'],'marking_config'=>$c['marking_config']]); $before=QuestionContract::publicProjection($q); $this->assertSame(2,$before['schema_version']); $this->assertArrayNotHasKey('marking',$before); $this->assertStringNotContainsString('correct_order',json_encode($before));
        }
    }

}
