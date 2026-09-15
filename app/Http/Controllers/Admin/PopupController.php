<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Popup;
use Illuminate\Http\Request;

/**
 * 메인 페이지 팝업 설정 — 프랜차이즈·매장·카페 과일 문의 등.
 */
class PopupController extends Controller
{
    public function edit()
    {
        return view('admin.popup.edit', ['popup' => Popup::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'title' => ['required', 'string', 'max:100'],
            'body' => ['nullable', 'string', 'max:500'],
            'contact' => ['nullable', 'string', 'max:100'],
            'link_url' => ['nullable', 'string', 'max:255'],
            'link_label' => ['nullable', 'string', 'max:30'],
            'image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ], [
            'title.required' => '팝업 제목을 입력해 주세요.',
            'image_file.mimes' => '이미지는 jpg, png, webp 형식만 업로드할 수 있습니다.',
            'image_file.max' => '이미지 용량은 5MB 이하만 가능합니다.',
        ]);

        $popup = Popup::current();

        // 이미지: 새 업로드 / 삭제 / 유지
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $filename = 'popup_'.time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('images/popup'), $filename);
            $data['image'] = 'images/popup/'.$filename;
        } elseif ($request->boolean('remove_image')) {
            $data['image'] = null;
        } else {
            $data['image'] = $popup->image;
        }

        unset($data['image_file'], $data['remove_image']);
        $data['is_active'] = $request->boolean('is_active');
        $data['link_url'] = $data['link_url'] ?: '/franchise#inquiry';
        $data['link_label'] = $data['link_label'] ?: '문의하기';

        $popup->fill($data)->save();

        return back()->with('success', '팝업 설정을 저장했습니다.');
    }
}
