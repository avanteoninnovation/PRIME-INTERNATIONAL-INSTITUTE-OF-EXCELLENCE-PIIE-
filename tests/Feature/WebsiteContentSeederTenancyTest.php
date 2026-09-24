<?php

namespace Tests\Feature;

use Database\Seeders\WebsiteContentSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * Pre-RBAC cleanup H — WebsiteContentSeeder (also run by DatabaseSeeder and
 * by the CMS on an empty install) used to delete EVERY school's pages,
 * sections, items and SEO rows and then upsert settings by key across all
 * schools. It now resets and seeds only the public-site school
 * (App\Support\PublicTenantResolver / primary_school_id), matching rows
 * within that school, so other schools' content survives.
 */
class WebsiteContentSeederTenancyTest extends TestCase
{
    use StaffModuleTestHelper;

    private int $schoolA;
    private int $schoolB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        foreach ([
            'database/migrations/2026_06_24_000001_create_website_management_tables.php',
            'database/migrations/2026_06_27_000002_ensure_website_management_schema_integrity.php',
            'database/migrations/2026_06_27_000003_add_page_header_and_navigation_fields_to_website_pages.php',
            'database/migrations/2026_09_23_000002_add_school_id_to_website_tables.php',
        ] as $path) {
            (require base_path($path))->up();
        }

        $this->schoolA = $this->makeSchool(['title' => 'School A', 'status' => 1]);
        $this->schoolB = $this->makeSchool(['title' => 'School B', 'status' => 1]);
        DB::table('global_settings')->updateOrInsert(['key' => 'primary_school_id'], ['value' => (string) $this->schoolA]);

        // School B already has its own content under the same keys the seeder uses.
        DB::table('website_pages')->insert(['school_id' => $this->schoolB, 'page_key' => 'home', 'slug' => 'home', 'title' => 'B Home Zq', 'status' => 1]);
        DB::table('website_sections')->insert(['school_id' => $this->schoolB, 'page_key' => 'home', 'section_key' => 'hero', 'title' => 'B Hero Zq', 'status' => 1]);
        DB::table('website_items')->insert(['school_id' => $this->schoolB, 'section_key' => 'hero', 'item_type' => 'general', 'title' => 'B Item Zq', 'status' => 1]);
        DB::table('website_settings')->insert(['school_id' => $this->schoolB, 'key' => 'motto', 'value' => 'B Motto Zq', 'is_json' => 0, 'status' => 1]);
        DB::table('website_seo_settings')->insert(['school_id' => $this->schoolB, 'page_key' => 'home', 'meta_title' => 'B Seo Zq', 'status' => 1]);
    }

    private function counts(int $school): array
    {
        return collect(['website_pages', 'website_sections', 'website_items', 'website_settings', 'website_seo_settings'])
            ->mapWithKeys(fn ($t) => [$t => DB::table($t)->where('school_id', $school)->count()])->all();
    }

    public function test_seeding_the_public_school_leaves_another_schools_content_untouched(): void
    {
        $before = [];
        foreach (['website_pages', 'website_sections', 'website_items', 'website_settings', 'website_seo_settings'] as $t) {
            $before[$t] = DB::table($t)->where('school_id', $this->schoolB)->get()->map(fn ($r) => (array) $r)->all();
        }

        (new WebsiteContentSeeder())->run();

        foreach ($before as $t => $rows) {
            $this->assertEquals($rows, DB::table($t)->where('school_id', $this->schoolB)->get()->map(fn ($r) => (array) $r)->all(), "{$t}: School B content changed");
        }
        $this->assertSame('B Motto Zq', DB::table('website_settings')->where('school_id', $this->schoolB)->where('key', 'motto')->value('value'));
    }

    public function test_the_public_school_receives_the_default_content_with_per_school_keys(): void
    {
        (new WebsiteContentSeeder())->run();

        $content = require database_path('seeders/piie_website_content_data.php');
        $a = $this->counts($this->schoolA);
        $this->assertSame(count($content['pages']), $a['website_pages']);
        $this->assertSame(count($content['settings']), $a['website_settings']);
        $this->assertSame(count($content['pages']), $a['website_seo_settings']);
        $this->assertGreaterThan(0, $a['website_sections']);
        $this->assertGreaterThan(0, $a['website_items']);

        // Both schools now own a "home" page and a "motto" setting — per-school uniqueness, not platform-wide.
        $this->assertSame(2, DB::table('website_pages')->where('page_key', 'home')->count());
        $this->assertSame(2, DB::table('website_settings')->where('key', 'motto')->count());
        $this->assertSame(0, DB::table('website_pages')->whereNull('school_id')->count(), 'unowned rows created');
    }

    public function test_reseeding_resets_the_public_school_without_duplicating_rows(): void
    {
        (new WebsiteContentSeeder())->run();
        $first = $this->counts($this->schoolA);
        DB::table('website_settings')->where('school_id', $this->schoolA)->where('key', 'motto')->update(['value' => 'Edited']);

        (new WebsiteContentSeeder())->run();

        $this->assertSame($first, $this->counts($this->schoolA));
        $this->assertSame('B Home Zq', DB::table('website_pages')->where('school_id', $this->schoolB)->value('title'));
    }
}
