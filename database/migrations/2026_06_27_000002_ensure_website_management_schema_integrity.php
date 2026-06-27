<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureWebsiteManagementSchemaIntegrity extends Migration
{
    public function up()
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
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
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
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
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
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
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
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('website_pages')) {
            Schema::table('website_pages', function (Blueprint $table) {
                if (!Schema::hasColumn('website_pages', 'page_key')) {
                    $table->string('page_key')->nullable()->after('id');
                }
                if (!Schema::hasColumn('website_pages', 'title')) {
                    $table->string('title')->nullable()->after('page_key');
                }
                if (!Schema::hasColumn('website_pages', 'slug')) {
                    $table->string('slug')->nullable()->after('title');
                }
                if (!Schema::hasColumn('website_pages', 'status')) {
                    $table->tinyInteger('status')->default(1)->after('slug');
                }
                if (!Schema::hasColumn('website_pages', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('status');
                }
                if (!Schema::hasColumn('website_pages', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('sort_order');
                }
                if (!Schema::hasColumn('website_pages', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                }
            });
        }

        if (Schema::hasTable('website_sections')) {
            Schema::table('website_sections', function (Blueprint $table) {
                if (!Schema::hasColumn('website_sections', 'page_key')) {
                    $table->string('page_key')->nullable()->after('id');
                }
                if (!Schema::hasColumn('website_sections', 'section_key')) {
                    $table->string('section_key')->nullable()->after('page_key');
                }
                if (!Schema::hasColumn('website_sections', 'title')) {
                    $table->string('title')->nullable()->after('section_key');
                }
                if (!Schema::hasColumn('website_sections', 'subtitle')) {
                    $table->string('subtitle')->nullable()->after('title');
                }
                if (!Schema::hasColumn('website_sections', 'content')) {
                    $table->longText('content')->nullable()->after('subtitle');
                }
                if (!Schema::hasColumn('website_sections', 'extra_json')) {
                    $table->longText('extra_json')->nullable()->after('content');
                }
                if (!Schema::hasColumn('website_sections', 'image')) {
                    $table->string('image')->nullable()->after('extra_json');
                }
                if (!Schema::hasColumn('website_sections', 'status')) {
                    $table->tinyInteger('status')->default(1)->after('image');
                }
                if (!Schema::hasColumn('website_sections', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('status');
                }
                if (!Schema::hasColumn('website_sections', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('sort_order');
                }
                if (!Schema::hasColumn('website_sections', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                }
            });
        }

        if (Schema::hasTable('website_items')) {
            Schema::table('website_items', function (Blueprint $table) {
                if (!Schema::hasColumn('website_items', 'section_key')) {
                    $table->string('section_key')->nullable()->after('id');
                }
                if (!Schema::hasColumn('website_items', 'item_type')) {
                    $table->string('item_type')->default('general')->after('section_key');
                }
                if (!Schema::hasColumn('website_items', 'title')) {
                    $table->string('title')->nullable()->after('item_type');
                }
                if (!Schema::hasColumn('website_items', 'subtitle')) {
                    $table->string('subtitle')->nullable()->after('title');
                }
                if (!Schema::hasColumn('website_items', 'description')) {
                    $table->text('description')->nullable()->after('subtitle');
                }
                if (!Schema::hasColumn('website_items', 'content')) {
                    $table->longText('content')->nullable()->after('description');
                }
                if (!Schema::hasColumn('website_items', 'image')) {
                    $table->string('image')->nullable()->after('content');
                }
                if (!Schema::hasColumn('website_items', 'link')) {
                    $table->string('link')->nullable()->after('image');
                }
                if (!Schema::hasColumn('website_items', 'button_text')) {
                    $table->string('button_text')->nullable()->after('link');
                }
                if (!Schema::hasColumn('website_items', 'status')) {
                    $table->tinyInteger('status')->default(1)->after('button_text');
                }
                if (!Schema::hasColumn('website_items', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('status');
                }
                if (!Schema::hasColumn('website_items', 'meta_json')) {
                    $table->longText('meta_json')->nullable()->after('sort_order');
                }
                if (!Schema::hasColumn('website_items', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('meta_json');
                }
                if (!Schema::hasColumn('website_items', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                }
            });
        }

        if (Schema::hasTable('website_settings')) {
            Schema::table('website_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('website_settings', 'key')) {
                    $table->string('key')->nullable()->after('id');
                }
                if (!Schema::hasColumn('website_settings', 'value')) {
                    $table->longText('value')->nullable()->after('key');
                }
                if (!Schema::hasColumn('website_settings', 'is_json')) {
                    $table->tinyInteger('is_json')->default(0)->after('value');
                }
                if (!Schema::hasColumn('website_settings', 'status')) {
                    $table->tinyInteger('status')->default(1)->after('is_json');
                }
                if (!Schema::hasColumn('website_settings', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('status');
                }
                if (!Schema::hasColumn('website_settings', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                }
            });
        }

        if (Schema::hasTable('website_seo_settings')) {
            Schema::table('website_seo_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('website_seo_settings', 'page_key')) {
                    $table->string('page_key')->nullable()->after('id');
                }
                if (!Schema::hasColumn('website_seo_settings', 'meta_title')) {
                    $table->string('meta_title')->nullable()->after('page_key');
                }
                if (!Schema::hasColumn('website_seo_settings', 'meta_description')) {
                    $table->text('meta_description')->nullable()->after('meta_title');
                }
                if (!Schema::hasColumn('website_seo_settings', 'meta_keywords')) {
                    $table->text('meta_keywords')->nullable()->after('meta_description');
                }
                if (!Schema::hasColumn('website_seo_settings', 'canonical_url')) {
                    $table->string('canonical_url')->nullable()->after('meta_keywords');
                }
                if (!Schema::hasColumn('website_seo_settings', 'status')) {
                    $table->tinyInteger('status')->default(1)->after('canonical_url');
                }
                if (!Schema::hasColumn('website_seo_settings', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('status');
                }
                if (!Schema::hasColumn('website_seo_settings', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                }
            });
        }
    }

    public function down()
    {
        // Intentionally left non-destructive for backward safety.
    }
}
