<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_classes', function (Blueprint $table): void {
            $table->unsignedBigInteger('course_offering_id')->nullable()->after('subject_id');
            $table->index(['school_id', 'course_offering_id'], 'live_classes_school_offering_idx');
            $table->foreign(['school_id', 'course_offering_id'], 'live_classes_offering_tenant_fk')
                ->references(['school_id', 'id'])
                ->on('course_offerings')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('live_classes', function (Blueprint $table): void {
            $table->dropForeign('live_classes_offering_tenant_fk');
            $table->dropIndex('live_classes_school_offering_idx');
            $table->dropColumn('course_offering_id');
        });
    }
};
