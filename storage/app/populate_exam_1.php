<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\OnlineExamController;
use App\Models\OnlineExam;
use App\Models\OnlineExamQuestion;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

$examId = 1;
$teacherId = 66;
$schoolId = 1;
$subjectId = 34;
$classId = 34;

$summary = [
    'exam_id' => $examId,
    'teacher_id' => $teacherId,
    'direct_question_ids' => [],
    'bank_question_ids' => [],
    'imported_question_ids' => [],
    'all_questions' => [],
    'publication_readiness_errors' => [],
    'total_question_marks' => 0,
    'warnings' => [],
    'snapshot_verification' => [],
];

DB::transaction(function () use (&$summary, $examId, $teacherId, $schoolId, $subjectId, $classId) {
    $exam = OnlineExam::findOrFail($examId);

    $exam->update([
        'school_id' => $schoolId,
        'subject_id' => $subjectId,
        'class_id' => $classId,
        'total_marks' => 100,
        'pass_mark' => 50,
        'duration_mins' => max(1, (int) $exam->duration_mins),
        'workflow_state' => 'draft',
        'is_published' => 0,
        'updater_id' => $teacherId,
        'updated_at' => now(),
    ]);

    OnlineExamQuestion::where('online_exam_id', $examId)->delete();

    $directPayloads = [
        [
            'question' => 'Which SQL clause is used to filter grouped results?',
            'type' => 'mcq',
            'option_a' => 'WHERE',
            'option_b' => 'HAVING',
            'option_c' => 'ORDER BY',
            'option_d' => 'LIMIT',
            'correct_ans' => 'b',
            'marks' => 10,
        ],
        [
            'question' => 'What does MVC stand for in web architecture?',
            'type' => 'mcq',
            'option_a' => 'Model View Controller',
            'option_b' => 'Module Version Cache',
            'option_c' => 'Memory View Compiler',
            'option_d' => 'Main Variable Class',
            'correct_ans' => 'a',
            'marks' => 10,
        ],
        [
            'question' => 'True or False: HTTPS encrypts data in transit between browser and server.',
            'type' => 'true_false',
            'option_a' => 'True',
            'option_b' => 'False',
            'correct_ans' => 'true',
            'marks' => 5,
        ],
        [
            'question' => '[Fill Blank] In Laravel, environment variables are defined in the _____ file.',
            'type' => 'short',
            'correct_ans' => '.env',
            'marks' => 10,
        ],
        [
            'question' => 'Briefly explain the difference between authentication and authorization.',
            'type' => 'short',
            'correct_ans' => null,
            'marks' => 10,
        ],
    ];

    $sort = 1;
    foreach ($directPayloads as $payload) {
        $q = OnlineExamQuestion::create(array_merge($payload, [
            'online_exam_id' => $examId,
            'question_bank_id' => null,
            'sort_order' => $sort++,
        ]));
        $summary['direct_question_ids'][] = $q->id;
    }

    $bankPayloads = [
        [
            'question' => 'Which protocol is primarily used to transfer web pages?',
            'type' => 'mcq',
            'option_a' => 'FTP',
            'option_b' => 'SMTP',
            'option_c' => 'HTTP',
            'option_d' => 'SSH',
            'correct_ans' => 'c',
            'marks' => 10,
        ],
        [
            'question' => 'Which command creates a new migration in Laravel?',
            'type' => 'mcq',
            'option_a' => 'php artisan make:model',
            'option_b' => 'php artisan make:migration',
            'option_c' => 'php artisan db:migrate',
            'option_d' => 'php artisan schema:create',
            'correct_ans' => 'b',
            'marks' => 10,
        ],
        [
            'question' => 'True or False: A foreign key can enforce referential integrity.',
            'type' => 'true_false',
            'option_a' => 'True',
            'option_b' => 'False',
            'correct_ans' => 'true',
            'marks' => 5,
        ],
        [
            'question' => '[Fill Blank] The default HTTP port is ____.',
            'type' => 'short',
            'correct_ans' => '80',
            'marks' => 10,
        ],
        [
            'question' => 'Essay: Design a secure online examination workflow for a school system and justify your choices.',
            'type' => 'essay',
            'correct_ans' => null,
            'marks' => 20,
        ],
    ];

    foreach ($bankPayloads as $payload) {
        $b = QuestionBank::create(array_merge($payload, [
            'school_id' => $schoolId,
            'subject_id' => $subjectId,
            'difficulty' => 'medium',
            'created_by' => $teacherId,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        $summary['bank_question_ids'][] = $b->id;
    }

    $teacher = User::findOrFail($teacherId);
    Auth::guard('web')->login($teacher);

    $controller = app(OnlineExamController::class);
    $reflection = new ReflectionClass($controller);
    $prop = $reflection->getProperty('school_id');
    $prop->setAccessible(true);
    $prop->setValue($controller, $schoolId);

    $request = Request::create(
        '/teacher/online-exams/' . $examId . '/question-bank/import',
        'POST',
        ['question_bank_ids' => $summary['bank_question_ids']]
    );

    $controller->teacherImportQuestion($request, $exam->fresh());

    $summary['imported_question_ids'] = OnlineExamQuestion::where('online_exam_id', $examId)
        ->whereIn('question_bank_id', $summary['bank_question_ids'])
        ->orderBy('id')
        ->pluck('id')
        ->all();

    if (count($summary['imported_question_ids']) !== 5) {
        $summary['warnings'][] = 'Expected 5 imported questions via workflow, found ' . count($summary['imported_question_ids']) . '.';
    }

    $firstBankId = $summary['bank_question_ids'][0];
    $linkedImported = OnlineExamQuestion::where('online_exam_id', $examId)
        ->where('question_bank_id', $firstBankId)
        ->first();

    $before = $linkedImported ? $linkedImported->question : null;
    QuestionBank::where('id', $firstBankId)->update([
        'question' => 'CHANGED BANK TEXT FOR SNAPSHOT TEST',
        'updated_at' => now(),
    ]);
    $after = $linkedImported ? OnlineExamQuestion::find($linkedImported->id)?->question : null;

    $summary['snapshot_verification'] = [
        'question_bank_id_tested' => $firstBankId,
        'imported_question_id_tested' => $linkedImported?->id,
        'before' => $before,
        'after' => $after,
        'snapshot_preserved' => $before !== null && $before === $after,
    ];

    $exam = OnlineExam::with('questions')->findOrFail($examId);
    $summary['publication_readiness_errors'] = $exam->publicationReadinessErrors();
    $summary['total_question_marks'] = (int) $exam->questions->sum('marks');

    foreach ($exam->questions()->orderBy('sort_order')->orderBy('id')->get() as $q) {
        $summary['all_questions'][] = [
            'id' => $q->id,
            'type' => $q->type,
            'normalized_type' => $q->normalized_type,
            'marks' => (int) $q->marks,
            'source' => $q->question_bank_id ? 'imported' : 'direct',
            'question_bank_id' => $q->question_bank_id,
        ];
    }

    $typeCounts = collect($summary['all_questions'])->groupBy('normalized_type')->map->count()->all();
    $expected = [
        'multiple_choice' => 4,
        'true_false' => 2,
        'short_answer' => 3,
        'essay' => 1,
    ];
    foreach ($expected as $type => $count) {
        if (($typeCounts[$type] ?? 0) !== $count) {
            $summary['warnings'][] = 'Distribution mismatch for ' . $type . ': expected ' . $count . ', got ' . ($typeCounts[$type] ?? 0) . '.';
        }
    }

    if ($summary['total_question_marks'] !== 100) {
        $summary['warnings'][] = 'Total question marks mismatch: expected 100, got ' . $summary['total_question_marks'] . '.';
    }

    $summary['warnings'][] = 'Schema constraint: question type enum currently supports only mcq,true_false,short,essay; fill_blank was stored as short with fill-blank wording.';
    $summary['warnings'][] = 'Auto-grading for fill_blank is not available in current objective scorer; only mcq and true_false are auto-graded.';

    if (!empty($summary['publication_readiness_errors'])) {
        $summary['warnings'][] = 'Publication readiness returned errors.';
    }

    $hasAttempts = DB::table('online_exam_submissions')->where('online_exam_id', $examId)->exists();
    if ($hasAttempts) {
        $summary['warnings'][] = 'Exam has submissions; expected none for this setup task.';
    }

    Auth::guard('web')->logout();
});

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
