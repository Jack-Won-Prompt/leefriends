<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 레시피 첨부(이미지·영상) 저장·삭제를 한곳에 모았다.
 * 웹 포털(Portal\Hq\RecipeController)과 앱 API(Api\Seller\RecipeController)가 같이 쓴다.
 *
 * 저장 위치는 public 디스크(storage/app/public)이고 주소는 /storage 심볼릭으로 서빙한다.
 * public/ 아래에 직접 쓰면 배포 계정(git)과 웹서버 계정이 달라 권한 오류가 나기 때문에,
 * 이 프로젝트의 다른 업로드(물품 이미지 등)와 같은 방식을 쓴다.
 */
class RecipeMedia
{
    /** 업로드 허용 영상 확장자 (Laravel mimes 규칙용) */
    public const VIDEO_EXTENSIONS = 'mp4,webm,mov,m4v';

    /** public 디스크 안의 저장 폴더 */
    public const IMAGE_DIR = 'recipes';

    public const VIDEO_DIR = 'recipes/videos';

    /** DB에 저장하는 상대경로의 접두사 (asset() 으로 그대로 URL이 된다) */
    public const PATH_PREFIX = 'storage/';

    /** 예전 방식으로 public/ 아래에 직접 저장했던 파일들 (삭제 호환용) */
    private const LEGACY_DIRS = ['images/recipes/', 'videos/recipes/'];

    public static function storeImage(UploadedFile $file): string
    {
        return self::put($file, self::IMAGE_DIR, 'recipe', 'jpg');
    }

    public static function storeVideo(UploadedFile $file): string
    {
        return self::put($file, self::VIDEO_DIR, 'recipe_video', 'mp4');
    }

    /** DB에 저장된 상대경로를 받아 실제 파일을 지운다. */
    public static function delete(?string $relativePath): void
    {
        $path = trim((string) $relativePath);
        if ($path === '') {
            return;
        }

        // 현재 방식 — public 디스크(storage/app/public/recipes/...)
        if (Str::startsWith($path, self::PATH_PREFIX.self::IMAGE_DIR.'/')) {
            Storage::disk('public')->delete(Str::after($path, self::PATH_PREFIX));

            return;
        }

        // 레거시 — public/images/recipes, public/videos/recipes
        if (Str::startsWith($path, self::LEGACY_DIRS)) {
            $full = public_path($path);
            if (is_file($full)) {
                @unlink($full);
            }
        }
    }

    /**
     * 이 서버가 실제로 받을 수 있는 업로드 최대 용량(MB).
     * upload_max_filesize / post_max_size 중 작은 값을 쓰고, 넉넉히 1MB를 뺀다
     * (제목·내용 등 다른 필드가 함께 전송되므로 한도에 딱 맞추면 거부될 수 있다).
     */
    public static function maxUploadMb(): int
    {
        $limits = array_filter([
            self::iniBytes('upload_max_filesize'),
            self::iniBytes('post_max_size'),
        ]);

        $bytes = $limits ? min($limits) : 8 * 1024 * 1024;
        $mb = (int) floor($bytes / 1048576) - 1;

        return max(1, $mb);
    }

    private static function put(UploadedFile $file, string $dir, string $prefix, string $fallbackExt): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $fallbackExt);
        $name = $prefix.'_'.now()->format('YmdHis').'_'.Str::lower(Str::random(6)).'.'.$ext;

        // public 디스크는 throw=false 설정이라 실패하면 예외 대신 false 를 돌려준다.
        if (Storage::disk('public')->putFileAs($dir, $file, $name) === false) {
            throw new \RuntimeException(
                "첨부 파일을 저장하지 못했습니다. 서버의 storage/app/public/{$dir} 폴더 쓰기 권한을 확인해 주세요."
            );
        }

        return self::PATH_PREFIX.$dir.'/'.$name;
    }

    /** "64M" 같은 php.ini 값을 바이트로. 0/빈값(무제한)은 null. */
    private static function iniBytes(string $key): ?int
    {
        $raw = trim((string) ini_get($key));
        if ($raw === '' || $raw === '0' || $raw === '-1') {
            return null;
        }

        $unit = strtolower(substr($raw, -1));
        $num = (float) $raw;

        return (int) match ($unit) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => $num,
        };
    }
}
