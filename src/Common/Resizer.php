<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Common;

/**
 * Pure GD image resizer — a {@see ResizeRequest} in, a {@see ResizeResult} out.
 *
 * Consolidates the ~19 near-identical `Common\Resizer` copies scattered across modules into
 * one tested, side-effect-free class. Unlike those copies it:
 *   - is request-agnostic (no `$_POST`/`$_FILES`, no echo, no redirect),
 *   - exposes explicit fit modes (inside / cover / stretch) instead of one-method-per-shape,
 *   - corrects JPEG EXIF orientation (phone photos no longer come out sideways),
 *   - preserves the source format and PNG/GIF transparency, and
 *   - honours the requested quality consistently (the legacy copies hard-coded JPEG 100 in
 *     one path and left a width unset in another).
 *
 * GD-only by design (every legacy copy was GD); an Imagick strategy can be added later.
 *
 * @api Stable Common-tier API (Lab\* is experimental, module-local code is private).
 * @since 1.2.0
 */
final class Resizer implements ImageResizerInterface
{
    public function resize(ResizeRequest $request): ResizeResult
    {
        $target = $request->targetPath;

        if (!\extension_loaded('gd')) {
            return ResizeResult::failure($target, 'The GD extension is not available.');
        }
        if (!\is_file($request->sourcePath)) {
            return ResizeResult::failure($target, 'Source image not found: ' . $request->sourcePath);
        }
        $info = @\getimagesize($request->sourcePath);
        if (false === $info) {
            return ResizeResult::failure($target, 'Not a readable image: ' . $request->sourcePath);
        }
        $mime = (string) $info['mime'];

        $image = $this->createImage($request->sourcePath, $mime);
        if (!$image instanceof \GdImage) {
            return ResizeResult::failure($target, 'Unsupported image format: ' . $mime);
        }
        if ('image/jpeg' === $mime) {
            $image = $this->applyExifOrientation($image, $request->sourcePath);
        }

        $srcW = \imagesx($image);
        $srcH = \imagesy($image);

        $outputMime = $request->outputMime ?? $mime;
        $canvas = $this->render($request, $image, $srcW, $srcH, $outputMime);
        if (!$this->save($canvas, $target, $outputMime, $request->quality)) {
            return ResizeResult::failure($target, 'Failed to write the target image: ' . $target);
        }

        return ResizeResult::success($target, \imagesx($canvas), \imagesy($canvas));
    }

    private function createImage(string $path, string $mime): ?\GdImage
    {
        $image = match ($mime) {
            'image/png'  => @\imagecreatefrompng($path),
            'image/jpeg' => @\imagecreatefromjpeg($path) ?: @\imagecreatefromstring((string) @\file_get_contents($path)),
            'image/gif'  => @\imagecreatefromgif($path),
            'image/webp' => \function_exists('imagecreatefromwebp') ? @\imagecreatefromwebp($path) : false,
            default      => false,
        };

        return $image instanceof \GdImage ? $image : null;
    }

    /**
     * Rotate a JPEG to its EXIF "Orientation" so portrait phone photos are upright.
     * Handles the three common rotations (3/6/8); leaves the rarer flip cases as-is.
     */
    private function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        if (!\function_exists('exif_read_data')) {
            return $image;
        }
        $exif        = @\exif_read_data($path);
        $orientation = \is_array($exif) ? (int) ($exif['Orientation'] ?? 0) : 0;

        $rotated = match ($orientation) {
            3       => \imagerotate($image, 180, 0),
            6       => \imagerotate($image, -90, 0),
            8       => \imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated instanceof \GdImage ? $rotated : $image;
    }

    private function render(ResizeRequest $r, \GdImage $src, int $srcW, int $srcH, string $mime): \GdImage
    {
        $maxW = \max(1, $r->maxWidth);
        $maxH = \max(1, $r->maxHeight);

        if (ResizeRequest::FIT_COVER === $r->fit) {
            // Scale to cover the box, then centre-crop to exactly maxW x maxH.
            $ratio   = \max($maxW / $srcW, $maxH / $srcH);
            $resizeW = \max(1, (int) \ceil($srcW * $ratio));
            $resizeH = \max(1, (int) \ceil($srcH * $ratio));

            $scaled = $this->newCanvas($resizeW, $resizeH, $mime);
            \imagecopyresampled($scaled, $src, 0, 0, 0, 0, $resizeW, $resizeH, $srcW, $srcH);

            $cropX  = (int) \max(0, \floor(($resizeW - $maxW) / 2));
            $cropY  = (int) \max(0, \floor(($resizeH - $maxH) / 2));
            $canvas = $this->newCanvas($maxW, $maxH, $mime);
            \imagecopy($canvas, $scaled, 0, 0, $cropX, $cropY, $maxW, $maxH);

            return $canvas;
        }

        if (ResizeRequest::FIT_STRETCH === $r->fit) {
            $newW = $maxW;
            $newH = $maxH;
        } else { // FIT_INSIDE
            $ratio = \min($maxW / $srcW, $maxH / $srcH);
            if (!$r->allowUpscale) {
                $ratio = \min($ratio, 1.0);
            }
            $newW = \max(1, (int) \round($srcW * $ratio));
            $newH = \max(1, (int) \round($srcH * $ratio));
        }

        $canvas = $this->newCanvas($newW, $newH, $mime);
        \imagecopyresampled($canvas, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        return $canvas;
    }

    private function newCanvas(int $width, int $height, string $mime): \GdImage
    {
        $canvas = \imagecreatetruecolor(\max(1, $width), \max(1, $height));
        if (\in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
            \imagealphablending($canvas, false);
            \imagesavealpha($canvas, true);
            $transparent = \imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            if (false !== $transparent) {
                \imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
            }
        }

        return $canvas;
    }

    private function save(\GdImage $image, string $path, string $mime, int $quality): bool
    {
        $quality = \max(0, \min(100, $quality));

        return match ($mime) {
            'image/png'  => \imagepng($image, $path, $this->pngLevel($quality)),
            'image/jpeg' => \imagejpeg($image, $path, $quality),
            'image/gif'  => \imagegif($image, $path),
            'image/webp' => \function_exists('imagewebp') && \imagewebp($image, $path, $quality),
            default      => false,
        };
    }

    /** Map quality 0–100 to PNG zlib compression 9–0 (higher quality => less compression). */
    private function pngLevel(int $quality): int
    {
        return \max(0, \min(9, 9 - (int) \round($quality / 100 * 9)));
    }
}
