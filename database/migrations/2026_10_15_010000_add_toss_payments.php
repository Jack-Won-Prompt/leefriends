<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 토스페이먼츠 매장 발주 결제.
 * - stores.toss_enabled: 매장별 토스결제 사용 여부 (우선 테스트 매장만 ON)
 * - orders.payment_status: 결제 상태 (null=비대상, awaiting/paid/waiting_deposit/failed/canceled)
 * - order_payments: 결제 시도·승인 내역 (감사/환불용)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stores', 'toss_enabled')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->boolean('toss_enabled')->default(false)->after('order_blocked');
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status', 20)->nullable()->after('status');
            }
            if (! Schema::hasColumn('orders', 'paid_amount')) {
                $table->unsignedBigInteger('paid_amount')->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('paid_amount');
            }
        });

        if (Schema::hasTable('order_payments')) {
            return;
        }
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('toss_order_id')->unique();   // 토스로 보낸 orderId (발주번호-난수)
            $table->string('payment_key')->nullable();   // 토스 paymentKey
            $table->string('method')->nullable();        // 카드/가상계좌/간편결제 등
            $table->unsignedBigInteger('amount');
            $table->string('status', 30)->default('ready'); // ready/done/waiting_deposit/canceled/failed
            $table->string('va_bank')->nullable();
            $table->string('va_account')->nullable();
            $table->timestamp('va_due_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('raw')->nullable();             // 토스 응답 원본
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'paid_amount', 'paid_at']);
        });
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('toss_enabled');
        });
    }
};
