<?php

namespace Tests\Feature;

use App\Http\Requests\OnlineExam\StoreOnlineExamQuestionRequest;
use App\Http\Requests\OnlineExam\UpdateOnlineExamQuestionRequest;
use Tests\TestCase;

/**
 * Stability repair M7 — the question engine validates correct_ans up to 255
 * characters and stores short-answer reference answers there, but both
 * correct_ans columns were VARCHAR(5): on MySQL a longer answer failed with
 * "Data too long" (HTTP 500). SQLite does not enforce VARCHAR lengths, so this
 * contract test pins the agreement between validation and the MySQL schema
 * (verified on the MySQL test database: all eight supported types save).
 */
class QuestionAnswerColumnContractTest extends TestCase
{
    private const MIGRATION = 'database/migrations/2026_09_24_000002_widen_question_correct_ans_columns.php';

    public function test_the_widening_migration_covers_both_tables_at_255(): void
    {
        $sql = file_get_contents(base_path(self::MIGRATION));

        $this->assertStringContainsString("'question_banks', 'online_exam_questions'", $sql);
        $this->assertStringContainsString('MODIFY `correct_ans` VARCHAR(255) NULL', $sql);
    }

    public function test_validation_never_accepts_more_than_the_column_holds(): void
    {
        foreach ([StoreOnlineExamQuestionRequest::class, UpdateOnlineExamQuestionRequest::class] as $request) {
            $rules = (new $request())->rules();
            $max = null;
            foreach ((array) $rules['correct_ans'] as $rule) {
                if (is_string($rule) && str_starts_with($rule, 'max:')) {
                    $max = (int) substr($rule, 4);
                }
            }
            $this->assertNotNull($max, "{$request} limits correct_ans");
            $this->assertLessThanOrEqual(255, $max, $request);
        }

        $controller = file_get_contents(app_path('Http/Controllers/OnlineExamController.php'));
        preg_match_all("/'correct_ans'\s*=>\s*'nullable\|string\|max:(\d+)'/", $controller, $m);
        $this->assertNotEmpty($m[1], 'question bank validation found');
        foreach ($m[1] as $limit) {
            $this->assertLessThanOrEqual(255, (int) $limit);
        }
    }
}
