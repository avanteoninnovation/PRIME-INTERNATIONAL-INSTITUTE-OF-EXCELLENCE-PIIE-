<?php

namespace App\Http\Controllers;

use Database\Seeders\WebsiteContentSeeder;
use App\Models\WebsiteItem;
use App\Models\WebsitePage;
use App\Models\WebsiteSection;
use App\Models\WebsiteSeoSetting;
use App\Models\WebsiteSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class WebsiteManagementController extends Controller
{
    private function ensureWebsiteTablesAndSeed()
    {
        if (!Schema::hasTable('website_pages')) {
            Schema::create('website_pages', function (Blueprint $table) {
                $table->id();
                $table->string('page_key')->unique();
                $table->string('title');
                $table->string('slug')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->integer('sort_order')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('website_sections')) {
            Schema::create('website_sections', function (Blueprint $table) {
                $table->id();
                $table->string('page_key')->index();
                $table->string('section_key')->index();
                $table->string('title')->nullable();
                $table->string('subtitle')->nullable();
                $table->longText('content')->nullable();
                $table->longText('extra_json')->nullable();
                $table->string('image')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('website_items')) {
            Schema::create('website_items', function (Blueprint $table) {
                $table->id();
                $table->string('section_key')->index();
                $table->string('item_type')->default('general');
                $table->string('title')->nullable();
                $table->string('subtitle')->nullable();
                $table->text('description')->nullable();
                $table->longText('content')->nullable();
                $table->string('image')->nullable();
                $table->string('link')->nullable();
                $table->string('button_text')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->integer('sort_order')->default(0);
                $table->longText('meta_json')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('website_settings')) {
            Schema::create('website_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->tinyInteger('is_json')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('website_seo_settings')) {
            Schema::create('website_seo_settings', function (Blueprint $table) {
                $table->id();
                $table->string('page_key')->unique();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->text('meta_keywords')->nullable();
                $table->string('canonical_url')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('website_pages') && WebsitePage::count() === 0) {
            (new WebsiteContentSeeder())->run();
        }
    }

    private function viewData()
    {
        $this->ensureWebsiteTablesAndSeed();

        return [
            'modules' => $this->moduleMap(),
            'pages' => WebsitePage::orderBy('sort_order')->orderBy('id')->get(),
            'sections' => WebsiteSection::orderBy('sort_order')->orderBy('id')->get(),
            'items' => WebsiteItem::orderBy('sort_order')->orderBy('id')->get(),
            'seo' => WebsiteSeoSetting::orderBy('page_key')->get()->keyBy('page_key'),
            'settings' => WebsiteSetting::orderBy('key')->get()->keyBy('key'),
        ];
    }

    private function moduleMap()
    {
        return [
            'home_page' => 'Home Page',
            'hero_slider' => 'Hero Slider/Banner',
            'about_institution' => 'About Institution',
            'vision_mission_motto' => 'Vision, Mission, Motto',
            'core_values' => 'Core Values',
            'why_choose_us' => 'Why Choose Us',
            'academic_programmes' => 'Academic Programmes',
            'programme_categories' => 'Programme Categories',
            'fees_structure' => 'Fees Structure',
            'admissions' => 'Admissions',
            'online_learning_odel' => 'Online Learning / ODeL',
            'governance_structure' => 'Governance Structure',
            'leadership_team' => 'Leadership / Team',
            'director_message' => 'Director Message',
            'principal_message' => 'Principal Message',
            'academic_registrar_message' => 'Academic Registrar Message',
            'institute_secretary_message' => 'Institute Secretary Message',
            'strategic_plan' => 'Strategic Plan',
            'research_innovation' => 'Research & Innovation',
            'partnerships_affiliations' => 'Partnerships / Affiliations',
            'student_support_services' => 'Student Support Services',
            'international_students' => 'International Students',
            'news_events' => 'News & Events',
            'faqs' => 'FAQs',
            'contact_page' => 'Contact Page',
            'footer_settings' => 'Footer Settings',
            'quick_links' => 'Quick Links',
            'portals_links' => 'Portals Links',
            'social_media_links' => 'Social Media Links',
            'seo_settings' => 'SEO Settings for each page',
        ];
    }

    private function saveImage(Request $request, $field, $old = null)
    {
        if (!$request->hasFile($field)) {
            return $old;
        }

        $dir = public_path('assets/uploads/website/');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        if (!empty($old) && File::exists($dir . $old)) {
            File::delete($dir . $old);
        }

        $file = $request->file($field);
        $name = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $name);

        return $name;
    }

    private function backToPanel(Request $request, $message)
    {
        if ($request->is('admin/*')) {
            return redirect()->route('admin.website.index')->with('message', $message);
        }

        return redirect()->route('superadmin.website.index')->with('message', $message);
    }

    public function superadminIndex()
    {
        return view('superadmin.website_management.index', $this->viewData());
    }

    public function adminIndex()
    {
        return view('admin.website_management.index', $this->viewData());
    }

    public function storePage(Request $request)
    {
        $this->ensureWebsiteTablesAndSeed();

        $data = $request->validate([
            'page_key' => 'required|string|max:191|unique:website_pages,page_key',
            'title' => 'required|string|max:191',
            'slug' => 'nullable|string|max:191',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|integer',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['status'] = $data['status'] ?? 1;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        WebsitePage::create($data);

        return $this->backToPanel($request, 'Website page created successfully.');
    }

    public function updatePage(Request $request, $id)
    {
        $this->ensureWebsiteTablesAndSeed();

        $page = WebsitePage::findOrFail($id);

        $data = $request->validate([
            'page_key' => 'required|string|max:191|unique:website_pages,page_key,' . $page->id,
            'title' => 'required|string|max:191',
            'slug' => 'nullable|string|max:191',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|integer',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['status'] = $data['status'] ?? 1;
        $data['updated_by'] = auth()->id();

        $page->update($data);

        return $this->backToPanel($request, 'Website page updated successfully.');
    }

    public function deletePage(Request $request, $id)
    {
        $this->ensureWebsiteTablesAndSeed();

        $page = WebsitePage::findOrFail($id);
        $page->delete();

        return $this->backToPanel($request, 'Website page deleted successfully.');
    }

    public function storeSection(Request $request)
    {
        $this->ensureWebsiteTablesAndSeed();

        $data = $request->validate([
            'page_key' => 'required|string|max:191',
            'section_key' => 'required|string|max:191',
            'title' => 'nullable|string|max:191',
            'subtitle' => 'nullable|string|max:191',
            'content' => 'nullable|string',
            'extra_json' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['status'] = $data['status'] ?? 1;
        $data['image'] = $this->saveImage($request, 'image');

        WebsiteSection::create($data);

        return $this->backToPanel($request, 'Section created successfully.');
    }

    public function updateSection(Request $request, $id)
    {
        $this->ensureWebsiteTablesAndSeed();

        $section = WebsiteSection::findOrFail($id);

        $data = $request->validate([
            'page_key' => 'required|string|max:191',
            'section_key' => 'required|string|max:191',
            'title' => 'nullable|string|max:191',
            'subtitle' => 'nullable|string|max:191',
            'content' => 'nullable|string',
            'extra_json' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['status'] = $data['status'] ?? 1;
        $data['image'] = $this->saveImage($request, 'image', $section->image);

        $section->update($data);

        return $this->backToPanel($request, 'Section updated successfully.');
    }

    public function deleteSection(Request $request, $id)
    {
        $this->ensureWebsiteTablesAndSeed();

        $section = WebsiteSection::findOrFail($id);

        if (!empty($section->image)) {
            $file = public_path('assets/uploads/website/' . $section->image);
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        $section->delete();

        return $this->backToPanel($request, 'Section deleted successfully.');
    }

    public function storeItem(Request $request)
    {
        $this->ensureWebsiteTablesAndSeed();

        $data = $request->validate([
            'section_key' => 'required|string|max:191',
            'item_type' => 'nullable|string|max:191',
            'title' => 'nullable|string|max:191',
            'subtitle' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'link' => 'nullable|string|max:191',
            'button_text' => 'nullable|string|max:191',
            'meta_json' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $data['item_type'] = $data['item_type'] ?? 'general';
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['status'] = $data['status'] ?? 1;
        $data['image'] = $this->saveImage($request, 'image');

        WebsiteItem::create($data);

        return $this->backToPanel($request, 'Item created successfully.');
    }

    public function updateItem(Request $request, $id)
    {
        $this->ensureWebsiteTablesAndSeed();

        $item = WebsiteItem::findOrFail($id);

        $data = $request->validate([
            'section_key' => 'required|string|max:191',
            'item_type' => 'nullable|string|max:191',
            'title' => 'nullable|string|max:191',
            'subtitle' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'link' => 'nullable|string|max:191',
            'button_text' => 'nullable|string|max:191',
            'meta_json' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $data['item_type'] = $data['item_type'] ?? 'general';
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['status'] = $data['status'] ?? 1;
        $data['image'] = $this->saveImage($request, 'image', $item->image);

        $item->update($data);

        return $this->backToPanel($request, 'Item updated successfully.');
    }

    public function deleteItem(Request $request, $id)
    {
        $this->ensureWebsiteTablesAndSeed();

        $item = WebsiteItem::findOrFail($id);

        if (!empty($item->image)) {
            $file = public_path('assets/uploads/website/' . $item->image);
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        $item->delete();

        return $this->backToPanel($request, 'Item deleted successfully.');
    }

    public function upsertSettings(Request $request)
    {
        $this->ensureWebsiteTablesAndSeed();

        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|max:191',
            'settings.*.value' => 'nullable|string',
            'settings.*.is_json' => 'nullable|integer',
            'settings.*.status' => 'nullable|integer',
        ]);

        foreach ($validated['settings'] as $setting) {
            WebsiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'] ?? null,
                    'is_json' => $setting['is_json'] ?? 0,
                    'status' => $setting['status'] ?? 1,
                ]
            );
        }

        return $this->backToPanel($request, 'Website settings updated successfully.');
    }

    public function upsertSeo(Request $request)
    {
        $this->ensureWebsiteTablesAndSeed();

        $validated = $request->validate([
            'seo' => 'required|array',
            'seo.*.page_key' => 'required|string|max:191',
            'seo.*.meta_title' => 'nullable|string|max:191',
            'seo.*.meta_description' => 'nullable|string',
            'seo.*.meta_keywords' => 'nullable|string',
            'seo.*.canonical_url' => 'nullable|string|max:191',
            'seo.*.status' => 'nullable|integer',
        ]);

        foreach ($validated['seo'] as $row) {
            WebsiteSeoSetting::updateOrCreate(
                ['page_key' => $row['page_key']],
                [
                    'meta_title' => $row['meta_title'] ?? null,
                    'meta_description' => $row['meta_description'] ?? null,
                    'meta_keywords' => $row['meta_keywords'] ?? null,
                    'canonical_url' => $row['canonical_url'] ?? null,
                    'status' => $row['status'] ?? 1,
                ]
            );
        }

        return $this->backToPanel($request, 'SEO settings updated successfully.');
    }
}
