<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\FranchiseInquiry;
use Illuminate\Http\Request;

/**
 * 본사 포털 — 온라인 창업 문의 확인/관리.
 */
class InquiryController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');

        $query = FranchiseInquiry::latest();
        if (array_key_exists($status, FranchiseInquiry::STATUSES)) {
            $query->where('status', $status);
        }

        return view('portal.hq.inquiries.index', [
            'inquiries' => $query->paginate(15)->withQueryString(),
            'status' => $status,
            'statuses' => FranchiseInquiry::STATUSES,
            'newCount' => FranchiseInquiry::where('status', 'new')->count(),
        ]);
    }

    public function show(FranchiseInquiry $inquiry)
    {
        return view('portal.hq.inquiries.show', [
            'inquiry' => $inquiry,
            'statuses' => FranchiseInquiry::STATUSES,
        ]);
    }

    public function update(Request $request, FranchiseInquiry $inquiry)
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,contacted,done'],
        ]);
        $inquiry->update($data);

        return back()->with('success', '문의 상태가 변경되었습니다.');
    }

    public function destroy(FranchiseInquiry $inquiry)
    {
        $inquiry->delete();

        return redirect()->route('portal.hq.inquiries.index')->with('success', '문의가 삭제되었습니다.');
    }
}
