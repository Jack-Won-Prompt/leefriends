<?php

namespace App\Market\Support;

use Illuminate\Support\Facades\Http;

/**
 * '망고정' 개점 장단점 AI 종합 분석.
 * supportworks 의 LLM 연결(OpenAI/Anthropic)을 재사용해, 규칙 기반으로 뽑은 실제 상권 지표와
 * 운영 입력(홀 테이블·배달)을 근거로 LLM 이 장단점/운영코멘트/등급을 생성한다.
 *
 * 숫자는 코드가 계산한 값만 근거로 제공(환각 방지). 실패 시 호출측이 규칙 기반으로 폴백.
 */
class MangoAiAdvisor
{
    public static function available(): bool
    {
        $cfg = config('services.market_ai');
        $provider = $cfg['provider'] ?? 'openai';

        return ! empty($cfg[$provider]['key'] ?? null);
    }

    /**
     * @return array{summary:string,pros:array,cons:array,planNotes:array,grade:array,ai:bool,ai_model:string,ai_generated_at:string}
     */
    public static function generate(array $report, array $plan = []): array
    {
        $base = MangoFranchiseAdvisor::analyze($report, $plan);
        $facts = self::facts($base['metrics'], $base['plan']);

        $system = <<<'SYS'
당신은 '리프랜즈'(망고 빙수 프랜차이즈) 개점 타당성을 평가하는 상권분석 컨설턴트입니다.
아래 제공되는 '상권 지표'와 '매장 운영 조건'만을 근거로 분석하세요. 제공되지 않은 수치를 지어내지 마세요.
리프랜즈는 젊은층·여성 중심의 체류형/테이크아웃/배달 병행 '빙수' 전문 카페입니다.
경쟁 비교는 반드시 인근 '빙수 전문 프랜차이즈' 점포(예: 설빙)만을 대상으로 하세요. 일반 카페·베이커리는 경쟁으로 세지 마세요.
홀 테이블 수(체류 수용력), 매장 평수(면적·임대료), 쿠팡잇츠·배민 연계(배달 채널)를 반드시 상권 특성과 연결해 평가하세요.
반드시 아래 JSON 스키마로만 응답하세요(설명 문장·마크다운 금지):
{
  "grade": {"label": "개점 추천|조건부 검토|신중 검토", "tone": "good|ok|caution", "score": 0-100},
  "summary": "2~4문장 한국어 종합 판단",
  "pros": [{"title": "짧은 제목", "detail": "근거 문장(숫자 포함)"}],
  "cons": [{"title": "짧은 제목", "detail": "근거 문장(숫자 포함)"}],
  "planNotes": [{"tone": "pro|con|info", "title": "짧은 제목", "detail": "홀/배달 운영 관련 코멘트"}]
}
SYS;

        $user = "다음은 분석 대상 상권의 실제 지표와 매장 운영 조건입니다.\n\n"
            .json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ."\n\n이 지역에 망고정을 개점할 때의 장단점을 위 JSON 스키마로 분석해 주세요.";

        $cfg = config('services.market_ai');
        $provider = $cfg['provider'] ?? 'openai';
        $model = $cfg['model'];
        $timeout = (int) ($cfg['timeout'] ?? 60);

        $text = $provider === 'anthropic'
            ? self::callAnthropic($cfg['anthropic'], $model, $timeout, $system, $user)
            : self::callOpenAi($cfg['openai'], $model, $timeout, $system, $user);

        $parsed = self::parseJson($text);

        return [
            'summary'   => (string) ($parsed['summary'] ?? $base['summary']),
            'pros'      => self::normList($parsed['pros'] ?? $base['pros']),
            'cons'      => self::normList($parsed['cons'] ?? $base['cons']),
            'planNotes' => self::normNotes($parsed['planNotes'] ?? $base['planNotes']),
            'grade'     => self::normGrade($parsed['grade'] ?? $base['grade']),
            'metrics'   => $base['metrics'],
            'plan'      => $base['plan'],
            'ai'        => true,
            'ai_model'  => $model,
            'ai_generated_at' => now()->toDateTimeString(),
        ];
    }

