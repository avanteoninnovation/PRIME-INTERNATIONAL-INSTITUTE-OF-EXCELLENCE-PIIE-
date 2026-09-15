<?php

namespace Tests\Feature;

use App\Models\QuestionBank;
use App\Models\Subject;
use App\Support\OnlineExams\QuestionBankImporter;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

class QuestionBankImporterTest extends TestCase
{
    use AdmissionsTestHelper;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
        $this->schoolId = $this->makeSchool();
    }

    private function csvFile(string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'qbi') . '.csv';
        file_put_contents($path, $content);

        return new UploadedFile($path, 'import.csv', 'text/csv', null, true);
    }

    public function test_it_imports_valid_rows_of_every_type(): void
    {
        $csv = "question,type,option_a,option_b,option_c,option_d,correct_ans,marks,difficulty,subject\n"
            . "\"What is 2+2?\",mcq,3,4,5,6,b,2,easy,\n"
            . "\"The sky is blue.\",true_false,,,,,true,1,,\n"
            . "\"Explain gravity.\",essay,,,,,,10,hard,\n";

        $result = QuestionBankImporter::import($this->csvFile($csv), $this->schoolId, null);

        $this->assertSame(3, $result['imported']);
        $this->assertEmpty($result['errors']);
        $this->assertSame(3, QuestionBank::where('school_id', $this->schoolId)->count());

        $mcq = QuestionBank::where('question', 'What is 2+2?')->first();
        $this->assertSame('mcq', $mcq->type);
        $this->assertSame('b', $mcq->correct_ans);
        $this->assertSame(2, $mcq->marks);
        $this->assertSame('easy', $mcq->difficulty);

        // difficulty defaults to medium when left blank
        $trueFalse = QuestionBank::where('question', 'The sky is blue.')->first();
        $this->assertSame('medium', $trueFalse->difficulty);
    }

    public function test_it_accepts_type_synonyms(): void
    {
        $csv = "question,type,option_a,option_b,correct_ans,marks\n"
            . "\"Q1\",multiple_choice,a,b,a,1\n"
            . "\"Q2\",short_answer,,,,1\n";

        $result = QuestionBankImporter::import($this->csvFile($csv), $this->schoolId, null);

        $this->assertSame(2, $result['imported']);
        $this->assertSame('mcq', QuestionBank::where('question', 'Q1')->value('type'));
        $this->assertSame('short', QuestionBank::where('question', 'Q2')->value('type'));
    }

    public function test_it_skips_invalid_rows_and_reports_them_without_failing_the_whole_file(): void
    {
        $csv = "question,type,option_a,option_b,correct_ans,marks\n"
            . "\"Good row\",mcq,a,b,a,2\n"
            . ",mcq,a,b,a,2\n" // missing question
            . "\"Bad type\",not_a_type,a,b,a,2\n"
            . "\"One Option\",mcq,onlyone,,x,2\n" // mcq needs >= 2 options
            . "\"Zero Marks\",essay,,,,0\n"; // marks must be >= 1

        $result = QuestionBankImporter::import($this->csvFile($csv), $this->schoolId, null);

        $this->assertSame(1, $result['imported']);
        $this->assertCount(4, $result['errors']);
        $this->assertStringContainsString('Row 3', $result['errors'][0]);
        $this->assertSame(1, QuestionBank::count());
        $this->assertSame('Good row', QuestionBank::first()->question);
    }

    public function test_a_row_can_report_more_than_one_problem(): void
    {
        $csv = "question,type,option_a,option_b,correct_ans,marks\n"
            . "\"Broken MCQ\",mcq,onlyone,,,\n"; // too few options AND missing correct_ans AND missing marks

        $result = QuestionBankImporter::import($this->csvFile($csv), $this->schoolId, null);

        $this->assertSame(0, $result['imported']);
        $this->assertGreaterThanOrEqual(2, count($result['errors']));
        foreach ($result['errors'] as $error) {
            $this->assertStringStartsWith('Row 2:', $error);
        }
    }

    public function test_it_matches_subject_by_name_case_insensitively(): void
    {
        Subject::create(['name' => 'Mathematics', 'class_id' => 1, 'school_id' => $this->schoolId, 'session_id' => 1]);

        $csv = "question,type,correct_ans,marks,subject\n"
            . "\"Q1\",short,,1,mathematics\n";

        $result = QuestionBankImporter::import($this->csvFile($csv), $this->schoolId, null);

        $this->assertSame(1, $result['imported']);
        $this->assertEmpty($result['warnings']);
        $subjectId = Subject::where('name', 'Mathematics')->value('id');
        $this->assertEquals($subjectId, QuestionBank::first()->subject_id);
    }

    public function test_an_unmatched_subject_name_is_a_warning_not_a_blocking_error(): void
    {
        $csv = "question,type,correct_ans,marks,subject\n"
            . "\"Q1\",short,,1,Nonexistent Subject\n";

        $result = QuestionBankImporter::import($this->csvFile($csv), $this->schoolId, null);

        $this->assertSame(1, $result['imported']);
        $this->assertEmpty($result['errors']);
        $this->assertCount(1, $result['warnings']);
        $this->assertNull(QuestionBank::first()->subject_id);
    }

    public function test_it_ignores_blank_lines(): void
    {
        $csv = "question,type,correct_ans,marks\n"
            . "\"Q1\",short,,1\n"
            . "\n"
            . "\"Q2\",short,,1\n";

        $result = QuestionBankImporter::import($this->csvFile($csv), $this->schoolId, null);

        $this->assertSame(2, $result['imported']);
        $this->assertEmpty($result['errors']);
    }

    public function test_only_questions_for_the_importing_school_are_created(): void
    {
        $otherSchoolId = $this->makeSchool();

        $csv = "question,type,correct_ans,marks\n\"Q1\",short,,1\n";
        QuestionBankImporter::import($this->csvFile($csv), $this->schoolId, null);

        $this->assertSame(1, QuestionBank::where('school_id', $this->schoolId)->count());
        $this->assertSame(0, QuestionBank::where('school_id', $otherSchoolId)->count());
    }
}
