<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('exams') && ! Schema::hasColumn('exams', 'exam_mode')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->string('exam_mode')->default('online')->after('status'); // online, physical, hybrid
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('exams') && Schema::hasColumn('exams', 'exam_mode')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropColumn('exam_mode');
            });
        }
    }
};
