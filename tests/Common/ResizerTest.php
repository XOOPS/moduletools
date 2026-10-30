<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Xoops\Helpers\Utility\Filesystem;
use Xoops\ModuleTools\Common\ResizeRequest;
use Xoops\ModuleTools\Common\Resizer;

#[CoversClass(Resizer::class)]
#[RequiresPhpExtension('gd')]
final class ResizerTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/moduletools-resize-' . bin2hex(random_bytes(6));
        self::assertTrue(Filesystem::mkdir($this->dir));
    }

    protected function tearDown(): void
    {
        Filesystem::deleteDirectory($this->dir);
    }

    public function testCoverOfAThinSourceNeverAllocatesMoreThanTheBox(): void
    {
        // 1 x 3000 into 200 x 200 used to build a 200 x 600000 intermediate (~480 MB).
        $source = $this->png(1, 3000);
        memory_reset_peak_usage();
        $before = memory_get_peak_usage();

        $result = new Resizer()->resize(new ResizeRequest($source, $this->dir . '/out.png', 200, 200, ResizeRequest::FIT_COVER));

        self::assertTrue($result->ok, (string) $result->error);
        self::assertSame([200, 200], [$result->width, $result->height]);
        self::assertLessThan(16 * 1024 * 1024, memory_get_peak_usage() - $before);
    }

    public function testCoverCropsTheCentreOfTheSource(): void
    {
        // 400 x 200 into 100 x 100: the crop is source x 100..299, so the blue left quarter is cut away.
        $image = imagecreatetruecolor(400, 200);
        imagefilledrectangle($image, 0, 0, 399, 199, (int) imagecolorallocate($image, 255, 0, 0));
        imagefilledrectangle($image, 0, 0, 99, 199, (int) imagecolorallocate($image, 0, 0, 255));
        imagepng($image, $source = $this->dir . '/wide.png');

        $result = new Resizer()->resize(new ResizeRequest($source, $target = $this->dir . '/out.png', 100, 100, ResizeRequest::FIT_COVER));

        self::assertSame([100, 100], [$result->width, $result->height]);
        $out = imagecreatefrompng($target);
        self::assertInstanceOf(\GdImage::class, $out);
        foreach ([0, 50, 99] as $x) {
            self::assertSame(['red' => 255, 'green' => 0, 'blue' => 0, 'alpha' => 0], imagecolorsforindex($out, imagecolorat($out, $x, 50)), "x=$x");
        }
    }

    public function testCoverKeepsSubPixelCentringOnTinySources(): void
    {
        // 1 x 2 (red over blue) into 200 x 100: the centre crop straddles both rows.
        $image = imagecreatetruecolor(1, 2);
        imagesetpixel($image, 0, 0, (int) imagecolorallocate($image, 255, 0, 0));
        imagesetpixel($image, 0, 1, (int) imagecolorallocate($image, 0, 0, 255));
        imagepng($image, $source = $this->dir . '/tiny.png');

        new Resizer()->resize(new ResizeRequest($source, $target = $this->dir . '/out.png', 200, 100, ResizeRequest::FIT_COVER));

        $out = imagecreatefrompng($target);
        self::assertInstanceOf(\GdImage::class, $out);
        self::assertGreaterThan(200, imagecolorsforindex($out, imagecolorat($out, 100, 0))['red']);
        self::assertGreaterThan(200, imagecolorsforindex($out, imagecolorat($out, 100, 99))['blue']);
    }

    public function testCoverCentresSmallDownscalesAndBoundsExtremeBoxes(): void
    {
        // 100 x 100, blue left half / red right half, into 1 x 2: the centre crop is columns
        // 25..75, so the result mixes both. The old 2x2 intermediate took its left column: pure blue.
        $image = imagecreatetruecolor(100, 100);
        imagefilledrectangle($image, 0, 0, 49, 99, (int) imagecolorallocate($image, 0, 0, 255));
        imagefilledrectangle($image, 50, 0, 99, 99, (int) imagecolorallocate($image, 255, 0, 0));
        imagepng($image, $source = $this->dir . '/halves.png');

        new Resizer()->resize(new ResizeRequest($source, $target = $this->dir . '/out.png', 1, 2, ResizeRequest::FIT_COVER));
        $out = imagecreatefrompng($target);
        self::assertInstanceOf(\GdImage::class, $out);
        $red = imagecolorsforindex($out, imagecolorat($out, 0, 0))['red'];
        self::assertGreaterThan(80, $red);
        self::assertLessThan(175, $red);

        // 1 x 1 into 10000 x 1 would need a 10000 x 10000 intermediate (~400 MB) for sub-pixel centring.
        memory_reset_peak_usage();
        $before = memory_get_peak_usage();
        $result = new Resizer()->resize(new ResizeRequest($this->png(1, 1), $this->dir . '/wide.png', 10_000, 1, ResizeRequest::FIT_COVER));
        self::assertSame([10_000, 1], [$result->width, $result->height]);
        self::assertLessThan(16 * 1024 * 1024, memory_get_peak_usage() - $before);
    }

    public function testANonPositiveTargetEdgeCannotDodgeTheBudget(): void
    {
        // Rendering clamps each edge to 1, so 2147483648 x 0 is a 2147483648 x 1 canvas.
        $source = $this->png(10, 10);
        foreach ([[2_147_483_648, 0], [2_147_483_648, -1], [0, 2_147_483_648]] as [$w, $h]) {
            $result = new Resizer()->resize(new ResizeRequest($source, $this->dir . '/out.png', $w, $h));

            self::assertFalse($result->ok, "{$w}x{$h}");
            self::assertStringContainsString('pixel budget', (string) $result->error);
        }
    }

    public function testAnOversizedHeaderIsRefusedBeforeDecoding(): void
    {
        // A tiny file whose header claims 20000 x 20000 would decode to ~1.6 GB.
        $ihdr   = pack('NNCCCCC', 20000, 20000, 8, 2, 0, 0, 0);
        $chunk  = static fn (string $type, string $data): string => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        $source = $this->dir . '/bomb.png';
        file_put_contents($source, "\x89PNG\r\n\x1a\n" . $chunk('IHDR', $ihdr) . $chunk('IEND', ''));

        $result = new Resizer()->resize(new ResizeRequest($source, $this->dir . '/out.png', 100, 100));

        self::assertFalse($result->ok);
        self::assertStringContainsString('pixel budget', (string) $result->error);
    }

    public function testANonImageSignatureNeverReachesTheMetadataParser(): void
    {
        // "CWS" is a zlib-compressed SWF, which getimagesize() would inflate in memory.
        $source = $this->dir . '/movie.swf';
        file_put_contents($source, 'CWS' . chr(10) . pack('V', 1_000_000_000) . gzcompress(str_repeat("\0", 1024)));

        $result = new Resizer()->resize(new ResizeRequest($source, $this->dir . '/out.png', 100, 100));

        self::assertFalse($result->ok);
        self::assertStringContainsString('Unsupported image format', (string) $result->error);
        self::assertTrue(new Resizer()->resize(new ResizeRequest($this->png(4, 4), $this->dir . '/ok.png', 2, 2))->ok);
    }

    private function png(int $width, int $height): string
    {
        $path = $this->dir . "/{$width}x{$height}.png";
        imagepng(imagecreatetruecolor($width, $height), $path);

        return $path;
    }
}
