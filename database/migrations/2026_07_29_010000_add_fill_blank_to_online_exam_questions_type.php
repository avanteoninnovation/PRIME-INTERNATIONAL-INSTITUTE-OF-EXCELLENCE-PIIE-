<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `online_exam_questions.type` was created as
 * enum('mcq','true_false','short','essay') — but the teacher-side question
 * form (resources/views/teacher/online_exam/question_form.blade.php and
 * questions.blade.php), the model's setTypeAttribute()/getNormalizedTypeAttribute()
 * mutators, and StoreOnlineExamQuestionRequest/UpdateOnlineExamQuestionRequest's
 * $dbTypeMap all already fully support a 'fill_blank' question type end to
 * end. Only the database column was never updated to allow it, so saving a
 * Fill Blank question silently truncated to an invalid/empty enum value
 * (this DB connection runs without STRICT_TRANS_TABLES) instead of raising an
 * error, making the question appear as the wrong type (or blank) afterward.
 * Adding the missing enum member is the minimal fix — every other layer
 * already expects it.
 */
return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE `online_exam_questions` MODIFY `type` ENUM('mcq','true_false','short','essay','fill_blank') NOT NULL DEFAULT 'mcq'");
    }

    public function down()
    {
        // Intentionally left as-is on rollback — narrowing the enum back
        // could fail against any row already saved with type='fill_blank'.
    }
};
