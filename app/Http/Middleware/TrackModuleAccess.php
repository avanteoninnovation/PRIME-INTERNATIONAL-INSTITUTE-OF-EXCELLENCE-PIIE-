<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Passively records meaningful module/page access for authenticated users,
 * so the owner can trace "who went where, and when" without every
 * controller needing to call AuditLog::record() itself.
 *
 * Deliberately excludes: guests (nothing to attribute), non-GET requests
 * (mutations are covered by AuditableObserver / existing manual calls),
 * AJAX/JSON requests (this app's modals fetch via $.ajax, not full page
 * loads — logging every modal open would be noise, not "meaningful
 * module/page access"), non-2xx responses, and a short list of routes that
 * are never a meaningful destination in their own right (auth pages,
 * health/asset-ish endpoints, the cache-clear utility route).
 *
 * Runs in terminate() so the response is never delayed by the audit write.
 */
class TrackModuleAccess
{
    private const ROUTE_NAME_NOISE = [
        'login', 'logout', 'logout.get', 'register', 'password.', 'verification.',
        'clear.cache', 'landingPage', 'website.', 'download.brochure',
    ];

    private const EXPORT_HINTS = ['export', 'csv', 'print', '_pdf', 'pdf_', '.pdf'];

    /**
     * @var array<string, string>
     */
    private const MODULE_LABELS = [
        'dashboard'          => 'Dashboard',
        'student'            => 'Students',
        'teacher'            => 'Staff',
        'accountant'         => 'Staff',
        'librarian'          => 'Staff',
        'warden'             => 'Staff',
        'admin'              => 'Staff',
        'programmes'         => 'Programmes',
        'hei_admissions'     => 'Admissions',
        'intake_sessions'    => 'Intakes',
        'admissions_agents'  => 'Agents',
        'fee_structures'     => 'Finance',
        'payroll'            => 'Payroll',
        'salary_structures'  => 'Payroll',
        'assets'             => 'Assets',
        'asset_categories'   => 'Assets',
        'procurement'        => 'Procurement',
        'book'               => 'Library',
        'book_issue'         => 'Library',
        'exam_category'      => 'Examinations',
        'offline_exam'       => 'Examinations',
        'online_exams'       => 'Online Exams',
        'gradebook'          => 'Results',
        'marks'              => 'Results',
        'grade_list'         => 'Results',
        'daily_attendance'   => 'Attendance',
        'attendance'         => 'Attendance',
        'class'              => 'Classes',
        'department'         => 'Departments',
        'expenses'           => 'Finance',
        'reports'            => 'Reports',
        'audit_log'          => 'Audit Logs',
        'settings'           => 'Settings',
        'live_classes'       => 'Live Classes',
        'assignments'        => 'Assignments',
        'leave'              => 'Leave Management',
        'promotion'          => 'Students',
        'routine'            => 'Routine',
        'syllabus'           => 'Syllabus',
        'academic_calendar'  => 'Academic Calendar',
        'schools'            => 'Schools',
        'package'            => 'Packages',
        'addon'              => 'Addons',
    ];

    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        if (AuditLog::$loggedThisRequest) {
            return;
        }

        if (!auth()->check() || !$request->isMethod('GET') || $request->ajax() || $request->wantsJson()) {
            return;
        }

        if ($response instanceof Response && $response->getStatusCode() >= 400) {
            return;
        }

        $routeName = optional($request->route())->getName();

        if (!$routeName || $this->isNoise($routeName)) {
            return;
        }

        $isExport = $this->looksLikeExport($routeName);

        AuditLog::record($isExport ? 'EXPORT' : 'VIEW', $this->moduleFromRoute($routeName), $this->describe($routeName, $isExport), [
            'event_type' => $isExport ? 'EXPORT' : 'ACCESS',
        ]);
    }

    private function isNoise(string $routeName): bool
    {
        foreach (self::ROUTE_NAME_NOISE as $needle) {
            if (str_starts_with($routeName, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeExport(string $routeName): bool
    {
        $lower = strtolower($routeName);

        foreach (self::EXPORT_HINTS as $hint) {
            if (str_contains($lower, $hint)) {
                return true;
            }
        }

        return false;
    }

    private function moduleFromRoute(string $routeName): string
    {
        $segments = explode('.', $routeName);
        // Drop the leading area prefix (admin/teacher/superadmin/...) when
        // there's a more specific segment to key off.
        $key = $segments[1] ?? $segments[0];

        return self::MODULE_LABELS[$key] ?? ucwords(str_replace(['_', '-'], ' ', $key));
    }

    private function describe(string $routeName, bool $isExport): string
    {
        $module = $this->moduleFromRoute($routeName);

        return $isExport
            ? "Exported/printed from {$module} ({$routeName})"
            : "Accessed {$module} ({$routeName})";
    }
}
