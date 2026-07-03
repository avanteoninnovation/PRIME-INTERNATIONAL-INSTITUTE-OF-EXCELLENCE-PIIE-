<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $table = 'audit_logs';
    protected $fillable = [
        'school_id', 'user_id', 'user_name', 'action',
        'module', 'description', 'ip_address', 'user_agent'
    ];

    protected $casts = ['created_at' => 'datetime'];

    /**
     * Write an audit entry.
     */
    public static function record(string $action, string $module, string $description): void
    {
        $user = auth()->user();
        static::create([
            'school_id'   => $user?->school_id,
            'user_id'     => $user?->id ?? 0,
            'user_name'   => $user?->name,
            'action'      => $action,
            'module'      => $module,
            'description' => $description,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'created_at'  => now(),
        ]);
    }
}
