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
        Schema::table('exam_enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_enrollments', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('expiry_date');
            }
            if (! Schema::hasColumn('exam_enrollments', 'amount_paid')) {
                $table->decimal('amount_paid', 10, 2)->default(0)->after('payment_status');
            }
            if (! Schema::hasColumn('exam_enrollments', 'access_granted')) {
                $table->boolean('access_granted')->default(true)->after('amount_paid');
            }
            if (! Schema::hasColumn('exam_enrollments', 'results_locked')) {
                $table->boolean('results_locked')->default(false)->after('access_granted');
            }
            if (! Schema::hasColumn('exam_enrollments', 'offline_marks')) {
                $table->decimal('offline_marks', 8, 2)->nullable()->after('results_locked');
            }
            if (! Schema::hasColumn('exam_enrollments', 'offline_remarks')) {
                $table->string('offline_remarks')->nullable()->after('offline_marks');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_enrollments', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'amount_paid', 'access_granted', 'results_locked', 'offline_marks', 'offline_remarks']);
        });
    }
};
