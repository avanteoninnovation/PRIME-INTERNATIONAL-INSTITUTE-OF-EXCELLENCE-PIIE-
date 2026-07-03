<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\GlobalSettings;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnhancedSettingsController extends Controller
{
    private $school_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->school_id = Auth::user()->school_id;
            return $next($request);
        });
    }

    // ── Academic / Grading ────────────────────────────────────
    public function academic()
    {
        $grades = Grade::where('school_id', $this->school_id)->orderByDesc('mark_from')->get();
        return view('admin.settings.academic', compact('grades'));
    }

    public function saveAcademic(Request $request)
    {
        // Delete existing and re-insert grading scale from posted data
        if ($request->has('grade')) {
            Grade::where('school_id', $this->school_id)->delete();
            foreach ($request->grade as $g) {
                if (!empty($g['name'])) {
                    Grade::create([
                        'school_id'      => $this->school_id,
                        'name'           => $g['name'],
                        'grade_point'    => $g['grade_point'] ?? 0,
                        'gpa_points'     => $g['gpa_points'] ?? 0,
                        'classification' => $g['classification'] ?? '',
                        'mark_from'      => $g['mark_from'] ?? 0,
                        'mark_upto'      => $g['mark_upto'] ?? 100,
                    ]);
                }
            }
        }
        return redirect()->back()->with('success', get_phrase('Grading scale updated'));
    }

    // ── Notifications ─────────────────────────────────────────
    public function notifications()
    {
        $notif_settings = $this->getNotifSettings();
        return view('admin.settings.notifications', compact('notif_settings'));
    }

    public function saveNotifications(Request $request)
    {
        $keys = [
            'notif_new_student', 'notif_fee_paid', 'notif_exam_published',
            'notif_assignment', 'notif_leave_request', 'notif_notice',
            'sms_new_student', 'sms_fee_paid', 'sms_exam_result',
        ];
        foreach ($keys as $key) {
            $val = $request->has($key) ? '1' : '0';
            GlobalSettings::updateOrCreate(['key' => $key], ['value' => $val]);
        }
        return redirect()->back()->with('success', get_phrase('Notification settings saved'));
    }

    // ── Permissions ───────────────────────────────────────────
    public function permissions()
    {
        $roles = DB::table('roles')->where('school_id', $this->school_id)->orWhere('school_id', 0)->get();
        $all_perms = $this->getPermissionList();
        // Load saved permissions per role
        $role_perms = [];
        foreach ($roles as $role) {
            $val = GlobalSettings::where('key', "role_perm_{$role->id}")->value('value');
            $role_perms[$role->id] = $val ? json_decode($val, true) : [];
        }
        return view('admin.settings.permissions', compact('roles', 'all_perms', 'role_perms'));
    }

    public function savePermissions(Request $request)
    {
        $roles = DB::table('roles')->where('school_id', $this->school_id)->orWhere('school_id', 0)->get();
        foreach ($roles as $role) {
            $perms = $request->input("perms.{$role->id}", []);
            GlobalSettings::updateOrCreate(
                ['key' => "role_perm_{$role->id}"],
                ['value' => json_encode($perms)]
            );
        }
        return redirect()->back()->with('success', get_phrase('Permissions saved'));
    }

    // ── Backup ────────────────────────────────────────────────
    public function backup()
    {
        $backups = $this->listBackups();
        return view('admin.settings.backup', compact('backups'));
    }

    public function runBackup(Request $request)
    {
        $dir = storage_path('backups');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $db     = config('database.connections.mysql.database');
        $user   = config('database.connections.mysql.username');
        $pass   = config('database.connections.mysql.password');
        $host   = config('database.connections.mysql.host');
        $file   = $dir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';

        // Use mysqldump via exec
        $cmd = "mysqldump --user={$user} --password={$pass} --host={$host} {$db} > \"{$file}\" 2>&1";
        exec($cmd, $output, $rc);

        if ($rc === 0 && file_exists($file)) {
            return redirect()->back()->with('success', get_phrase('Backup created') . ': ' . basename($file));
        }

        // Fallback: export schema only via PHP
        $tables  = DB::select('SHOW TABLES');
        $sql     = "-- TDIIBT Backup " . date('Y-m-d H:i:s') . "\n-- Database: {$db}\n\n";
        foreach ($tables as $t) {
            $tname = array_values((array)$t)[0];
            $create = DB::select("SHOW CREATE TABLE `{$tname}`");
            $sql   .= array_values((array)$create[0])[1] . ";\n\n";
        }
        file_put_contents($file, $sql);

        return redirect()->back()->with('success', get_phrase('Schema backup created') . ': ' . basename($file));
    }

    // ── API ───────────────────────────────────────────────────
    public function apiSettings()
    {
        $api_key = GlobalSettings::where('key', "api_key_{$this->school_id}")->value('value');
        if (!$api_key) {
            $api_key = Str::random(40);
            GlobalSettings::create(['key' => "api_key_{$this->school_id}", 'value' => $api_key]);
        }
        $api_base = url('/api');
        return view('admin.settings.api', compact('api_key', 'api_base'));
    }

    public function regenerateKey()
    {
        $api_key = Str::random(40);
        GlobalSettings::updateOrCreate(
            ['key' => "api_key_{$this->school_id}"],
            ['value' => $api_key]
        );
        return redirect()->back()->with('success', get_phrase('API key regenerated'));
    }

    // ── Helpers ───────────────────────────────────────────────
    private function getNotifSettings(): array
    {
        $keys = [
            'notif_new_student', 'notif_fee_paid', 'notif_exam_published',
            'notif_assignment', 'notif_leave_request', 'notif_notice',
            'sms_new_student', 'sms_fee_paid', 'sms_exam_result',
        ];
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = GlobalSettings::where('key', $key)->value('value') === '1';
        }
        return $result;
    }

    private function getPermissionList(): array
    {
        return [
            'Academic'    => ['View Dashboard', 'View Students', 'Edit Students', 'Delete Students', 'View Staff', 'Edit Staff', 'Manage Courses', 'Enter Marks', 'Publish Results', 'Manage Attendance'],
            'Finance'     => ['View Finance', 'Record Payments', 'Manage Payroll', 'View Invoices'],
            'Admissions'  => ['View Admissions', 'Manage Admissions', 'Issue Offer Letters'],
            'Operations'  => ['View Library', 'Post Notices', 'Manage Events', 'Manage Leave'],
            'System'      => ['View Reports', 'View Audit Log', 'System Settings', 'Manage Users'],
        ];
    }

    private function listBackups(): array
    {
        $dir = storage_path('backups');
        if (!is_dir($dir)) return [];
        $files = glob($dir . '/backup_*.sql');
        if (!$files) return [];
        $result = [];
        foreach (array_reverse($files) as $f) {
            $result[] = [
                'name' => basename($f),
                'size' => round(filesize($f) / 1024, 1) . ' KB',
                'date' => date('Y-m-d H:i:s', filemtime($f)),
            ];
        }
        return array_slice($result, 0, 10);
    }
}
