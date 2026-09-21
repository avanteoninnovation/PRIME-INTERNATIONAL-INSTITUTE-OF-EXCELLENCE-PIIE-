<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('online_exam_answers', function (Blueprint $table) {
            $table->unsignedInteger('answer_revision')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('online_exam_answers', function (Blueprint $table) {
            $table->dropColumn('answer_revision');
        });
    }
};
