<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Audit\ClientInfo;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $table = 'audit_logs';
    protected $fillable = [
        'school_id', 'user_id', 'user_name', 'role_id', 'role_name',
        'action', 'event_type', 'module', 'route_name', 'url', 'method',
        'description', 'record_type', 'record_id', 'old_values', 'new_values',
        'ip_address', 'user_agent', 'device_type', 'browser', 'platform',
        'status', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Attribute keys that must never be persisted into old_values/new_values,
     * even if a caller accidentally passes a full model attribute array.
     */
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'remember_token',
        'token', 'api_token', 'api_key', 'secret', 'access_token',
        'refresh_token', 'card_number', 'cvv', 'cvc', 'pin',
    ];

    /**
     * Set true the moment any audit entry is written during the current
     * request. Lets the passive page-access middleware skip logging a
     * generic "VIEW" when a more specific entry (CREATE/UPDATE/DELETE/etc.)
     * was already recorded for the same request, avoiding duplicate rows.
     */
    public static bool $loggedThisRequest = false;

    /** Role labels for audit display only — not an authorization source. */
    private const ROLE_NAMES = [
        1 => 'Super Admin', 2 => 'Admin', 3 => 'Teacher', 4 => 'Accountant',
        5 => 'Librarian', 6 => 'Parent', 7 => 'Student', 8 => 'Driver',
        9 => 'Alumni', 10 => 'Warden', 11 => 'Registrar', 12 => 'HOD',
        13 => 'Director', 14 => 'HR Manager', 15 => 'Procurement Officer',
        16 => 'Store Keeper', 17 => 'Receptionist', 18 => 'Examinations Officer',
        19 => 'Admissions Staff',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public static function roleName(?int $roleId): ?string
    {
        return $roleId ? (self::ROLE_NAMES[$roleId] ?? "Role #{$roleId}") : null;
    }

    /**
     * Write an audit entry. Backward compatible with the original 3-arg
     * signature used throughout the codebase; pass $context for the richer
     * fields (record_type/record_id/old_values/new_values/event_type/status).
     */
    public static function record(string $action, string $module, string $description, array $context = []): void
    {
        $user    = auth()->user();
        $request = request();
        $client  = ClientInfo::fromRequest($request);

        static::$loggedThisRequest = true;

        // Audit logging is observability, not business logic: a failure
        // here (e.g. a pending migration not yet applied) must never break
        // the create/update/delete/login it was trying to record. Report
        // the error to the normal log channels and move on.
        try {
            static::create([
                'school_id'   => $context['school_id'] ?? $user?->school_id,
                'user_id'     => $user?->id ?? 0,
                'user_name'   => $user?->name,
                'role_id'     => $user?->role_id,
                'role_name'   => static::roleName($user?->role_id),
                'action'      => $action,
                'event_type'  => $context['event_type'] ?? 'ACTION',
                'module'      => $module,
                'route_name'  => \Illuminate\Support\Facades\Route::currentRouteName(),
                'url'         => $request->fullUrl(),
                'method'      => $request->method(),
                'description' => $description,
                'record_type' => $context['record_type'] ?? null,
                'record_id'   => $context['record_id'] ?? null,
                'old_values'  => static::redact($context['old_values'] ?? null),
                'new_values'  => static::redact($context['new_values'] ?? null),
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'device_type' => $client['device_type'],
                'browser'     => $client['browser'],
                'platform'    => $client['platform'],
                'status'      => $context['status'] ?? null,
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private static function redact(?array $values): ?array
    {
        if (!$values) {
            return $values;
        }

        foreach (self::SENSITIVE_KEYS as $key) {
            if (array_key_exists($key, $values)) {
                unset($values[$key]);
            }
        }

        return $values;
    }
}
