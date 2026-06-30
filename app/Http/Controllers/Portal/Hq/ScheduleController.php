<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * 본사 일정 관리 (캘린더). 날짜별 일정/내용 등록·수정·삭제.
 */
class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $schedules = Schedule::orderBy('schedule_date')->orderBy('id')->get();

        // 캘린더용: 날짜(Y-m-d) → 일정 배열
        $byDate = $schedules->groupBy(fn ($s) => $s->schedule_date->format('Y-m-d'))
            ->map(fn ($group) => $group->map(fn ($s) => [
                'id' => $s->id,
                'date' => $s->schedule_date->format('Y-m-d'),
                'title' => $s->title,
                'content' => $s->content,
                'color' => $s->color,
            ])->values());

        return view('portal.hq.schedules.index', [
            'byDate' => $byDate,
            'ym' => $request->query('ym'), // 초기 표시 월 (YYYY-MM)
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['created_by'] = Auth::id();
        Schedule::create($data);

        return $this->redirectBack($request, '일정을 등록했습니다.');
    }

    public function update(Request $request, Schedule $schedule)
    {
        $schedule->update($this->validateData($request));

        return $this->redirectBack($request, '일정을 수정했습니다.');
    }

    public function destroy(Request $request, Schedule $schedule)
    {
        $schedule->delete();

        return $this->redirectBack($request, '일정을 삭제했습니다.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'schedule_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:100'],
            'content' => ['nullable', 'string', 'max:2000'],
            'color' => ['nullable', Rule::in(Schedule::COLORS)],
        ], [
            'title.required' => '일정 제목을 입력해 주세요.',
            'schedule_date.required' => '날짜를 선택해 주세요.',
        ]);
    }

    /** 보던 월(ym)을 유지하며 목록으로 복귀 */
    private function redirectBack(Request $request, string $msg)
    {
        $ym = $request->input('ym');

        return redirect()->route('portal.hq.schedules.index', $ym ? ['ym' => $ym] : [])->with('success', $msg);
    }
}
