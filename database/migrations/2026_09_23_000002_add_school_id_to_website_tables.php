<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Security Phase 2H: website CMS content is school-owned.
 *
 * Every CMS table gains school_id — including sections, items and SEO rows,
 * because they link to pages/sections only through shared text keys
 * (page_key / section_key), which cannot carry ownership once two schools
 * both have a "home" page or a "hero" section. The platform-wide unique keys
 * become per-school so each school can have its own home page, settings, etc.
 *
 * Existing rows are the institution's public site, which PIIE already
 * attributes to one school through global_settings.primary_school_id (see
 * App\Support\PublicTenantResolver). They are backfilled to that school only
 * when it is explicitly configured and exists; otherwise they stay NULL
 * rather than being guessed.
 *
 * Follows the existing convention: unsignedBigInteger + index, no foreign
 * key constraint (so no cascade).
 */
return new class extends Migration
{
    private const TABLES = ['website_pages', 'website_sections', 'website_items', 'website_settings', 'website_seo_settings'];

    /** table => [old single-column unique index, its column] */
    private const UNIQUES = [
        'website_pages' => ['website_pages_page_key_unique', 'page_key'],
        'website_settings' => ['website_settings_key_unique', 'key'],
        'website_seo_settings' => ['website_seo_settings_page_key_unique', 'page_key'],
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'school_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedBigInteger('school_id')->nullable()->after('id')->index();
                });
            }
        }

        foreach (self::UNIQUES as $table => [$index, $column]) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table, $index, $column) {
                if ($this->indexExists($table, $index)) {
                    $t->dropUnique($index);
                }
                if (!$this->indexExists($table, "{$table}_school_id_{$column}_unique")) {
                    $t->unique(['school_id', $column]);
                }
            });
        }

        $primary = Schema::hasTable('global_settings')
            ? DB::table('global_settings')->where('key', 'primary_school_id')->value('value')
            : null;

        if (!empty($primary) && DB::table('schools')->where('id', (int) $primary)->exists()) {
            foreach (self::TABLES as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->whereNull('school_id')->update(['school_id' => (int) $primary]);
                }
            }
        }
    }

    public function down(): void
    {
        // Restoring the platform-wide unique keys fails (rather than silently deleting
        // content) if two schools already use the same page_key / setting key.
        foreach (self::UNIQUES as $table => [$index, $column]) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'school_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table, $index, $column) {
                if ($this->indexExists($table, "{$table}_school_id_{$column}_unique")) {
                    $t->dropUnique(['school_id', $column]);
                }
                if (!$this->indexExists($table, $index)) {
                    $t->unique($column, $index);
                }
            });
        }

        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'school_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['school_id']);
            });
            if (DB::getDriverName() === 'sqlite') {
                // Laravel 9 needs doctrine/dbal to drop columns on SQLite; SQLite >= 3.35 can do it natively.
                DB::statement("ALTER TABLE {$table} DROP COLUMN school_id");
            } else {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('school_id');
                });
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))->contains('name', $index);
        }

        return !empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]));
    }
};
