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
        Schema::table('exam_enrollments', function (Blueprint $table): void {
            $table->boolean('access_granted')->default(true)->after('expiry_date');
            $table->string('payment_status')->default('paid')->after('access_granted');
            $table->decimal('amount_paid', 12, 2)->default(0)->after('payment_status');
            $table->boolean('results_locked')->default(false)->after('amount_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_enrollments', function (Blueprint $table): void {
            $table->dropColumn(['access_granted', 'payment_status', 'amount_paid', 'results_locked']);
        });
    }
};