    /** LLM 에 넘길 사실(코드 계산값) */
    private static function facts(array $m, array $plan): array
    {
        return [
            'brand' => '리프랜즈 (망고 빙수 프랜차이즈)',
            '상권' => [
                '거주인구' => $m['resident'],
                '직장인구' => $m['workplace'],
                '배후세대' => $m['households'],
                '유동인구_주간합' => $m['floating'],
                '여성비중_pct' => $m['female_share'],
                '유동_피크' => $m['peak_label'],
                '카드매출_최대소비층' => $m['top_segment'],
                '학생수' => $m['students'],
                '전체점포수' => $m['stores_total'],
                '데이터수록' => $m['covered'],
            ],
            '빙수_프랜차이즈_경쟁' => [
                '점포수' => $m['bingsu_total'],
                '브랜드' => array_map(fn ($b) => $b['name'].'('.$b['count'].')', array_slice($m['bingsu_brands'], 0, 8)),
                '설명' => '경쟁은 이 빙수 전문 프랜차이즈 점포만 비교 대상입니다.',
            ],
            '운영조건' => [
                '홀_테이블수' => $plan['hall_tables'],
                '매장_평수' => $plan['area_pyeong'],
                '쿠팡잇츠_연계' => $plan['coupang'],
                '배민_연계' => $plan['baemin'],
                '입력됨' => $plan['configured'],
            ],
        ];
    }

    private static function callOpenAi(array $c, string $model, int $timeout, string $system, string $user): string
    {
        $res = Http::withOptions(['verify' => false])
            ->withHeaders(['Authorization' => 'Bearer '.($c['key'] ?? '')])
            ->timeout($timeout)
            ->post(rtrim($c['base_url'], '/').'/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

        if (! $res->successful()) {
            throw new \RuntimeException('OpenAI API '.$res->status().': '.mb_substr($res->body(), 0, 300));
        }

        return (string) ($res->json('choices.0.message.content') ?? '');
    }

    private static function callAnthropic(array $c, string $model, int $timeout, string $system, string $user): string
    {
        $res = Http::withOptions(['verify' => false])
            ->withHeaders([
                'x-api-key' => $c['key'] ?? '',
                'anthropic-version' => '2023-06-01',
            ])
            ->timeout($timeout)
            ->post(rtrim($c['base_url'], '/').'/messages', [
                'model' => $model,
                'max_tokens' => 2000,
                'system' => $system,
                'messages' => [['role' => 'user', 'content' => $user]],
            ]);

        if (! $res->successful()) {
            throw new \RuntimeException('Anthropic API '.$res->status().': '.mb_substr($res->body(), 0, 300));
        }

        return (string) ($res->json('content.0.text') ?? '');
    }

    /** 응답 텍스트에서 JSON 추출 */
    private static function parseJson(string $text): array
    {
        $text = trim($text);
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        // 코드펜스/설명이 섞였을 때 첫 { … 마지막 } 추출
        $s = strpos($text, '{');
        $e = strrpos($text, '}');
        if ($s !== false && $e !== false && $e > $s) {
            $decoded = json_decode(substr($text, $s, $e - $s + 1), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        throw new \RuntimeException('AI 응답을 JSON 으로 해석하지 못했습니다.');
    }

    private static function normList($v): array
    {
        if (! is_array($v)) return [];
        $out = [];
        foreach ($v as $it) {
            if (is_array($it) && isset($it['title'])) {
                $out[] = ['title' => (string) $it['title'], 'detail' => (string) ($it['detail'] ?? '')];
            } elseif (is_string($it)) {
                $out[] = ['title' => $it, 'detail' => ''];
            }
        }
        return $out;
    }

    private static function normNotes($v): array
    {
        if (! is_array($v)) return [];
        $out = [];
        foreach ($v as $it) {
            if (! is_array($it) || ! isset($it['title'])) continue;
            $tone = in_array($it['tone'] ?? 'info', ['pro', 'con', 'info'], true) ? $it['tone'] : 'info';
            $out[] = ['tone' => $tone, 'title' => (string) $it['title'], 'detail' => (string) ($it['detail'] ?? '')];
        }
        return $out;
    }

    private static function normGrade($v): array
    {
        $tone = in_array($v['tone'] ?? 'ok', ['good', 'ok', 'caution'], true) ? $v['tone'] : 'ok';
        $score = (int) ($v['score'] ?? 50);
        $score = max(0, min(100, $score));

        return ['label' => (string) ($v['label'] ?? '조건부 검토'), 'tone' => $tone, 'score' => $score];
    }
}
