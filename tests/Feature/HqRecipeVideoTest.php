<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 레시피 영상 — 본사 등록·수정, 매장 열람까지의 흐름을 확인한다.
 * 개발 DB를 그대로 쓰므로 만든 레시피는 테스트가 끝날 때 지운다(RefreshDatabase 사용 안 함).
 */
class HqRecipeVideoTest extends TestCase
{
    private array $createdRecipeIds = [];

    /**
     * 이 앱은 하위 경로(APP_URL=.../leefriends)로 서비스되지만 테스트 클라이언트는
     * 그 경로를 벗기지 못한다. 테스트 동안만 루트를 http://localhost 로 맞춘다.
     */
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    protected function tearDown(): void
    {
        foreach (Recipe::whereIn('id', $this->createdRecipeIds)->get() as $recipe) {
            foreach ([$recipe->image, $recipe->video] as $path) {
                if ($path && is_file(public_path($path))) {
                    @unlink(public_path($path));
                }
            }
            $recipe->forceDelete();
        }

        parent::tearDown();
    }

    /**
     * 테스트 HTTP 클라이언트는 하위 경로(/leefriends)를 모르므로
     * 라우트 URL에서 APP_URL의 경로 부분을 떼어 낸다.
     */
    private function path(string $name, $params = []): string
    {
        $base = rtrim((string) parse_url((string) config('app.url'), PHP_URL_PATH), '/');
        $path = (string) parse_url(route($name, $params), PHP_URL_PATH);

        return '/'.ltrim($base !== '' ? Str::after($path, $base) : $path, '/');
    }

    private function hq(): User
    {
        $user = User::where('role', 'hq')->first();
        if (! $user) {
            $this->markTestSkipped('본사 계정이 없습니다.');
        }

        return $user;
    }

    public function test_hq_can_attach_a_video_file_and_store_can_see_it(): void
    {
        $response = $this->actingAs($this->hq())->post($this->path('portal.hq.recipes.store'), [
            'title' => '[테스트] 망고 빙수',
            'content' => '<p>얼음을 간다</p>',
            'video_file' => UploadedFile::fake()->create('mango.mp4', 300, 'video/mp4'),
            'video_link' => 'https://youtu.be/dQw4w9WgXcQ',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $recipe = Recipe::latest('id')->first();
        $this->createdRecipeIds[] = $recipe->id;

        // 영상 파일이 public/videos/recipes 에 실제로 저장됐다
        $this->assertStringStartsWith('videos/recipes/', $recipe->video);
        $this->assertFileExists(public_path($recipe->video));
        $this->assertTrue($recipe->has_video);

        // 외부 링크는 iframe 주소로 바뀐다
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', $recipe->video_embed_url);

        // 매장 화면과 앱 API 가 영상을 함께 내려준다
        $store = User::where('role', 'store')->first();
        if ($store) {
            // 카드 데이터는 @json 으로 심기므로 슬래시가 escape 된다 — 파일명과 영상 ID로 확인
            // 포털 화면은 ?panel=1 (탭 iframe) 모드에서 본문이 렌더된다
            $this->actingAs($store)->get($this->path('portal.recipes.index').'?panel=1')
                ->assertOk()
                ->assertSee(basename($recipe->video), false)
                ->assertSee('youtube.com\/embed\/dQw4w9WgXcQ', false)
                ->assertSee('▶ 영상', false);
        }
    }

    public function test_hq_can_replace_and_remove_the_video_on_update(): void
    {
        $hq = $this->hq();

        $this->actingAs($hq)->post($this->path('portal.hq.recipes.store'), [
            'title' => '[테스트] 수정 대상',
            'video_file' => UploadedFile::fake()->create('first.mp4', 120, 'video/mp4'),
        ])->assertSessionHasNoErrors();

        $recipe = Recipe::latest('id')->first();
        $this->createdRecipeIds[] = $recipe->id;
        $firstPath = $recipe->video;
        $this->assertFileExists(public_path($firstPath));

        // 1) 새 영상으로 교체 — 이전 파일은 지워진다
        $this->actingAs($hq)->put($this->path('portal.hq.recipes.update', $recipe), [
            'title' => '[테스트] 수정됨',
            'video_file' => UploadedFile::fake()->create('second.mp4', 150, 'video/mp4'),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $recipe->refresh();
        $this->assertSame('[테스트] 수정됨', $recipe->title);
        $this->assertNotSame($firstPath, $recipe->video);
        $this->assertFileExists(public_path($recipe->video));
        $this->assertFileDoesNotExist(public_path($firstPath));

        $secondPath = $recipe->video;

        // 2) 아무 파일도 안 올리면 기존 영상이 그대로 남는다
        $this->actingAs($hq)->put($this->path('portal.hq.recipes.update', $recipe), [
            'title' => '[테스트] 제목만 변경',
        ])->assertSessionHasNoErrors();

        $recipe->refresh();
        $this->assertSame($secondPath, $recipe->video);

        // 3) 삭제 체크 — 파일과 컬럼이 함께 비워진다
        $this->actingAs($hq)->put($this->path('portal.hq.recipes.update', $recipe), [
            'title' => '[테스트] 영상 삭제',
            'remove_video' => '1',
        ])->assertSessionHasNoErrors();

        $recipe->refresh();
        $this->assertNull($recipe->video);
        $this->assertFalse($recipe->has_video);
        $this->assertFileDoesNotExist(public_path($secondPath));
    }

    public function test_non_video_file_is_rejected(): void
    {
        $this->actingAs($this->hq())
            ->post($this->path('portal.hq.recipes.store'), [
                'title' => '[테스트] 잘못된 파일',
                'video_file' => UploadedFile::fake()->create('note.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('video_file');
    }

    public function test_video_link_variants_become_embed_urls(): void
    {
        $cases = [
            'https://www.youtube.com/watch?v=abc123XYZ' => 'https://www.youtube.com/embed/abc123XYZ',
            'https://youtu.be/abc123XYZ?t=10' => 'https://www.youtube.com/embed/abc123XYZ',
            'https://www.youtube.com/shorts/abc123XYZ' => 'https://www.youtube.com/embed/abc123XYZ',
            'https://vimeo.com/123456789' => 'https://player.vimeo.com/video/123456789',
            'https://cdn.example.com/a.mp4' => 'https://cdn.example.com/a.mp4',
        ];

        foreach ($cases as $link => $expected) {
            $recipe = new Recipe(['video_link' => $link]);
            $this->assertSame($expected, $recipe->video_embed_url, "링크 변환 실패: {$link}");
        }
    }
}
