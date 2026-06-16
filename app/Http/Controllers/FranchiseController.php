<?php

namespace App\Http\Controllers;

use App\Models\FranchiseInquiry;
use App\Models\Store;
use Illuminate\Http\Request;

class FranchiseController extends Controller
{
    public function index()
    {
        $storeCount = Store::active()->count();

        return view('franchise', compact('storeCount'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'region' => ['nullable', 'string', 'max:50'],
            'budget' => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:2000'],
            'agree_privacy' => ['accepted'],
        ], [
            'name.required' => '성함을 입력해 주세요.',
            'phone.required' => '연락처를 입력해 주세요.',
            'agree_privacy.accepted' => '개인정보 수집·이용에 동의해 주세요.',
        ]);

        $validated['agree_privacy'] = true;
        $validated['status'] = 'new';

        FranchiseInquiry::create($validated);

        return redirect()->route('franchise.thanks');
    }

    public function thanks()
    {
        return view('franchise-thanks');
    }
}
