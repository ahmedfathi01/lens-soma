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
        Schema::table('bookings', function (Blueprint $table) {
            $table->json('payment_details')->nullable()->after('payment_transaction_id')
                ->comment('تفاصيل الدفع المستلمة من بوابة الدفع بما في ذلك المبلغ المدفوع مقدما وجدول الدفعات المتبقية');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('payment_details');
        });
    }
};
