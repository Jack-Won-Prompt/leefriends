<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\SupplyProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 상품(물품) 관리.
 *  - 본사: 전체 CRUD + 공급처 등록 물품 승인/반려
 *  - 공급처: 자사 물품 등록/수정/삭제(승인 전), 등록 시 승인대기
 * (이미지 업로드는 웹 포털에서 처리)
 */
class ProductController extends Controller
{
    use ResolvesSeller;

    public function index(Request $request): JsonResponse
    {
        [$type, $sid] = $this->seller($request);
        $q = trim((string) $request->query('q', ''));
        $approval = $request->query('approval', 'all');

        $query = SupplyProduct::with(['units', 'supplier'])->catalogOrder();
        if ($type === 'supplier') {
            $query->where('supplier_id', $sid);
        }
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"));
        }
        if (in_array($approval, ['approved', 'pending', 'rejected'], true)) {
            $query->where('approval_status', $approval);
        }
        $products = $query->paginate(50);

        return response()->json([
            'data' => $products->getCollection()->map(fn (SupplyProduct $p) => $this->transform($p))->values(),
            'meta' => [
                'role' => $type,
                'categories' => ProductCategory::ordered()->pluck('name')->values(),
                'suppliers' => $type === 'hq'
                    ? Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name'])
                    : [],
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        if ($type === 'supplier') {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:100'],
                'category' => ['required', 'string', 'max:50'],
                'spec' => ['nullable', 'string', 'max:50'],
                'unit' => ['required', 'string', 'max:30'],
                'supply_price' => ['required', 'integer', 'min:0'],
                'sort_order' => ['nullable', 'integer'],
            ]);
            $product = DB::transaction(function () use ($data, $sid) {
                $p = SupplyProduct::create([
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'category_code' => SupplyProduct::CATEGORY_CODES[$data['category']] ?? null,
                    'spec' => $data['spec'] ?? null,
                    'unit' => $data['unit'],
                    'supply_type' => 'supplier',
                    'supplier_id' => $sid,
                    'supply_price' => $data['supply_price'],
                    'store_price' => 0,
                    'registered_by' => 'supplier',
                    'approval_status' => 'pending',
                    'is_active' => true,
                    'sort_order' => (int) ($data['sort_order'] ?? 0),
                ]);
                $p->units()->create(['name' => $p->unit, 'supply_price' => $p->supply_price,
                    'store_price' => 0, 'is_default' => true, 'sort_order' => 0]);

                return $p;
            });

            return response()->json([
                'message' => '물품을 등록했습니다. 본사 승인 후 매장에서 발주할 수 있습니다.',
                'data' => $this->transform($product->fresh(['units', 'supplier'])),
            ], 201);
        }

        // hq
        $data = $this->validateHq($request);
        $product = DB::transaction(function () use ($data) {
            $p = SupplyProduct::create($data);
            $p->units()->create(['name' => $p->unit ?: '개', 'store_price' => $p->store_price,
                'supply_price' => $p->supply_price, 'is_default' => true, 'sort_order' => 0]);

            return $p;
        });

        return response()->json([
            'message' => '품목이 등록되었습니다.',
            'data' => $this->transform($product->fresh(['units', 'supplier'])),
        ], 201);
    }

    public function update(Request $request, SupplyProduct $product): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        if ($type === 'supplier') {
            abort_unless($product->supplier_id == $sid, 403);
            if ($product->approval_status === 'approved') {
                return response()->json(['message' => '승인된 물품은 수정할 수 없습니다. 본사에 문의해 주세요.'], 400);
            }
            $data = $request->validate([
                'name' => ['required', 'string', 'max:100'],
                'category' => ['required', 'string', 'max:50'],
                'spec' => ['nullable', 'string', 'max:50'],
                'unit' => ['required', 'string', 'max:30'],
                'supply_price' => ['required', 'integer', 'min:0'],
                'sort_order' => ['nullable', 'integer'],
            ]);
            DB::transaction(function () use ($product, $data) {
                $product->update([
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'category_code' => SupplyProduct::CATEGORY_CODES[$data['category']] ?? $product->category_code,
                    'spec' => $data['spec'] ?? null,
                    'unit' => $data['unit'],
                    'supply_price' => $data['supply_price'],
                    'sort_order' => (int) ($data['sort_order'] ?? 0),
                    'approval_status' => 'pending', // 수정 시 재승인 대기
                ]);
                $def = $product->units()->where('is_default', true)->first() ?? $product->units()->first();
                $def?->update(['name' => $product->unit, 'supply_price' => $product->supply_price]);
            });

            return response()->json(['message' => '물품을 수정했습니다.', 'data' => $this->transform($product->fresh(['units', 'supplier']))]);
        }

