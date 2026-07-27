<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'account_status')) {
                $table->string('account_status', 20)->default('active')->after('status');
            }
            if (!Schema::hasColumn('users', 'documents')) {
                $table->text('documents')->nullable()->after('account_status');
            }
            if (!Schema::hasColumn('users', 'student_info')) {
                $table->text('student_info')->nullable()->after('documents');
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'exam_category_id')) {
                $table->unsignedBigInteger('exam_category_id')->nullable()->after('exam_type');
            }
            if (!Schema::hasColumn('exams', 'room_number')) {
                $table->string('room_number')->nullable()->after('exam_category_id');
            }
        });

        Schema::table('student_fee_managers', function (Blueprint $table) {
            if (!Schema::hasColumn('student_fee_managers', 'amount')) {
                $table->decimal('amount', 10, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('student_fee_managers', 'discounted_price')) {
                $table->decimal('discounted_price', 10, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('student_fee_managers', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('student_id');
            }
        });

        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'studentLimit')) {
                $table->string('studentLimit')->nullable()->after('days');
            }
            if (!Schema::hasColumn('packages', 'features')) {
                $table->text('features')->nullable()->after('studentLimit');
            }
        });

        Schema::table('addons', function (Blueprint $table) {
            if (!Schema::hasColumn('addons', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('unique_identifier');
            }
        });

        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                if (!Schema::hasColumn('grades', 'total_marks')) {
                    $table->integer('total_marks')->nullable();
                }
            });
        }

        if (Schema::hasTable('payment_methods')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                if (!Schema::hasColumn('payment_methods', 'currency')) {
                    $table->string('currency', 10)->nullable();
                }
                if (!Schema::hasColumn('payment_methods', 'currency_position')) {
                    $table->string('currency_position', 10)->nullable();
                }
            });
        }
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['account_status', 'documents', 'student_info'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            foreach (['exam_category_id', 'room_number'] as $col) {
                if (Schema::hasColumn('exams', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('student_fee_managers', function (Blueprint $table) {
            foreach (['amount', 'discounted_price', 'parent_id'] as $col) {
                if (Schema::hasColumn('student_fee_managers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('packages', function (Blueprint $table) {
            foreach (['studentLimit', 'features'] as $col) {
                if (Schema::hasColumn('packages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('addons', function (Blueprint $table) {
            if (Schema::hasColumn('addons', 'parent_id')) {
                $table->dropColumn('parent_id');
            }
        });

        if (Schema::hasTable('grades') && Schema::hasColumn('grades', 'total_marks')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->dropColumn('total_marks');
            });
        }

        if (Schema::hasTable('payment_methods')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                foreach (['currency', 'currency_position'] as $col) {
                    if (Schema::hasColumn('payment_methods', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
