<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 기준정보 — 택배사 관리 (직접 배송 포함).
 */
class CourierController extends Controller
{
    public function index()
    {
        return view('portal.hq.couriers.index', [
            'couriers' => Courier::ordered()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('couriers', 'name')],
            'is_direct' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ], ['name.unique' => '이미 등록된 택배사입니다.']);

        Courier::create([
            'name' => $data['name'],
            'is_direct' => $request->boolean('is_direct'),
            'is_active' => true,
            'sort_order' => $data['sort_order'] ?? ((int) Courier::max('sort_order') + 1),
        ]);

        return back()->with('success', '택배사를 등록했습니다.');
    }

    public function update(Request $request, Courier $courier)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('couriers', 'name')->ignore($courier->id)],
            'is_direct' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $courier->update([
            'name' => $data['name'],
            'is_direct' => $request->boolean('is_direct'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($data['sort_order'] ?? $courier->sort_order),
        ]);

        return back()->with('success', '택배사를 수정했습니다.');
    }

    public function destroy(Courier $courier)
    {
        $courier->delete();

        return back()->with('success', '택배사를 삭제했습니다.');
    }
}