        // hq
        $data = $this->validateHq($request, $product);
        DB::transaction(function () use ($product, $data) {
            $product->update($data);
            $def = $product->units()->where('is_default', true)->first() ?? $product->units()->first();
            if ($def) {
                $def->update(['name' => $product->unit, 'store_price' => $product->store_price, 'supply_price' => $product->supply_price]);
            } else {
                $product->units()->create(['name' => $product->unit, 'is_default' => true, 'sort_order' => 0,
                    'store_price' => $product->store_price, 'supply_price' => $product->supply_price]);
            }
        });

        return response()->json(['message' => '품목이 수정되었습니다.', 'data' => $this->transform($product->fresh(['units', 'supplier']))]);
    }

    public function destroy(Request $request, SupplyProduct $product): JsonResponse
    {
        [$type, $sid] = $this->seller($request);
        if ($type === 'supplier') {
            abort_unless($product->supplier_id == $sid, 403);
        }
        DB::transaction(function () use ($product) {
            $product->units()->delete();
            $product->supplierPrices()->delete();
            $product->delete();
        });

        return response()->json(['message' => '물품이 삭제되었습니다.']);
    }

    /** PATCH approve (hq) */
    public function approve(Request $request, SupplyProduct $product): JsonResponse
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403);
        $data = $request->validate(['store_price' => ['required', 'integer', 'min:0']]);
        DB::transaction(function () use ($product, $data) {
            $product->update([
                'store_price' => $data['store_price'],
                'approval_status' => 'approved',
                'approval_note' => null,
                'is_active' => true,
            ]);
            $def = $product->units()->where('is_default', true)->first() ?? $product->units()->first();
            $def?->update(['store_price' => $data['store_price']]);
        });

        return response()->json(['message' => "{$product->name} 물품을 승인했습니다."]);
    }

    /** PATCH reject (hq) */
    public function reject(Request $request, SupplyProduct $product): JsonResponse
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403);
        $data = $request->validate(['approval_note' => ['nullable', 'string', 'max:200']]);
        $product->update(['approval_status' => 'rejected', 'approval_note' => $data['approval_note'] ?? null, 'is_active' => false]);

        return response()->json(['message' => "{$product->name} 물품을 반려했습니다."]);
    }

    private function validateHq(Request $request, ?SupplyProduct $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:50'],
            'spec' => ['nullable', 'string', 'max:50'],
            'unit' => ['required', 'string', 'max:30'],
            'store_price' => ['required', 'integer', 'min:0'],
            'supply_type' => ['required', 'in:hq,supplier'],
            'supplier_id' => ['nullable', 'required_if:supply_type,supplier', 'exists:suppliers,id'],
            'supply_price' => ['nullable', 'integer', 'min:0'],
            'is_market_price' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['category_code'] = SupplyProduct::CATEGORY_CODES[$data['category']]
            ?? ($product->category_code ?? null);
        if ($data['supply_type'] === 'supplier') {
            $data['supply_price'] = (int) ($data['supply_price'] ?? 0);
        } else {
            $data['supplier_id'] = null;
            $data['supply_price'] = 0;
        }
        $data['is_market_price'] = (bool) ($data['is_market_price'] ?? false);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        return $data;
    }

    private function transform(SupplyProduct $p): array
    {
        return [
            'id' => $p->id,
            'code' => $p->code,
            'name' => $p->name,
            'category' => $p->category,
            'category_code' => $p->category_code,
            'spec' => $p->spec,
            'unit' => $p->unit,
            'supply_type' => $p->supply_type,
            'supplier_id' => $p->supplier_id,
            'supplier_name' => $p->supply_type === 'supplier' ? $p->supplier?->name : '본사',
            'supply_price' => (int) $p->supply_price,
            'store_price' => (int) $p->store_price,
            'is_market_price' => (bool) $p->is_market_price,
            'is_active' => (bool) $p->is_active,
            'approval_status' => $p->approval_status,
            'approval_note' => $p->approval_note,
            'image' => $p->image ? asset($p->image) : null,
        ];
    }
}
