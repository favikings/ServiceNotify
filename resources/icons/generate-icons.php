<?php

declare(strict_types=1);

if (!extension_loaded('gd')) {
    fwrite(STDERR, "The GD extension is required to generate PWA icons.\n");
    exit(1);
}

$outputDirectory = dirname(__DIR__, 2) . '/public/assets/icons';
if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0775, true) && !is_dir($outputDirectory)) {
    throw new RuntimeException('Unable to create the PWA icon output directory.');
}

/** @param array{size:int,maskable:bool} $definition */
function generateIcon(string $path, array $definition): void
{
    $size = $definition['size'];
    $scale = 4;
    $canvasSize = $size * $scale;
    $image = imagecreatetruecolor($canvasSize, $canvasSize);
    imageantialias($image, true);

    $canvas = imagecolorallocate($image, 13, 20, 16);
    $surface = imagecolorallocate($image, 28, 40, 32);
    $border = imagecolorallocate($image, 48, 64, 55);
    $green = imagecolorallocate($image, 114, 229, 140);
    $greenMid = imagecolorallocate($image, 76, 146, 93);
    $greenQuiet = imagecolorallocate($image, 41, 78, 50);
    imagefill($image, 0, 0, $canvas);

    $safeFactor = $definition['maskable'] ? 0.28 : 0.16;
    $inset = (int) round($canvasSize * $safeFactor);
    $corner = (int) round($canvasSize * 0.16);
    imagefilledrectangle($image, $inset + $corner, $inset, $canvasSize - $inset - $corner, $canvasSize - $inset, $surface);
    imagefilledrectangle($image, $inset, $inset + $corner, $canvasSize - $inset, $canvasSize - $inset - $corner, $surface);
    imagefilledellipse($image, $inset + $corner, $inset + $corner, $corner * 2, $corner * 2, $surface);
    imagefilledellipse($image, $canvasSize - $inset - $corner, $inset + $corner, $corner * 2, $corner * 2, $surface);
    imagefilledellipse($image, $inset + $corner, $canvasSize - $inset - $corner, $corner * 2, $corner * 2, $surface);
    imagefilledellipse($image, $canvasSize - $inset - $corner, $canvasSize - $inset - $corner, $corner * 2, $corner * 2, $surface);

    $center = (int) ($canvasSize / 2);
    $available = $canvasSize - ($inset * 2);
    $outer = (int) round($available * 0.78);
    $middle = (int) round($available * 0.49);
    $dot = (int) round($available * 0.18);
    imagesetthickness($image, max(2, (int) round($canvasSize * 0.012)));
    imageellipse($image, $center, $center, $outer, $outer, $greenQuiet);
    imageellipse($image, $center, $center, $middle, $middle, $greenMid);
    imagefilledellipse($image, $center, $center, $dot, $dot, $green);

    imagesetthickness($image, max(2, (int) round($canvasSize * 0.006)));
    $borderInset = $inset + max(3, (int) round($canvasSize * 0.012));
    imagerectangle($image, $borderInset, $borderInset, $canvasSize - $borderInset, $canvasSize - $borderInset, $border);

    $output = imagecreatetruecolor($size, $size);
    imagecopyresampled($output, $image, 0, 0, 0, 0, $size, $size, $canvasSize, $canvasSize);
    imagepng($output, $path, 9);
    imagedestroy($output);
    imagedestroy($image);
}

$icons = [
    'icon-192.png' => ['size' => 192, 'maskable' => false],
    'icon-512.png' => ['size' => 512, 'maskable' => false],
    'icon-maskable-512.png' => ['size' => 512, 'maskable' => true],
    'apple-touch-icon.png' => ['size' => 180, 'maskable' => false],
];

foreach ($icons as $filename => $definition) {
    generateIcon($outputDirectory . '/' . $filename, $definition);
    fwrite(STDOUT, 'Generated ' . $filename . PHP_EOL);
}
