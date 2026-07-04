<?php

namespace App\Services\Content;

use App\Models\BlogPost;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * 네이버 콘텐츠 수집 — 공식 블로그 RSS(블로그 글) / 클립 URL 메타(썸네일·제목) 추출.
 * 썸네일은 핫링크 차단/만료를 피하기 위해 서버에 직접 다운로드하여 로컬 경로로 저장한다.
 */
class NaverContentService
{
    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';

    private const THUMB_DIR = 'images/blog'; // public/ 기준

    private const CLIP_THUMB_DIR = 'images/clip'; // public/ 기준

    /**
     * 공식 네이버 블로그 RSS 를 읽어 새 글을 저장한다. 썸네일 이미지는 서버로 다운로드한다.
     *
     * @return array{added:int, total:int, images:int}
     */
    public function syncBlogPosts(?string $blogId = null): array
    {
        $blogId = $blogId ?: config('services.naver.blog_id');
        $url = "https://rss.blog.naver.com/{$blogId}.xml";

        $res = Http::withHeaders(['User-Agent' => self::UA])->timeout(20)->get($url);
        if (! $res->ok()) {
            throw new \RuntimeException("네이버 블로그 RSS 를 가져오지 못했습니다. (HTTP {$res->status()})");
        }

        $xml = @simplexml_load_string($res->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false || ! isset($xml->channel->item)) {
            throw new \RuntimeException('블로그 RSS 형식을 해석하지 못했습니다.');
        }

        $added = 0;
        $images = 0;
        foreach ($xml->channel->item as $item) {
            $link = trim((string) $item->link);
            $title = trim((string) $item->title);
            if ($link === '' || $title === '') {
                continue;
            }

            $externalId = $this->externalIdFromLink($link);
            $desc = (string) $item->description;
            $summary = Str::of(strip_tags($desc))->replaceMatches('/\s+/', ' ')->trim()->limit(120)->value();

            $posted = null;
            try {
                $posted = $item->pubDate ? Carbon::parse((string) $item->pubDate) : null;
            } catch (\Throwable) {
            }

            $existing = BlogPost::where('external_id', $externalId)->first();

            // 로컬 썸네일이 아직 없으면 다운로드 (신규 또는 기존 원격/누락 건 백필)
            $needImage = ! $existing || ! $this->isLocalThumb($existing->thumbnail);
            $thumb = $existing?->thumbnail;
            if ($needImage) {
                $downloaded = $this->downloadThumbnail($blogId, $link, $desc, $externalId);
                if ($downloaded) {
                    $thumb = $downloaded;
                    $images++;
                } elseif (! $this->isLocalThumb($thumb)) {
                    // 다운로드 실패(권한 등) 시, 배포된 images/blog/{external_id}.* 파일이 있으면 사용
                    foreach (['jpg', 'png', 'jpeg', 'webp'] as $ext) {
                        $rel = self::THUMB_DIR . '/' . $externalId . '.' . $ext;
                        if (is_file(public_path($rel))) {
                            $thumb = $rel;
                            break;
                        }
                    }
                }
            }

            if ($existing) {
                $existing->update([
                    'title' => $title,
                    'summary' => $summary,
                    'url' => $link,
                    'posted_at' => $posted,
                    'thumbnail' => $thumb,
                ]);

                continue;
            }

            BlogPost::create([
                'external_id' => $externalId,
                'title' => $title,
                'url' => $link,
                'thumbnail' => $thumb,
                'summary' => $summary,
                'posted_at' => $posted,
                'sort_order' => 0,
                'is_active' => true,
            ]);
            $added++;
        }

        return ['added' => $added, 'total' => BlogPost::count(), 'images' => $images];
    }

    /**
     * 글의 대표 이미지를 서버로 다운로드하고 로컬 상대경로(images/blog/xxx.jpg)를 반환한다.
     * 우선순위: 모바일 글 페이지 og:image → RSS 설명의 phinf 이미지.
     */
    private function downloadThumbnail(string $blogId, string $link, string $desc, string $externalId): ?string
    {
        $source = $this->resolveImageUrl($blogId, $link, $desc);
        if (! $source) {
            return null;
        }

        try {
            $res = Http::withHeaders(['User-Agent' => self::UA])->timeout(20)->get($source);
            if (! $res->ok()) {
                return null;
            }
            $body = $res->body();
            if (strlen($body) < 500) { // 손상/빈 이미지 방지
                return null;
            }

            $ext = str_contains($res->header('Content-Type'), 'png') ? 'png' : 'jpg';
            $dir = public_path(self::THUMB_DIR);
            File::ensureDirectoryExists($dir);
            $filename = $externalId . '.' . $ext;
            File::put($dir . '/' . $filename, $body);

            return self::THUMB_DIR . '/' . $filename;
        } catch (\Throwable) {
            return null;
        }
    }

