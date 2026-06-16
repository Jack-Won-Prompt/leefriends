<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::orderBy('region')->orderBy('name')->paginate(20);

        return view('admin.stores.index', compact('stores'));
    }

    public function store(Request $request)
    {
        Store::create($this->validateData($request));

        return redirect()->route('admin.stores.index')->with('success', '매장이 등록되었습니다.');
    }

    public function update(Request $request, Store $store)
    {
        $store->update($this->validateData($request));

        return redirect()->route('admin.stores.index')->with('success', '매장이 수정되었습니다.');
    }

    public function destroy(Store $store)
    {
        $store->delete();

        return back()->with('success', '매장이 삭제되었습니다.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'region' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'address_detail' => ['nullable', 'string', 'max:255'],
            'corp_postcode' => ['nullable', 'string', 'max:20'],
            'corp_address' => ['nullable', 'string', 'max:255'],
            'corp_address_detail' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'hours' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['image'] = 'images/store/default.svg';

        return $data;
    }
}
