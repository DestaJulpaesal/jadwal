<?php

if (! extension_loaded('gd')) {
    fwrite(STDERR, "PHP GD is required to generate PWA icons.\n");
    exit(1);
}

$outputDirectory = dirname(__DIR__).'/public/icons';
if (! is_dir($outputDirectory) && ! mkdir($outputDirectory, 0755, true) && ! is_dir($outputDirectory)) {
    throw new RuntimeException('Unable to create the public icon directory.');
}

$render = static function (int $size, string $path, bool $maskable = false): void {
    $image = imagecreatetruecolor($size, $size);
    if ($image === false) {
        throw new RuntimeException('Unable to allocate an icon image.');
    }

    imageantialias($image, true);
    $scale = $size / 48;
    $background = imagecolorallocate($image, 247, 248, 245);
    $outline = imagecolorallocate($image, 29, 41, 37);
    $blue = imagecolorallocate($image, 143, 177, 255);
    $pink = imagecolorallocate($image, 255, 158, 196);
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $background);

    $centerX = (int) round($size / 2);
    $centerY = (int) round($size / 2);
    $factor = $maskable ? 0.78 : 1.0;
    $point = static fn (float $value, int $center): int => (int) round($center + ($value - 24) * $scale * $factor);
    $diameter = static fn (float $value): int => (int) round($value * $scale * $factor);
    $stroke = max(1, (int) round(2.6 * $scale * $factor));

    foreach ([10, 38] as $earX) {
        imagefilledellipse($image, $point($earX, $centerX), $point(11, $centerY), $diameter(11), $diameter(11), $pink);
        imageellipse($image, $point($earX, $centerX), $point(11, $centerY), $diameter(11), $diameter(11), $outline);
    }

    imagefilledellipse($image, $point(24, $centerX), $point(26, $centerY), $diameter(34), $diameter(34), $blue);
    imageellipse($image, $point(24, $centerX), $point(26, $centerY), $diameter(34), $diameter(34), $outline);
    imagefilledellipse($image, $point(24, $centerX), $point(26, $centerY), $diameter(25), $diameter(25), $white);
    imageellipse($image, $point(24, $centerX), $point(26, $centerY), $diameter(25), $diameter(25), $outline);

    imagesetthickness($image, $stroke);
    imageline($image, $point(13, $centerX), $point(39, $centerY), $point(10, $centerX), $point(45, $centerY), $outline);
    imageline($image, $point(35, $centerX), $point(39, $centerY), $point(38, $centerX), $point(45, $centerY), $outline);
    imageline($image, $point(4, $centerX), $point(20, $centerY), $point(4, $centerX), $point(30, $centerY), $outline);
    imageline($image, $point(44, $centerX), $point(20, $centerY), $point(44, $centerX), $point(30, $centerY), $outline);
    imagesetthickness($image, 1);

    foreach ([19.5, 28.5] as $eyeX) {
        imagefilledellipse($image, $point($eyeX, $centerX), $point(24.5, $centerY), $diameter(4.6), $diameter(4.6), $outline);
    }
    foreach ([18.8, 27.8] as $shineX) {
        imagefilledellipse($image, $point($shineX, $centerX), $point(23.7, $centerY), $diameter(1.6), $diameter(1.6), $white);
    }

    imagefilledpolygon($image, [
        $point(20.5, $centerX), $point(29.5, $centerY),
        $point(27.5, $centerX), $point(29.5, $centerY),
        $point(24, $centerX), $point(36.3, $centerY),
    ], $outline);
    foreach ([15, 33] as $cheekX) {
        imagefilledellipse($image, $point($cheekX, $centerX), $point(28.5, $centerY), $diameter(5), $diameter(3), $pink);
    }

    if (! imagepng($image, $path, 9)) {
        imagedestroy($image);
        throw new RuntimeException("Unable to write icon: {$path}");
    }

    imagedestroy($image);
};

$render(192, $outputDirectory.'/jadwal-192.png');
$render(512, $outputDirectory.'/jadwal-512.png');
$render(512, $outputDirectory.'/jadwal-maskable-512.png', true);
$render(180, $outputDirectory.'/apple-touch-icon.png');

fwrite(STDOUT, "PWA icons generated in {$outputDirectory}\n");
