<?php

/**
 * Unit test of brandkit_is_ico_file(): real icons pass, look-alikes fail.
 * Usage: php tests/unit/ico_test.php [/path/to/glpi]
 */

define('GLPI_ROOT', $argv[1] ?? sys_get_temp_dir());
require __DIR__ . '/../../inc/assets.php';

function png(int $size): string
{
    $img = imagecreatetruecolor($size, $size);
    imagefill($img, 0, 0, imagecolorallocate($img, 200, 30, 30));
    ob_start();
    imagepng($img);
    return (string) ob_get_clean();
}

function dib(int $size): string
{
    // BITMAPINFOHEADER (height doubled for the AND mask) + 32 bpp pixels + mask
    $header = pack('VVVvvVVVVVV', 40, $size, $size * 2, 1, 32, 0, 0, 0, 0, 0, 0);
    return $header . str_repeat("\x1e\x1e\xc8\xff", $size * $size) . str_repeat("\x00", (int) (ceil($size / 32) * 4 * $size));
}

/** @param string[] $images */
function ico(array $images): string
{
    $count = count($images);
    $offset = 6 + 16 * $count;
    $dir = pack('vvv', 0, 1, $count);
    $data = '';
    foreach ($images as $size => $image) {
        $dir .= pack('CCCCvvVV', $size % 256, $size % 256, 0, 0, 1, 32, strlen($image), $offset + strlen($data));
        $data .= $image;
    }
    return $dir . $data;
}

$valid = ico([16 => png(16), 32 => png(32), 256 => png(256)]);
$cases = [
    'PNG icons, 3 sizes' => [$valid, true],
    'BMP (DIB) icons' => [ico([16 => dib(16), 32 => dib(32)]), true],
    'plugin pics/favicon.ico' => [file_get_contents(__DIR__ . '/../../pics/favicon.ico'), true],
    'icon header + HTML' => ["\x00\x00\x01\x00\x01\x00" . str_repeat('<html><script>alert(1)</script></html>', 3), false],
    'PNG renamed .ico' => [png(32), false],
    'truncated icon' => [substr($valid, 0, 40), false],
    'entry pointing outside the file' => [substr_replace($valid, pack('V', 1000000), 6 + 12, 4), false],
    'valid directory, HTML image data' => [substr_replace($valid, '<script>', 6 + 16 * 3, 8), false],
    'empty file' => ['', false],
];
$glpiFavicon = GLPI_ROOT . '/public/pics/favicon.ico';
if (is_file($glpiFavicon)) {
    $cases['GLPI public/pics/favicon.ico'] = [file_get_contents($glpiFavicon), true];
}

$failed = 0;
foreach ($cases as $name => [$content, $expected]) {
    $file = tempnam(sys_get_temp_dir(), 'ico');
    file_put_contents($file, $content);
    $actual = brandkit_is_ico_file($file);
    unlink($file);
    $ok = $actual === $expected;
    $failed += $ok ? 0 : 1;
    printf("  %s %-34s %s\n", $ok ? 'OK  ' : 'FAIL', $name, $actual ? 'accepted' : 'refused');
}
printf("  %d/%d passed\n", count($cases) - $failed, count($cases));
exit($failed ? 1 : 0);
