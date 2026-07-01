<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 모바일 앱 — 일정(캘린더). 로그인 사용자의 소속(역할+조직) 일정만 조회·관리.
 * 웹 Portal\ScheduleController 와 동일한 forUser 범위 규칙.
 */
class ScheduleController extends Controller
{
    /** GET /api/v1/schedules  — 전체 일정(날짜 오름차순, 플랫 리스트). */
    public function index(Request $request): JsonResponse
    {
        $schedules = Schedule::forUser($request->user())
            ->orderBy('schedule_date')->orderBy('id')->get();

        return response()->json([
            'data' => $schedules->map(fn (Schedule $s) => $this->toJson($s))->values(),
            'colors' => Schedule::COLORS,
        ]);
    }

    /** POST /api/v1/schedules */
    public function store(Request $request): JsonResponse
    {
        $me = $request->user();
        $data = $this->validateData($request);
        $data['role'] = $me->role;
        $data['store_id'] = $me->store_id;
        $data['supplier_id'] = $me->supplier_id;
        $data['created_by'] = $me->id;
        $schedule = Schedule::create($data);

        return response()->json(['data' => $this->toJson($schedule)], 201);
    }

    /** PUT /api/v1/schedules/{schedule} */
    public function update(Request $request, Schedule $schedule): JsonResponse
    {
        $this->authorizeOwn($request, $schedule);
        $schedule->update($this->validateData($request));

        return response()->json(['data' => $this->toJson($schedule)]);
    }

    /** DELETE /api/v1/schedules/{schedule} */
    public function destroy(Request $request, Schedule $schedule): JsonResponse
    {
        $this->authorizeOwn($request, $schedule);
        $schedule->delete();

        return response()->json(['message' => '일정을 삭제했습니다.']);
    }

    private function toJson(Schedule $s): array
    {
        return [
            'id' => $s->id,
            'date' => $s->schedule_date->format('Y-m-d'),
            'title' => $s->title,
            'content' => $s->content,
            'color' => $s->color ?: 'mango',
        ];
    }

    private function authorizeOwn(Request $request, Schedule $schedule): void
    {
        $me = $request->user();
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
}
