<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FranchiseInquiry;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');

        $query = FranchiseInquiry::latest();
        if (array_key_exists($status, FranchiseInquiry::STATUSES)) {
            $query->where('status', $status);
        }
        $inquiries = $query->paginate(15)->withQueryString();

        return view('admin.inquiries.index', [
            'inquiries' => $inquiries,
            'status' => $status,
            'statuses' => FranchiseInquiry::STATUSES,
        ]);
    }

    public function show(FranchiseInquiry $inquiry)
    {
        return view('admin.inquiries.show', [
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

        return back()->with('success', '상태가 변경되었습니다.');
    }

    public function destroy(FranchiseInquiry $inquiry)
    {
        $inquiry->delete();

        return redirect()->route('admin.inquiries.index')->with('success', '문의가 삭제되었습니다.');
    }
}
