<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\OnlineExam;
use App\Models\OnlineExamQuestion;
use Illuminate\Support\Facades\DB;

$result = DB::transaction(function () {
    $teacher = DB::table('teacher_permissions as tp')
        ->join('users as u', 'u.id', '=', 'tp.teacher_id')
        ->join('subjects as s', function ($join) {
            $join->on('s.class_id', '=', 'tp.class_id')
                ->on('s.school_id', '=', 'tp.school_id');
        })
        ->select('u.id as teacher_id', 'u.school_id', 'tp.class_id', 's.id as subject_id')
        ->where('u.role_id', 3)
        ->whereNotNull('u.school_id')
        ->first();

    if (!$teacher) {
        throw new RuntimeException('No teacher with mapped subject/class assignment found.');
    }

    $now = now();

    $exam = OnlineExam::create([
        'school_id' => (int) $teacher->school_id,
        'title' => 'Teacher Practice Exam ' . $now->format('Ymd_His'),
        'subject_id' => (int) $teacher->subject_id,
        'class_id' => null,
        'exam_type' => 'quiz',
        'start_datetime' => $now->copy()->subHour(),
        'end_datetime' => $now->copy()->addDays(2),
        'duration_mins' => 45,
        'total_marks' => 50,
        'pass_mark' => 25,
        'max_attempts' => 1,
        'instructions' => 'Answer all questions. This exam is created for student login testing.',
        'workflow_state' => 'draft',
        'is_published' => 0,
        'auto_submit' => 1,
        'shuffle_questions' => 0,
        'shuffle_options' => 0,
        'allow_previous_navigation' => 1,
        'result_release_policy' => 'immediate',
        'webcam_required' => 0,
        'fullscreen_required' => 0,
        'created_by' => (int) $teacher->teacher_id,
        'creator_id' => (int) $teacher->teacher_id,
        'updater_id' => (int) $teacher->teacher_id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $questions = [
        [
            'question' => 'Which of these is a PHP framework?',
            'type' => 'mcq',
            'option_a' => 'Laravel',
            'option_b' => 'Django',
            'option_c' => 'Rails',
            'option_d' => 'Spring',
            'correct_ans' => 'a',
            'marks' => 10,
        ],
        [
            'question' => 'True or False: SQL is used to query relational databases.',
            'type' => 'true_false',
            'option_a' => 'True',
            'option_b' => 'False',
            'option_c' => null,
            'option_d' => null,
            'correct_ans' => 'true',
            'marks' => 5,
        ],
        [
            'question' => 'What command is used to create a migration in Laravel?',
            'type' => 'mcq',
            'option_a' => 'php artisan make:migration',
            'option_b' => 'php artisan migrate:new',
            'option_c' => 'php artisan db:create',
            'option_d' => 'php artisan schema:migration',
            'correct_ans' => 'a',
            'marks' => 10,
        ],
        [
            'question' => 'In your own words, explain what authentication means in a web app.',
            'type' => 'short',
            'option_a' => null,
            'option_b' => null,
            'option_c' => null,
            'option_d' => null,
            'correct_ans' => null,
            'marks' => 10,
        ],
        [
            'question' => 'Essay: Describe a complete flow for securing an online examination process.',
            'type' => 'essay',
            'option_a' => null,
            'option_b' => null,
            'option_c' => null,
            'option_d' => null,
            'correct_ans' => null,
            'marks' => 15,
        ],
    ];

    $questionIds = [];
    $sort = 1;
    foreach ($questions as $q) {
        $row = OnlineExamQuestion::create([
            'online_exam_id' => $exam->id,
            'question_bank_id' => null,
            'question' => $q['question'],
            'type' => $q['type'],
            'option_a' => $q['option_a'],
            'option_b' => $q['option_b'],
            'option_c' => $q['option_c'],
            'option_d' => $q['option_d'],
            'correct_ans' => $q['correct_ans'],
            'marks' => $q['marks'],
            'sort_order' => $sort++,
        ]);
        $questionIds[] = $row->id;
    }

    $exam->refresh();
    $errors = $exam->publicationReadinessErrors();

    if (!empty($errors)) {
        return [
            'created' => true,
            'published' => false,
            'exam_id' => $exam->id,
            'title' => $exam->title,
            'teacher_id' => (int) $teacher->teacher_id,
            'school_id' => (int) $teacher->school_id,
            'question_ids' => $questionIds,
            'question_marks_total' => (int) $exam->questions()->sum('marks'),
            'readiness_errors' => $errors,
        ];
    }

    $exam->update([
        'workflow_state' => 'published',
        'is_published' => 1,
        'reviewed_by' => (int) $teacher->teacher_id,
        'reviewed_at' => now(),
        'updater_id' => (int) $teacher->teacher_id,
    ]);

    return [
        'created' => true,
        'published' => true,
        'exam_id' => $exam->id,
        'title' => $exam->title,
        'teacher_id' => (int) $teacher->teacher_id,
        'school_id' => (int) $teacher->school_id,
        'question_ids' => $questionIds,
        'question_marks_total' => (int) $exam->questions()->sum('marks'),
        'readiness_errors' => [],
    ];
});

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
