<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * 레시피 첨부(이미지·영상) 저장·삭제를 한곳에 모았다.
 * 웹 포털(Portal\Hq\RecipeController)과 앱 API(Api\Seller\RecipeController)가 같이 쓴다.
 * 파일은 public/ 아래에 두어 웹·앱이 같은 주소로 본다.
 */
class RecipeMedia
{
    /** 업로드 허용 영상 확장자 (Laravel mimes 규칙용) */
    public const VIDEO_EXTENSIONS = 'mp4,webm,mov,m4v';

    public const IMAGE_DIR = 'images/recipes';

    public const VIDEO_DIR = 'videos/recipes';

    public static function storeImage(UploadedFile $file): string
    {
        return self::put($file, self::IMAGE_DIR, 'recipe', 'jpg');
    }

    public static function storeVideo(UploadedFile $file): string
    {
        return self::put($file, self::VIDEO_DIR, 'recipe_video', 'mp4');
    }

    /** public/ 기준 상대 경로를 받아 실제 파일을 지운다. */
    public static function delete(?string $relativePath): void
    {
        $relativePath = trim((string) $relativePath);
        if ($relativePath === '') {
            return;
        }
        // 안전장치: 지정한 업로드 폴더 밖은 건드리지 않는다
        if (! Str::startsWith($relativePath, [self::IMAGE_DIR.'/', self::VIDEO_DIR.'/'])) {
            return;
        }
        $full = public_path($relativePath);
        if (is_file($full)) {
            @unlink($full);
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

        File::ensureDirectoryExists(public_path($dir));
        $file->move(public_path($dir), $name);

        return $dir.'/'.$name;
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