    /** 다운로드 가능한 대표 이미지 URL 결정 */
    private function resolveImageUrl(string $blogId, string $link, string $desc): ?string
    {
        // 1) 모바일 글 페이지의 og:image (완전한 URL → 다운로드 가능)
        if (preg_match('#/(\d+)#', $link, $m)) {
            $mobile = "https://m.blog.naver.com/{$blogId}/{$m[1]}";
            try {
                $res = Http::withHeaders(['User-Agent' => self::UA])->timeout(15)->get($mobile);
                if ($res->ok()) {
                    $og = $this->metaContent($res->body(), 'og:image');
                    if ($og) {
                        return $og;
                    }
                }
            } catch (\Throwable) {
            }
        }

        // 2) RSS 설명의 phinf 이미지 (fallback)
        if (preg_match('/https?:\/\/[^"\'\s]*phinf\.pstatic\.net[^"\'\s]+?\.(?:jpg|jpeg|png)/i', $desc, $m)) {
            return $m[0];
        }

        return null;
    }

    private function isLocalThumb(?string $thumb): bool
    {
        return $thumb !== null && str_starts_with($thumb, self::THUMB_DIR);
    }

    /**
     * 원격 이미지 URL 을 서버로 다운로드하고 로컬 상대경로(images/clip/xxx.jpg)를 반환한다.
     * 이미 로컬 경로이거나 다운로드 실패 시 null.
     */
    public function downloadClipThumbnail(?string $sourceUrl): ?string
    {
        if (! $sourceUrl || ! Str::startsWith($sourceUrl, ['http://', 'https://'])) {
            return null;
        }

        try {
            $res = Http::withHeaders(['User-Agent' => self::UA])->timeout(20)->get($sourceUrl);
            if (! $res->ok()) {
                return null;
            }
            $body = $res->body();
            if (strlen($body) < 500) {
                return null;
            }

            $ext = str_contains((string) $res->header('Content-Type'), 'png') ? 'png' : 'jpg';
            $dir = public_path(self::CLIP_THUMB_DIR);
            File::ensureDirectoryExists($dir);
            $filename = 'clip-' . substr(md5(uniqid('', true)), 0, 10) . '.' . $ext;
            File::put($dir . '/' . $filename, $body);

            return self::CLIP_THUMB_DIR . '/' . $filename;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 클립(또는 임의 페이지) URL 에서 og:title / og:image 를 추출한다.
     *
     * @return array{title:?string, thumbnail:?string}
     */
    public function fetchClipMeta(string $url): array
    {
        try {
            $res = Http::withHeaders(['User-Agent' => self::UA])->timeout(15)->get($url);
            if (! $res->ok()) {
                return ['title' => null, 'thumbnail' => null];
            }
            $html = $res->body();

            return [
                'title' => $this->metaContent($html, 'og:title'),
                'thumbnail' => $this->metaContent($html, 'og:image'),
            ];
        } catch (\Throwable) {
            return ['title' => null, 'thumbnail' => null];
        }
    }

    private function externalIdFromLink(string $link): string
    {
        // https://blog.naver.com/{id}/{logNo}?... → {id}_{logNo}
        if (preg_match('#blog\.naver\.com/([^/]+)/(\d+)#', $link, $m)) {
            return $m[1] . '_' . $m[2];
        }

        return md5($link);
    }

    private function metaContent(string $html, string $property): ?string
    {
        $p = preg_quote($property, '/');
        // property="og:xxx" content="..."  또는  content 가 앞에 오는 경우 모두 대응
        if (preg_match('/<meta[^>]+property=["\']' . $p . '["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $m)) {
            return html_entity_decode($m[1]);
        }
        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]*property=["\']' . $p . '["\']/i', $html, $m)) {
            return html_entity_decode($m[1]);
        }

        return null;
    }
}
