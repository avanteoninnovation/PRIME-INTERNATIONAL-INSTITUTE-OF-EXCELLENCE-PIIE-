<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RBAC Phase 3A — per-school staff permission grants.
 *
 * The permission catalogue itself lives in config/permissions.php (code, not
 * data). These tables only record WHO was granted WHAT, in WHICH school:
 *
 *   user_permissions      direct grants to one staff user
 *   staff_roles           per-school custom roles / permission bundles
 *                         (e.g. "Examinations Officer")
 *   staff_role_permissions the permissions in each bundle
 *   user_staff_roles      bundles assigned to users
 *
 * Base roles (users.role_id), the roles table, global_settings.role_perm_*
 * and users.menu_permission are untouched: those stay the backward-compatible
 * layer. Nothing is backfilled — no existing user needs a grant to keep what
 * they can do today. Follows the existing convention: unsignedBigInteger +
 * index, no foreign-key constraints (so no cascades). Creation-only, so the
 * rollback just drops these four new tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_permissions')) {
            Schema::create('user_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('permission', 100);
                $table->unsignedBigInteger('granted_by')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'permission']);
            });
        }

        if (!Schema::hasTable('staff_roles')) {
            Schema::create('staff_roles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->string('name', 100);
                $table->string('description', 255)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['school_id', 'name']);
            });
        }

        if (!Schema::hasTable('staff_role_permissions')) {
            Schema::create('staff_role_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('staff_role_id')->index();
                $table->string('permission', 100);
                $table->timestamps();
                $table->unique(['staff_role_id', 'permission']);
            });
        }

        if (!Schema::hasTable('user_staff_roles')) {
            Schema::create('user_staff_roles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('staff_role_id')->index();
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'staff_role_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_staff_roles');
        Schema::dropIfExists('staff_role_permissions');
        Schema::dropIfExists('staff_roles');
        Schema::dropIfExists('user_permissions');
    }
};
