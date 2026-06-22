<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplyProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * 품목 관리 — 매장 판매 완제품(기본정보 + 판매단가)을 관리한다.
 */
class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => $request->query('category', 'all'),
            'active' => $request->query('active', 'all'),
            'approval' => $request->query('approval', 'all'),
        ];

        $query = SupplyProduct::with('units')->catalogOrder();

        if (in_array($filters['approval'], ['approved', 'pending', 'rejected'], true)) {
            $query->where('approval_status', $filters['approval']);
        }

        if ($filters['q'] !== '') {
            $query->where(function ($w) use ($filters) {
                $w->where('name', 'like', "%{$filters['q']}%")->orWhere('code', 'like', "%{$filters['q']}%");
            });
        }
        if ($filters['category'] !== 'all') {
            $query->where('category', $filters['category']);
        }
        if ($filters['active'] === 'active') {
            $query->where('is_active', true);
        } elseif ($filters['active'] === 'hidden') {
            $query->where('is_active', false);
        }

        $products = $query->with('supplier')->paginate(30)->withQueryString();
        $categories = SupplyProduct::query()->select('category')->distinct()->orderBy('category')->pluck('category');
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('portal.hq.products.index', compact('products', 'categories', 'filters', 'suppliers'));
    }

    /** 발주 카탈로그 대분류 */
    public const CATEGORIES = ['마카롱', '쿠키', '재료'];

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        // 대분류코드 자동 설정 (코드 채번은 모델 creating 이벤트가 처리)
        $data['category_code'] = SupplyProduct::CATEGORY_CODES[$data['category']] ?? null;

        DB::transaction(function () use ($request, $data) {
            $product = SupplyProduct::create($data); // code 자동 채번
            // 기본 단위 1개 (판매단가/공급가 동기화)
            $product->units()->create([
                'name' => $product->unit ?: '개', 'store_price' => $product->store_price, 'supply_price' => $product->supply_price,
                'is_default' => true, 'sort_order' => 0,
            ]);
            // 품목 이미지 업로드
            if ($request->hasFile('image_file')) {
                $product->update(['image' => $this->storeImage($request, $product)]);
            }
        });

        return redirect()->route('portal.hq.products.index')->with('success', '품목이 등록되었습니다.');
    }

    public function update(Request $request, SupplyProduct $product)
    {
        $data = $this->validateData($request);
        $data['category_code'] = SupplyProduct::CATEGORY_CODES[$data['category']] ?? $product->category_code;

        DB::transaction(function () use ($request, $product, $data) {
            $product->update($data);

            // 기본 단위 명칭/판매단가/공급가 동기화 (없으면 생성)
            $def = $product->units()->where('is_default', true)->first() ?? $product->units()->first();
            if ($def) {
                $def->update(['name' => $product->unit, 'store_price' => $product->store_price, 'supply_price' => $product->supply_price]);
            } else {
                $product->units()->create(['name' => $product->unit, 'is_default' => true, 'sort_order' => 0, 'store_price' => $product->store_price, 'supply_price' => $product->supply_price]);
            }

            // 이미지: 새 파일 업로드(기존 교체) 또는 삭제 요청
            if ($request->hasFile('image_file')) {
                $this->deleteImage($product->image);
                $product->update(['image' => $this->storeImage($request, $product)]);
            } elseif ($request->boolean('remove_image')) {
                $this->deleteImage($product->image);
                $product->update(['image' => null]);
            }
        });

        return redirect()->route('portal.hq.products.index')->with('success', '완제품이 수정되었습니다.');
    }

    /** 공급처 등록 물품 승인 — 매장 판매가(출고가) 책정 + 노출 */
    public function approve(Request $request, SupplyProduct $product)
    {
        $data = $request->validate([
            'store_price' => ['required', 'integer', 'min:0'],
        ], ['store_price.required' => '매장 판매가를 입력해 주세요.']);

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

        return back()->with('success', "{$product->name} 물품을 승인했습니다. 매장 발주 화면에 노출됩니다.");
    }

    /** 공급처 등록 물품 반려 */
    public function reject(Request $request, SupplyProduct $product)
    {
        $data = $request->validate([
            'approval_note' => ['nullable', 'string', 'max:200'],
        ]);

        $product->update(['approval_status' => 'rejected', 'approval_note' => $data['approval_note'] ?? null, 'is_active' => false]);

        return back()->with('success', "{$product->name} 물품을 반려했습니다.");
    }

    public function destroy(SupplyProduct $product)
    {
        $this->deleteImage($product->image);
        $product->units()->delete();
        $product->supplierPrices()->delete();
        $product->delete();

        return back()->with('success', '완제품이 삭제되었습니다.');
    }

    /** 업로드 이미지를 storage/app/public/products 에 저장하고 상대경로(storage/...) 반환 */
    private function storeImage(Request $request, SupplyProduct $product): string
    {
        $file = $request->file('image_file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $name = $product->code . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;

        // public 디스크(storage/app/public)는 PHP가 항상 쓰기 가능 → /storage 심볼릭으로 서빙
        $file->storeAs('products', $name, 'public');

        return 'storage/products/' . $name;
    }

    /** 업로드된 이미지 파일 삭제 (외부/기본 이미지는 무시) */
    private function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }
        if (str_starts_with($path, 'storage/products/')) {
            Storage::disk('public')->delete(substr($path, strlen('storage/'))); // products/xxx
        } elseif (str_starts_with($path, 'uploads/products/')) { // 레거시(public/uploads) 호환
            $full = public_path($path);
            if (is_file($full)) {
                @unlink($full);
            }
        }
    }

    private function validateData(Request $request): array
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
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ], [
            'store_price.required' => '판매단가를 입력해 주세요.',
            'supplier_id.required_if' => '공급사 발송 품목은 공급처를 선택해 주세요.',
            'image_file.image' => '이미지 파일만 업로드할 수 있습니다.',
            'image_file.max' => '이미지는 최대 4MB까지 업로드할 수 있습니다.',
        ]);

        unset($data['image_file']); // 파일은 별도 처리

        // 공급 구분에 따른 정규화
        if ($data['supply_type'] === 'supplier') {
            $data['supply_price'] = (int) ($data['supply_price'] ?? 0);
        } else {
            $data['supplier_id'] = null;   // 본사 직공급은 공급처 없음
            $data['supply_price'] = 0;
        }

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
