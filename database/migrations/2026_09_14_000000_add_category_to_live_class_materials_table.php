<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets one live_class_materials row be either a class resource (slides,
 * readings — the existing behaviour) or a recording (new), without a
 * parallel table: same upload/link/list/delete code path, just filtered by
 * `category`. See App\Models\LiveClassMaterial.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('live_class_materials', function (Blueprint $table) {
            $table->string('category', 20)->default('resource')->after('type');
        });
    }

    public function down()
    {
        Schema::table('live_class_materials', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
