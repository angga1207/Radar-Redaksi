<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ImageOptimizer
{
    public function deletePublicUrl(?string $url, string $directory): void
    {
        if (! $url) {
            return;
        }

        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        $path = str_starts_with($path, 'storage/') ? substr($path, 8) : '';
        if ($path !== '' && str_starts_with($path, trim($directory, '/').'/')) {
            Storage::disk('public')->delete($path);
        }
    }

    /** @return array{path: string, thumbnail_path: ?string, width: ?int, height: ?int, size: int, mime_type: string} */
    public function store(UploadedFile $file, string $directory, bool $createThumbnail = false): array
    {
        $contents = file_get_contents($file->getRealPath());
        $image = $contents !== false && function_exists('imagecreatefromstring') ? @imagecreatefromstring($contents) : false;
        if ($image !== false && function_exists('imagewebp')) {
            $basename = $directory.'/'.Str::uuid();
            ob_start();
            imagewebp($image, null, 82);
            $optimized = ob_get_clean();
            $path = $basename.'.webp';
            Storage::disk('public')->put($path, $optimized ?: $contents);
            $thumbnailPath = null;
            if ($createThumbnail && imagesx($image) > 640) {
                $thumbnail = imagescale($image, 640);
                if ($thumbnail !== false) {
                    ob_start();
                    imagewebp($thumbnail, null, 78);
                    $thumbnailContents = ob_get_clean();
                    $thumbnailPath = $basename.'-thumb.webp';
                    Storage::disk('public')->put($thumbnailPath, $thumbnailContents ?: $contents);
                    imagedestroy($thumbnail);
                }
            }
            $width = imagesx($image);
            $height = imagesy($image);
            imagedestroy($image);

            return ['path' => $path, 'thumbnail_path' => $thumbnailPath, 'width' => $width, 'height' => $height, 'size' => Storage::disk('public')->size($path), 'mime_type' => 'image/webp'];
        }
        $dimensions = @getimagesize($file->getRealPath());
        $path = $file->store($directory, 'public');

        return ['path' => $path, 'thumbnail_path' => null, 'width' => $dimensions[0] ?? null, 'height' => $dimensions[1] ?? null, 'size' => $file->getSize(), 'mime_type' => $file->getMimeType() ?: 'application/octet-stream'];
    }
}
