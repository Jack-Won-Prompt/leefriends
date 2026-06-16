<?php

namespace App\Http\Controllers\Portal\Supplier;

use App\Http\Controllers\Controller;
use App\Models\SupplyProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * 공급처 물품(재료) 등록/관리.
 * - 공급처는 공급가만 입력하며, 등록 시 «승인대기» 상태.
 * - 본사 승인(매장 판매가 책정) 후 매장 발주 화면에 노출.
 */
class ProductController extends Controller
{
    /** 발주 카탈로그 대분류 */
    public const CATEGORIES = ['마카롱', '쿠키', '재료'];

    public function index(Request $request)
    {
        $supplierId = $this->supplierId();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'approval' => $request->query('approval', 'all'),
        ];

        $query = SupplyProduct::with('units')->where('supplier_id', $supplierId)->catalogOrder();

        if ($filters['q'] !== '') {
            $query->where(function ($w) use ($filters) {
                $w->where('name', 'like', "%{$filters['q']}%")->orWhere('code', 'like', "%{$filters['q']}%");
            });
        }
        if (in_array($filters['approval'], ['approved', 'pending', 'rejected'], true)) {
            $query->where('approval_status', $filters['approval']);
        }

        $products = $query->paginate(30)->withQueryString();

        return view('portal.supplier.products.index', compact('products', 'filters'));
    }

    public function store(Request $request)
    {
        $supplierId = $this->supplierId();
        $data = $this->validateData($request);

        DB::transaction(function () use ($data, $supplierId) {
            $product = SupplyProduct::create([
                'name' => $data['name'],
                'category' => $data['category'],
                'category_code' => SupplyProduct::CATEGORY_CODES[$data['category']] ?? null,
                'spec' => $data['spec'] ?? null,
                'unit' => $data['unit'],
                'supply_type' => 'supplier',
                'supplier_id' => $supplierId,
                'supply_price' => $data['supply_price'],
                'store_price' => 0,             // 본사 승인 시 책정
                'registered_by' => 'supplier',
                'approval_status' => 'pending', // 승인대기
                'is_active' => true,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]);
            $product->units()->create([
                'name' => $product->unit, 'supply_price' => $product->supply_price, 'store_price' => 0,
                'is_default' => true, 'sort_order' => 0,
            ]);
        });

        return redirect()->route('portal.supplier.products.index')->with('success', '물품을 등록했습니다. 본사 승인 후 매장에서 발주할 수 있습니다.');
    }

    public function update(Request $request, SupplyProduct $product)
    {
        $this->authorizeOwn($product);
        abort_if($product->approval_status === 'approved', 400, '승인된 물품은 수정할 수 없습니다. 본사에 문의해 주세요.');

        $data = $this->validateData($request);

        DB::transaction(function () use ($product, $data) {
            $product->update([
                'name' => $data['name'],
                'category' => $data['category'],
                'category_code' => SupplyProduct::CATEGORY_CODES[$data['category']] ?? $product->category_code,
                'spec' => $data['spec'] ?? null,
                'unit' => $data['unit'],
                'supply_price' => $data['supply_price'],
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                // 반려 후 수정 시 다시 승인대기로
                'approval_status' => 'pending',
                'approval_note' => null,
            ]);
            $def = $product->units()->where('is_default', true)->first() ?? $product->units()->first();
            $def?->update(['name' => $product->unit, 'supply_price' => $product->supply_price]);
        });

        return redirect()->route('portal.supplier.products.index')->with('success', '물품을 수정했습니다. 본사 재승인 후 노출됩니다.');
    }

    public function destroy(SupplyProduct $product)
    {
        $this->authorizeOwn($product);
        abort_if($product->approval_status === 'approved', 400, '승인된 물품은 삭제할 수 없습니다. 본사에 문의해 주세요.');

        $product->units()->delete();
        $product->supplierPrices()->delete();
        $product->delete();

        return back()->with('success', '물품을 삭제했습니다.');
    }

    private function supplierId(): int
    {
        $id = Auth::user()->supplier_id;
        abort_unless($id, 403, '연결된 공급처가 없습니다.');

        return $id;
    }

    private function authorizeOwn(SupplyProduct $product): void
    {
        abort_unless($product->supplier_id === $this->supplierId(), 403);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'in:' . implode(',', self::CATEGORIES)],
            'spec' => ['nullable', 'string', 'max:50'],
            'unit' => ['required', 'string', 'max:30'],
            'supply_price' => ['required', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer'],
        ], [
            'supply_price.required' => '공급가를 입력해 주세요.',
        ]);
    }
}
