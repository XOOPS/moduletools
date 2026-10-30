<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Internal;

/**
 * libpng inflates zTXt, compressed iTXt and iCCP chunks while GD reads the header, before any
 * raster is allocated, so a pixel budget does not bound them: deflate expands up to ~1000x.
 *
 * @internal
 */
final class PngMetadata
{
    // ponytail: fixed 64 KiB compressed (~64 MB inflated at worst); ICC profiles and text are a few KB
    private const int MAX_COMPRESSED_BYTES = 65_536;

    /** Whether the PNG's compressed metadata fits the budget; false for an unreadable or malformed file. */
    public static function isWithinBudget(string $path): bool
    {
        $handle = @\fopen($path, 'rb');
        if (false === $handle) {
            return false;
        }
        try {
            if ("\x89PNG\r\n\x1A\n" !== \fread($handle, 8)) {
                return false;
            }
            $compressed = 0;
            // Every chunk is walked, IDAT skipped by seek: libpng also reads text after the image data.
            while (8 === \strlen($header = (string) \fread($handle, 8))) {
                ['length' => $length, 'type' => $type] = (array) \unpack('Nlength/a4type', $header);
                if ($length > 0x7FFFFFFF) {
                    return false;
                }
                $read = 0;
                if ('zTXt' === $type || 'iCCP' === $type) {
                    $compressed += $length;
                } elseif ('iTXt' === $type) {
                    if (0 === $length) {
                        return false; // malformed, and fread() refuses a zero length
                    }
                    // keyword (1-79 bytes), NUL, then the compression flag
                    $head = (string) \fread($handle, \min($length, 81));
                    $read = \strlen($head);
                    $nul  = \strpos($head, "\0");
                    if (false === $nul || "\0" !== ($head[$nul + 1] ?? "\0")) {
                        $compressed += $length;
                    }
                } elseif ('IEND' === $type) {
                    return true;
                }
                if ($compressed > self::MAX_COMPRESSED_BYTES) {
                    return false;
                }
                \fseek($handle, $length - $read + 4, \SEEK_CUR); // rest of the data, then the CRC
            }

            return false; // truncated: no IEND (a seek past EOF ends the walk here)
        } finally {
            \fclose($handle);
        }
    }
}
