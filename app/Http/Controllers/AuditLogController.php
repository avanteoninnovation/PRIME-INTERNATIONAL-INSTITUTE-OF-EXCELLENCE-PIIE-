<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // Audit logs are security-sensitive: only Super Admin (school-wide
        // view across tenants) and School Admin (own tenant only) may view
        // them, regardless of what a customized menu_permission JSON blob
        // might otherwise allow for other staff roles under the broader
        // 'admin' middleware.
        $this->middleware(function ($request, $next) {
            $roleId = (int) (Auth::user()->role_id ?? 0);

            if (!in_array($roleId, [1, 2], true)) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $user       = Auth::user();
        $isSuper    = $user->role_id == 1;
        $search     = $request->search ?? '';
        $module     = $request->module ?? '';
        $action     = $request->action ?? '';
        $roleId     = $request->role_id ?? '';
        $ipAddress  = $request->ip_address ?? '';
        $deviceType = $request->device_type ?? '';
        $userId     = $request->user_id ?? '';
        $schoolId   = $isSuper ? ($request->school_id ?? '') : $user->school_id;
        $dateFrom   = $request->date_from ?? '';
        $dateTo     = $request->date_to ?? '';

        $base = fn() => AuditLog::when(!$isSuper, fn($q) => $q->where('school_id', $user->school_id))
            ->when($isSuper && $schoolId !== '', fn($q) => $q->where('school_id', $schoolId));

        $logs = $base()
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('description', 'like', "%$search%")
                  ->orWhere('user_name', 'like', "%$search%")
                  ->orWhere('action', 'like', "%$search%")
                  ->orWhere('url', 'like', "%$search%");
            }))
            ->when($module, fn($q) => $q->where('module', $module))
            ->when($action, fn($q) => $q->where('action', $action))
            ->when($roleId !== '', fn($q) => $q->where('role_id', $roleId))
            ->when($ipAddress, fn($q) => $q->where('ip_address', 'like', "%$ipAddress%"))
            ->when($deviceType, fn($q) => $q->where('device_type', $deviceType))
            ->when($userId !== '', fn($q) => $q->where('user_id', $userId))
            ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->with(['user', 'school'])
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $modules = $base()->distinct()->pluck('module')->filter()->sort()->values();
        $actions = $base()->distinct()->pluck('action')->filter()->sort()->values();
        $deviceTypes = $base()->distinct()->pluck('device_type')->filter()->sort()->values();
        $schools = $isSuper ? School::orderBy('title')->get(['id', 'title']) : collect();

        return view('admin.audit_log.index', compact(
            'logs', 'search', 'module', 'action', 'roleId', 'ipAddress', 'deviceType',
            'userId', 'schoolId', 'dateFrom', 'dateTo', 'modules', 'actions', 'deviceTypes',
            'schools', 'isSuper'
        ));
    }

    public function show(Request $request, $id)
    {
        $user    = Auth::user();
        $isSuper = $user->role_id == 1;

        $log = AuditLog::with(['user', 'school'])->findOrFail($id);

        // Cross-tenant IDOR guard: a School Admin must never be able to
        // view another school's audit entry by guessing/incrementing the id.
        if (!$isSuper && $log->school_id != $user->school_id) {
            abort(403);
        }

        return view('admin.audit_log.detail', compact('log'));
    }
}
