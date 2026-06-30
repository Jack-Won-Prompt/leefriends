<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * 일정 관리 (캘린더) — 본사/매장/공급처 각자 소속 일정.
 * 역할 + 소속(store_id/supplier_id) 범위로만 조회·관리.
 */
class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $schedules = Schedule::forUser(Auth::user())->orderBy('schedule_date')->orderBy('id')->get();

        $byDate = $schedules->groupBy(fn ($s) => $s->schedule_date->format('Y-m-d'))
            ->map(fn ($group) => $group->map(fn ($s) => [
                'id' => $s->id,
                'date' => $s->schedule_date->format('Y-m-d'),
                'title' => $s->title,
                'content' => $s->content,
                'color' => $s->color,
            ])->values());

        return view('portal.schedules.index', [
            'byDate' => $byDate,
            'ym' => $request->query('ym'),
        ]);
    }

    public function store(Request $request)
    {
        $me = Auth::user();
        $data = $this->validateData($request);
        $data['role'] = $me->role;
        $data['store_id'] = $me->store_id;
        $data['supplier_id'] = $me->supplier_id;
        $data['created_by'] = $me->id;
        Schedule::create($data);

        return $this->redirectBack($request, '일정을 등록했습니다.');
    }

    public function update(Request $request, Schedule $schedule)
    {
        $this->authorizeOwn($schedule);
        $schedule->update($this->validateData($request));

        return $this->redirectBack($request, '일정을 수정했습니다.');
    }

    public function destroy(Request $request, Schedule $schedule)
    {
        $this->authorizeOwn($schedule);
        $schedule->delete();

        return $this->redirectBack($request, '일정을 삭제했습니다.');
    }

    private function authorizeOwn(Schedule $schedule): void
    {
        $me = Auth::user();
        $ok = $schedule->role === $me->role
            && (int) $schedule->store_id === (int) $me->store_id
            && (int) $schedule->supplier_id === (int) $me->supplier_id;
        abort_unless($ok, 403);
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

    private function redirectBack(Request $request, string $msg)
    {
        $ym = $request->input('ym');

        return redirect()->route('portal.schedules.index', $ym ? ['ym' => $ym] : [])->with('success', $msg);
    }
}
