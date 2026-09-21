<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleShellConsistencyTest extends TestCase
{
    public function test_online_exam_views_use_their_authoritative_role_shells(): void
    {
        foreach (['list', 'instructions', 'take', 'result'] as $view) {
            $contents = file_get_contents(resource_path("views/student/online_exam/{$view}.blade.php"));
            $this->assertStringContainsString("@extends('student.navigation')", $contents);
            $this->assertStringNotContainsString("@extends('admin.navigation')", $contents);
        }

        $this->assertStringContainsString("@extends('student.navigation')", file_get_contents(resource_path('views/student/dashboard.blade.php')));
        $this->assertStringContainsString("@extends('teacher.navigation')", file_get_contents(resource_path('views/teacher/online_exam/index.blade.php')));
        $this->assertStringContainsString("@extends('admin.navigation')", file_get_contents(resource_path('views/admin/online_exam/index.blade.php')));
    }

    public function test_role_online_exam_routes_keep_role_middleware(): void
    {
        $this->assertContains('student', Route::getRoutes()->getByName('student.online_exam.list')->middleware());
        $this->assertContains('teacher', Route::getRoutes()->getByName('teacher.online_exams.index')->middleware());
        $this->assertContains('admin', Route::getRoutes()->getByName('admin.online_exams.index')->middleware());
    }
}
