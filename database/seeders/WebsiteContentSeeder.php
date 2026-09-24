<?php

namespace Database\Seeders;

use App\Models\WebsiteItem;
use App\Models\WebsitePage;
use App\Models\WebsiteSection;
use App\Models\WebsiteSeoSetting;
use App\Models\WebsiteSetting;
use App\Support\PublicTenantResolver;
use Illuminate\Database\Seeder;

/**
 * Seeds the default PIIE website content for the public-site school only
 * (global_settings.primary_school_id via App\Support\PublicTenantResolver —
 * CMS content is school-owned since Security Phase 2H).
 *
 * For that school it keeps the original behaviour: pages, sections, items
 * and SEO are reset to the defaults and settings are updated in place. Every
 * lookup and delete is limited to that school, so other schools' website
 * content (which may use the same page/section/setting keys) is never
 * touched.
 */
class WebsiteContentSeeder extends Seeder
{
    public function run()
    {
        $schoolId = PublicTenantResolver::resolveSchoolId();

        if (empty($schoolId)) {
            // No school yet — nothing can own the content.
            return;
        }

        $content = require __DIR__ . '/piie_website_content_data.php';
        $owned = fn (string $model) => $model::where('school_id', $schoolId);

        $owned(WebsiteItem::class)->delete();
        $owned(WebsiteSection::class)->delete();
        $owned(WebsitePage::class)->delete();
        $owned(WebsiteSeoSetting::class)->delete();

        $upsert = function (string $model, array $match, array $values) use ($owned, $schoolId) {
            $row = $owned($model)->where($match)->first() ?? new $model($match);
            $row->fill($values);
            $row->school_id = $schoolId; // not mass-assignable
            $row->save();
        };

        foreach ($content['pages'] as $page) {
            $upsert(WebsitePage::class, ['page_key' => $page['page_key']], $page);
        }

        foreach ($content['sections'] as $section) {
            $upsert(WebsiteSection::class, ['section_key' => $section['section_key']], $section);
        }

        foreach ($content['items'] as $item) {
            $upsert(WebsiteItem::class, ['section_key' => $item['section_key'], 'item_type' => $item['item_type'], 'title' => $item['title']], $item);
        }

        foreach ($content['settings'] as $key => $value) {
            $upsert(WebsiteSetting::class, ['key' => $key], ['value' => $value, 'is_json' => 0, 'status' => 1]);
        }

        foreach ($content['pages'] as $page) {
            $upsert(WebsiteSeoSetting::class, ['page_key' => $page['page_key']], [
                'meta_title' => 'PIIE - ' . $page['title'],
                'meta_description' => 'Prime International Institute of Excellence (PIIE) official website.',
                'meta_keywords' => 'PIIE, Prime International Institute of Excellence, online higher education Uganda, ODeL Uganda',
                'canonical_url' => null,
                'status' => 1,
            ]);
        }
    }
}
