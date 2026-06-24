<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 매장 사업자 정보 (본사→매장 세금계산서 공급받는자)
        Schema::table('stores', function (Blueprint $table) {
            $table->string('biz_no')->nullable()->after('email');     // 사업자등록번호
            $table->string('ceo')->nullable()->after('biz_no');       // 대표자
            $table->string('biz_type')->nullable()->after('ceo');     // 업태
            $table->string('biz_class')->nullable()->after('biz_type'); // 종목
        });

        // 제품 부가세 구분: inc(과세·부가세포함) / exc(과세·부가세별도) / exempt(면세)
        Schema::table('supply_products', function (Blueprint $table) {
            $table->string('tax_type', 10)->default('inc')->after('store_price');
        });

        // 세금계산서 일반화 (본사→매장 / 공급처→본사)
        Schema::table('tax_invoices', function (Blueprint $table) {
            $table->string('direction', 20)->default('supplier_to_hq')->after('id'); // supplier_to_hq | hq_to_store
            $table->foreignId('store_id')->nullable()->after('supplier_id')->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->after('store_id')->constrained()->nullOnDelete();
            $table->string('invoicer_corp_num')->nullable()->after('order_id');   // 발행자(공급자) 사업자번호
            $table->string('invoicer_corp_name')->nullable()->after('invoicer_corp_num');
            $table->string('invoicee_corp_num')->nullable()->after('invoicer_corp_name'); // 공급받는자 사업자번호
            $table->string('invoicee_corp_name')->nullable()->after('invoicee_corp_num');
            $table->string('invoicee_email')->nullable()->after('invoicee_corp_name');
            $table->json('items')->nullable()->after('invoicee_email');           // 품목 스냅샷
            $table->foreignId('issued_by')->nullable()->after('items')->constrained('users')->nullOnDelete();
            // supplier_id 는 기존 컬럼(공급처→본사). hq_to_store 에서는 null 허용 필요
            $table->unsignedBigInteger('supplier_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['biz_no', 'ceo', 'biz_type', 'biz_class']);
        });
        Schema::table('supply_products', function (Blueprint $table) {
            $table->dropColumn('tax_type');
        });
        Schema::table('tax_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
            $table->dropConstrainedForeignId('order_id');
            $table->dropConstrainedForeignId('issued_by');
            $table->dropColumn(['direction', 'invoicer_corp_num', 'invoicer_corp_name', 'invoicee_corp_num', 'invoicee_corp_name', 'invoicee_email', 'items']);
        });
    }
};
