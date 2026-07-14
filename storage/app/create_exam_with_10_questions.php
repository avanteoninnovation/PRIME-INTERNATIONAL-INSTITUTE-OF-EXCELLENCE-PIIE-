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
        throw new RuntimeException('No teacher assignment found');
    }

    $now = now();
    $exam = OnlineExam::create([
        'school_id' => (int) $teacher->school_id,
        'title' => 'Teacher Follow-Along Exam ' . $now->format('Ymd_His'),
        'subject_id' => (int) $teacher->subject_id,
        'class_id' => null,
        'exam_type' => 'quiz',
        'start_datetime' => $now->copy()->subMinutes(5),
        'end_datetime' => $now->copy()->addDays(3),
        'duration_mins' => 60,
        'total_marks' => 100,
        'pass_mark' => 50,
        'max_attempts' => 1,
        'instructions' => 'Follow-along exam with 10 questions for student testing.',
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
        ['question' => 'Which protocol secures web traffic?', 'type' => 'mcq', 'option_a' => 'HTTP', 'option_b' => 'HTTPS', 'option_c' => 'FTP', 'option_d' => 'SMTP', 'correct_ans' => 'b', 'marks' => 10],
        ['question' => 'Which command lists Laravel routes?', 'type' => 'mcq', 'option_a' => 'php artisan route:list', 'option_b' => 'php artisan route:show', 'option_c' => 'php artisan routes', 'option_d' => 'php artisan list:routes', 'correct_ans' => 'a', 'marks' => 10],
        ['question' => 'What does SQL stand for?', 'type' => 'mcq', 'option_a' => 'Standard Query Language', 'option_b' => 'Structured Query Language', 'option_c' => 'Simple Query Logic', 'option_d' => 'System Query Language', 'correct_ans' => 'b', 'marks' => 10],
        ['question' => 'Which key links two tables?', 'type' => 'mcq', 'option_a' => 'Primary key', 'option_b' => 'Foreign key', 'option_c' => 'Unique key', 'option_d' => 'Index key', 'correct_ans' => 'b', 'marks' => 10],

        ['question' => 'True or False: Laravel uses MVC architecture.', 'type' => 'true_false', 'option_a' => 'True', 'option_b' => 'False', 'option_c' => null, 'option_d' => null, 'correct_ans' => 'true', 'marks' => 5],
        ['question' => 'True or False: GET requests should modify server state.', 'type' => 'true_false', 'option_a' => 'True', 'option_b' => 'False', 'option_c' => null, 'option_d' => null, 'correct_ans' => 'false', 'marks' => 5],

        ['question' => 'In one sentence, define authentication.', 'type' => 'short', 'option_a' => null, 'option_b' => null, 'option_c' => null, 'option_d' => null, 'correct_ans' => null, 'marks' => 10],
        ['question' => 'In one sentence, define authorization.', 'type' => 'short', 'option_a' => null, 'option_b' => null, 'option_c' => null, 'option_d' => null, 'correct_ans' => null, 'marks' => 10],
        ['question' => 'Briefly explain CSRF protection in Laravel.', 'type' => 'short', 'option_a' => null, 'option_b' => null, 'option_c' => null, 'option_d' => null, 'correct_ans' => null, 'marks' => 10],

        ['question' => 'Essay: Describe how to build a secure online exam platform including authentication, authorization, and monitoring.', 'type' => 'essay', 'option_a' => null, 'option_b' => null, 'option_c' => null, 'option_d' => null, 'correct_ans' => null, 'marks' => 20],
    ];

    $ids = [];
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
        $ids[] = $row->id;
    }

    $exam->refresh();
    $errors = $exam->publicationReadinessErrors();

    if (empty($errors)) {
        $exam->update([
            'workflow_state' => 'published',
            'is_published' => 1,
            'reviewed_by' => (int) $teacher->teacher_id,
            'reviewed_at' => now(),
            'updater_id' => (int) $teacher->teacher_id,
        ]);
    }

    return [
        'exam_id' => $exam->id,
        'title' => $exam->title,
        'teacher_id' => (int) $teacher->teacher_id,
        'school_id' => (int) $teacher->school_id,
        'question_ids' => $ids,
        'question_count' => count($ids),
        'question_total_marks' => (int) $exam->questions()->sum('marks'),
        'readiness_errors' => $errors,
        'published' => empty($errors),
    ];
});

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
