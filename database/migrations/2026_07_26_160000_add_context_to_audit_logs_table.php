<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'role_id')) {
                $table->unsignedTinyInteger('role_id')->nullable()->after('user_name');
            }
            if (!Schema::hasColumn('audit_logs', 'role_name')) {
                $table->string('role_name', 60)->nullable()->after('role_id');
            }
            if (!Schema::hasColumn('audit_logs', 'event_type')) {
                $table->string('event_type', 20)->default('ACTION')->after('action');
            }
            if (!Schema::hasColumn('audit_logs', 'route_name')) {
                $table->string('route_name', 150)->nullable()->after('module');
            }
            if (!Schema::hasColumn('audit_logs', 'url')) {
                $table->string('url', 500)->nullable()->after('route_name');
            }
            if (!Schema::hasColumn('audit_logs', 'method')) {
                $table->string('method', 10)->nullable()->after('url');
            }
            if (!Schema::hasColumn('audit_logs', 'record_type')) {
                $table->string('record_type', 100)->nullable()->after('description');
            }
            if (!Schema::hasColumn('audit_logs', 'record_id')) {
                $table->unsignedBigInteger('record_id')->nullable()->after('record_type');
            }
            if (!Schema::hasColumn('audit_logs', 'old_values')) {
                $table->json('old_values')->nullable()->after('record_id');
            }
            if (!Schema::hasColumn('audit_logs', 'new_values')) {
                $table->json('new_values')->nullable()->after('old_values');
            }
            if (!Schema::hasColumn('audit_logs', 'device_type')) {
                $table->string('device_type', 20)->nullable()->after('user_agent');
            }
            if (!Schema::hasColumn('audit_logs', 'browser')) {
                $table->string('browser', 60)->nullable()->after('device_type');
            }
            if (!Schema::hasColumn('audit_logs', 'platform')) {
                $table->string('platform', 60)->nullable()->after('browser');
            }
            if (!Schema::hasColumn('audit_logs', 'status')) {
                $table->string('status', 20)->nullable()->after('platform');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'audit_logs', ['user_id'], 'audit_logs_user_id_index');
            $this->addIndexIfMissing($table, 'audit_logs', ['school_id'], 'audit_logs_school_id_index');
            $this->addIndexIfMissing($table, 'audit_logs', ['module'], 'audit_logs_module_index');
            $this->addIndexIfMissing($table, 'audit_logs', ['action'], 'audit_logs_action_index');
            $this->addIndexIfMissing($table, 'audit_logs', ['event_type'], 'audit_logs_event_type_index');
            $this->addIndexIfMissing($table, 'audit_logs', ['created_at'], 'audit_logs_created_at_index');
            $this->addIndexIfMissing($table, 'audit_logs', ['record_type', 'record_id'], 'audit_logs_record_type_record_id_index');
            $this->addIndexIfMissing($table, 'audit_logs', ['ip_address'], 'audit_logs_ip_address_index');
        });
    }

    private function addIndexIfMissing(Blueprint $table, string $tableName, array $columns, string $indexName)
    {
        $exists = collect(Schema::getConnection()->select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?", [$indexName]))->isNotEmpty();

        if (!$exists) {
            $table->index($columns, $indexName);
        }
    }

    public function down()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $columns = [
                'role_id', 'role_name', 'event_type', 'route_name', 'url', 'method',
                'record_type', 'record_id', 'old_values', 'new_values',
                'device_type', 'browser', 'platform', 'status',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('audit_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
