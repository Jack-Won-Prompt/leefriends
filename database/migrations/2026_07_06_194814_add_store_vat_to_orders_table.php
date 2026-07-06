<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 매장 청구 부가세(가산분: 과세·별도 품목 10%). 합계 = store_amount + store_vat + shipping_fee
            $table->integer('store_vat')->default(0)->after('store_amount');
        });

        // 기존 발주 백필
        Order::with('items.supplyProduct')->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                $order->updateQuietly(['store_vat' => Order::addedVatFor($order->items)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('store_vat');
        });
    }
};
