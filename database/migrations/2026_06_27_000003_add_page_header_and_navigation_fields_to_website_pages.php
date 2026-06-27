<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPageHeaderAndNavigationFieldsToWebsitePages extends Migration
{
    public function up()
    {
        if (Schema::hasTable('website_pages')) {
            Schema::table('website_pages', function (Blueprint $table) {
                // Page header fields
                if (!Schema::hasColumn('website_pages', 'subtitle')) {
                    $table->string('subtitle')->nullable()->after('title');
                }
                if (!Schema::hasColumn('website_pages', 'page_image')) {
                    $table->string('page_image')->nullable()->after('subtitle');
                }
                if (!Schema::hasColumn('website_pages', 'overlay_opacity')) {
                    $table->integer('overlay_opacity')->default(40)->after('page_image');
                }
                if (!Schema::hasColumn('website_pages', 'cta_button_text')) {
                    $table->string('cta_button_text')->nullable()->after('overlay_opacity');
                }
                if (!Schema::hasColumn('website_pages', 'cta_button_link')) {
                    $table->string('cta_button_link')->nullable()->after('cta_button_text');
                }
                
                // Navigation fields
                if (!Schema::hasColumn('website_pages', 'show_in_navigation')) {
                    $table->boolean('show_in_navigation')->default(true)->after('cta_button_link');
                }
                if (!Schema::hasColumn('website_pages', 'display_order')) {
                    $table->integer('display_order')->default(0)->after('show_in_navigation');
                }
                if (!Schema::hasColumn('website_pages', 'nav_title')) {
                    $table->string('nav_title')->nullable()->after('display_order')->comment('Custom title for navigation if different from page title');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('website_pages')) {
            Schema::table('website_pages', function (Blueprint $table) {
                $columns = ['subtitle', 'page_image', 'overlay_opacity', 'cta_button_text', 'cta_button_link', 'show_in_navigation', 'display_order', 'nav_title'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('website_pages', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
}
