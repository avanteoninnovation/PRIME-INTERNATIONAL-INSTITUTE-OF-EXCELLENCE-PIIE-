<?php

namespace Database\Seeders;

use App\Models\WebsiteItem;
use App\Models\WebsitePage;
use App\Models\WebsiteSection;
use App\Models\WebsiteSeoSetting;
use App\Models\WebsiteSetting;
use Illuminate\Database\Seeder;

class WebsiteContentSeeder extends Seeder
{
    public function run()
    {
        $content = require __DIR__ . '/piie_website_content_data.php';

        WebsiteItem::query()->delete();
        WebsiteSection::query()->delete();
        WebsitePage::query()->delete();
        WebsiteSeoSetting::query()->delete();

        foreach ($content['pages'] as $page) {
            WebsitePage::updateOrCreate(['page_key' => $page['page_key']], $page);
        }

        foreach ($content['sections'] as $section) {
            WebsiteSection::updateOrCreate(
                ['section_key' => $section['section_key']],
                $section
            );
        }

        foreach ($content['items'] as $item) {
            WebsiteItem::updateOrCreate(
                ['section_key' => $item['section_key'], 'item_type' => $item['item_type'], 'title' => $item['title']],
                $item
            );
        }

        foreach ($content['settings'] as $key => $value) {
            WebsiteSetting::updateOrCreate(['key' => $key], ['value' => $value, 'is_json' => 0, 'status' => 1]);
        }

        foreach ($content['pages'] as $page) {
            WebsiteSeoSetting::updateOrCreate(
                ['page_key' => $page['page_key']],
                [
                    'meta_title' => 'PIIE - ' . $page['title'],
                    'meta_description' => 'Prime International Institute of Excellence (PIIE) official website.',
                    'meta_keywords' => 'PIIE, Prime International Institute of Excellence, online higher education Uganda, ODeL Uganda',
                    'canonical_url' => null,
                    'status' => 1,
                ]
            );
        }
    }
}
