<?php
/**
 * Hairdresser Pro - Placeholder Image Generator
 * Generates placeholder images using PHP GD library
 * Run: php generate_images.php
 */

$imagesDir = __DIR__ . '/images';
$uploadsDir = $imagesDir . '/uploads';

if (!is_dir($imagesDir)) mkdir($imagesDir, 0777, true);
if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

if (!extension_loaded('gd')) {
    echo "GD extension not loaded. Creating SVG placeholders instead.\n";
    createSvgPlaceholders($imagesDir);
    exit;
}

echo "Generating placeholder images...\n";

// Logo
createImage($imagesDir . '/logo.png', 200, 200, '#6200ea', '#ffffff', 'HP', 60);
echo "✓ logo.png\n";

// Hero background
createGradientImage($imagesDir . '/hero-bg.png', 1200, 600, '#1a0033', '#6200ea', 'Salon Interior');
echo "✓ hero-bg.png\n";

// Hairdresser photos
$colors = ['#bb86fc', '#03dac6', '#cf6679'];
$names = ['SM', 'JW', 'ER'];
for ($i = 1; $i <= 3; $i++) {
    createImage($imagesDir . "/hairdresser-{$i}.png", 300, 300, $colors[$i-1], '#ffffff', $names[$i-1], 80);
    echo "✓ hairdresser-{$i}.png\n";
}

// Service images
$serviceColors = ['#6200ea', '#03dac6', '#bb86fc', '#cf6679', '#ff7043'];
$serviceLabels = ['✂', '🎨', '💇', '💆', '✂'];
$serviceNames = ['haircut', 'coloring', 'blowdry', 'treatment', 'beard'];
for ($i = 0; $i < 5; $i++) {
    createImage($imagesDir . "/service-{$serviceNames[$i]}.png", 400, 300, $serviceColors[$i], '#ffffff', $serviceLabels[$i], 60);
    echo "✓ service-{$serviceNames[$i]}.png\n";
}

// Default avatar
createImage($imagesDir . '/default-avatar.png', 200, 200, '#444444', '#ffffff', '?', 60);
echo "✓ default-avatar.png\n";

echo "\nAll images generated successfully!\n";

/**
 * Create a simple image with text
 */
function createImage(string $path, int $w, int $h, string $bgColor, string $textColor, string $text, int $fontSize): void {
    $img = imagecreatetruecolor($w, $h);
    imagesavealpha($img, true);

    $bg = hexToColor($img, $bgColor);
    $tc = hexToColor($img, $textColor);

    imagefilledrectangle($img, 0, 0, $w, $h, $bg);

    // Add subtle pattern
    $pattern = hexToColor($img, adjustBrightness($bgColor, 15));
    for ($i = 0; $i < $w; $i += 20) {
        imageline($img, $i, 0, $i + $h, $h, $pattern);
    }

    // Draw circle for avatars
    if ($w === $h && $w <= 300) {
        $cx = (int)($w / 2);
        $cy = (int)($h / 2);
        $lighter = hexToColor($img, adjustBrightness($bgColor, 25));
        imagefilledellipse($img, $cx, $cy, (int)($w * 0.8), (int)($h * 0.8), $lighter);
    }

    // Add text
    $textWidth = imagefontwidth(5) * strlen($text);
    $textHeight = imagefontheight(5);
    $x = (int)(($w - $textWidth) / 2);
    $y = (int)(($h - $textHeight) / 2);

    // Use built-in font (larger)
    $font = 5;
    imagestring($img, $font, $x, $y, $text, $tc);

    imagepng($img, $path, 6);
    imagedestroy($img);
}

/**
 * Create gradient image
 */
function createGradientImage(string $path, int $w, int $h, string $color1, string $color2, string $label): void {
    $img = imagecreatetruecolor($w, $h);

    $r1 = hexdec(substr($color1, 1, 2));
    $g1 = hexdec(substr($color1, 3, 2));
    $b1 = hexdec(substr($color1, 5, 2));
    $r2 = hexdec(substr($color2, 1, 2));
    $g2 = hexdec(substr($color2, 3, 2));
    $b2 = hexdec(substr($color2, 5, 2));

    for ($i = 0; $i < $h; $i++) {
        $r = (int)($r1 + ($r2 - $r1) * $i / $h);
        $g = (int)($g1 + ($g2 - $g1) * $i / $h);
        $b = (int)($b1 + ($b2 - $b1) * $i / $h);
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $i, $w, $i, $color);
    }

    // Add decorative elements
    $white = imagecolorallocatealpha($img, 255, 255, 255, 100);
    imagefilledellipse($img, (int)($w * 0.3), (int)($h * 0.4), 200, 200, $white);
    imagefilledellipse($img, (int)($w * 0.7), (int)($h * 0.6), 150, 150, $white);

    // Label
    $tc = imagecolorallocate($img, 255, 255, 255);
    $textWidth = imagefontwidth(5) * strlen($label);
    $x = (int)(($w - $textWidth) / 2);
    imagestring($img, 5, $x, (int)($h / 2), $label, $tc);

    imagepng($img, $path, 6);
    imagedestroy($img);
}

function hexToColor($img, string $hex): int {
    $hex = ltrim($hex, '#');
    return imagecolorallocate($img, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
}

function adjustBrightness(string $hex, int $amount): string {
    $hex = ltrim($hex, '#');
    $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $amount));
    $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $amount));
    $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $amount));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

/**
 * SVG fallback if GD is not available
 */
function createSvgPlaceholders(string $dir): void {
    $files = [
        'logo.png' => svgPlaceholder(200, 200, '#6200ea', 'HP'),
        'hero-bg.png' => svgPlaceholder(1200, 600, '#1a0033', 'Salon'),
        'hairdresser-1.png' => svgPlaceholder(300, 300, '#bb86fc', 'SM'),
        'hairdresser-2.png' => svgPlaceholder(300, 300, '#03dac6', 'JW'),
        'hairdresser-3.png' => svgPlaceholder(300, 300, '#cf6679', 'ER'),
        'service-haircut.png' => svgPlaceholder(400, 300, '#6200ea', 'Haircut'),
        'service-coloring.png' => svgPlaceholder(400, 300, '#03dac6', 'Coloring'),
        'service-blowdry.png' => svgPlaceholder(400, 300, '#bb86fc', 'BlowDry'),
        'service-treatment.png' => svgPlaceholder(400, 300, '#cf6679', 'Treatment'),
        'service-beard.png' => svgPlaceholder(400, 300, '#ff7043', 'Beard'),
        'default-avatar.png' => svgPlaceholder(200, 200, '#444444', '?'),
    ];

    foreach ($files as $name => $svg) {
        // Save as SVG since GD is not available
        $svgName = str_replace('.png', '.svg', $name);
        file_put_contents($dir . '/' . $svgName, $svg);

        // Also create a minimal 1x1 PNG as fallback
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPj/HwADBwIAMCbHYQAAAABJRU5ErkJggg==');
        file_put_contents($dir . '/' . $name, $png);

        echo "✓ {$name}\n";
    }
}

function svgPlaceholder(int $w, int $h, string $color, string $text): string {
    return <<<SVG
<svg width="{$w}" height="{$h}" xmlns="http://www.w3.org/2000/svg">
  <rect width="100%" height="100%" fill="{$color}"/>
  <text x="50%" y="50%" fill="white" font-family="Arial" font-size="24" text-anchor="middle" dy=".3em">{$text}</text>
</svg>
SVG;
}
