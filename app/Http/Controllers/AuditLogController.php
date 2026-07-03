<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user      = Auth::user();
        $school_id = $user->school_id;
        $search    = $request->search ?? '';
        $module    = $request->module ?? '';

        $logs = AuditLog::when($user->role_id != 1, fn($q) => $q->where('school_id', $school_id))
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('description', 'like', "%$search%")
                  ->orWhere('user_name', 'like', "%$search%")
                  ->orWhere('action', 'like', "%$search%");
            }))
            ->when($module, fn($q) => $q->where('module', $module))
            ->orderByDesc('created_at')
            ->paginate(30);

        $modules = AuditLog::when($user->role_id != 1, fn($q) => $q->where('school_id', $school_id))
            ->distinct()
            ->pluck('module')
            ->filter()
            ->sort()
            ->values();

        return view('admin.audit_log.index', compact('logs', 'search', 'module', 'modules'));
    }
}
