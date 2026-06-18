<?php

use App\Models\ProductCategory;
use App\Models\SupplyProduct;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 1) 기존 supply_products 분류에서 카테고리 시드 (정의 순서 우선)
        $seed = ['마카롱' => 'MAC', '쿠키' => 'COO', '재료' => 'MAT'];
        $order = 0;
        foreach ($seed as $name => $code) {
            ProductCategory::firstOrCreate(['name' => $name], ['code' => $code, 'sort_order' => $order++]);
        }
        // 혹시 매핑에 없는 기존 분류도 등록
        foreach (DB::table('supply_products')->select('category', 'category_code')->distinct()->get() as $row) {
            if ($row->category && ! ProductCategory::where('name', $row->category)->exists()) {
                ProductCategory::create(['name' => $row->category, 'code' => $row->category_code ?: $this->genCode($row->category), 'sort_order' => $order++]);
            }
        }

        // 2) 재료(materials) → 품목(supply_products)으로 흡수
        if (Schema::hasTable('materials')) {
            foreach (DB::table('materials')->orderBy('id')->get() as $m) {
                $catName = $m->category ?: '재료';
                $cat = ProductCategory::firstOrCreate(
                    ['name' => $catName],
                    ['code' => $this->genCode($catName), 'sort_order' => $order++]
                );
                // 동일 명칭 품목이 이미 있으면 건너뜀
                if (SupplyProduct::where('name', $m->name)->exists()) {
                    continue;
                }
                SupplyProduct::create([
                    'name' => $m->name,
                    'category' => $catName,
                    'category_code' => $cat->code,
                    'unit' => $m->unit ?: '개',
                    'spec' => $m->spec,
                    'supply_type' => 'hq',          // 본사 직공급(재료)
                    'supply_price' => 0,
                    'store_price' => 0,
                    'sort_order' => $m->sort_order ?? 0,
                    'is_active' => (bool) $m->is_active,
                    'approval_status' => 'approved',
                ]);
            }
        }
    }

    /** 신규 카테고리 코드 자동 생성 (영문/숫자는 그대로, 한글 등은 CAT+id) */
    private function genCode(string $name): string
    {
        $ascii = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
        if (strlen($ascii) >= 2) {
            $code = substr($ascii, 0, 3);
        } else {
            $code = 'CAT'.(ProductCategory::max('id') + 1);
        }
        // 중복 방지
        $base = $code;
        $i = 1;
        while (ProductCategory::where('code', $code)->exists()) {
            $code = $base.$i++;
        }

        return $code;
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
