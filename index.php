<?php

// functions
function getContrastRatio($color1, $color2) {
    $rgb1 = parseColor($color1);
    $rgb2 = parseColor($color2);
    
    if (!$rgb1 || !$rgb2) {
        return false;
    }
    
    $l1 = getLuminance($rgb1);
    $l2 = getLuminance($rgb2);
    
    $lighter = max($l1, $l2);
    $darker = min($l1, $l2);
    
    return ($lighter + 0.05) / ($darker + 0.05);
}

function parseColor($color) {
    $color = strtolower(trim($color));
    
    // Handle hex
    if (preg_match('/^#?([a-f\d]{3}|[a-f\d]{6})$/', $color)) {
        $color = ltrim($color, '#');
        if (strlen($color) == 3) {
            $color = $color[0].$color[0].$color[1].$color[1].$color[2].$color[2];
        }
        return [
            hexdec(substr($color, 0, 2)),
            hexdec(substr($color, 2, 2)),
            hexdec(substr($color, 4, 2))
        ];
    }
    
    // Handle rgb/rgba
    if (preg_match('/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*[\d.]+)?\)$/', $color, $matches)) {
        return [
            (int)$matches[1],
            (int)$matches[2],
            (int)$matches[3]
        ];
    }
    
    // Handle hsl/hsla
    if (preg_match('/^hsla?\((\d+),\s*(\d+)%,\s*(\d+)%(?:,\s*[\d.]+)?\)$/', $color, $matches)) {
        return hslToRgb(
            (int)$matches[1],
            (int)$matches[2],
            (int)$matches[3]
        );
    }
    
    return false;
}

function hslToRgb($h, $s, $l) {
    $h /= 360;
    $s /= 100;
    $l /= 100;
    
    if ($s == 0) {
        $r = $g = $b = $l;
    } else {
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        
        $r = hueToRgb($p, $q, $h + 1/3);
        $g = hueToRgb($p, $q, $h);
        $b = hueToRgb($p, $q, $h - 1/3);
    }
    
    return [
        round($r * 255),
        round($g * 255),
        round($b * 255)
    ];
}

function hueToRgb($p, $q, $t) {
    if ($t < 0) $t += 1;
    if ($t > 1) $t -= 1;
    if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
    if ($t < 1/2) return $q;
    if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
    return $p;
}

function getLuminance($rgb) {
    list($r, $g, $b) = array_map(function($val) {
        $val = $val / 255;
        return $val <= 0.03928 
            ? $val / 12.92 
            : pow(($val + 0.055) / 1.055, 2.4);
    }, $rgb);
    
    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

// get color input through the form
$input = $_GET['colors'] ?? '';
$colors = $input ? array_filter(explode("\n", $input)) : [];
$colors = array_slice($colors, 0, 20);

function validateColor($color) {
    $color = trim($color);
    return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3}|[A-Fa-f0-9]{8}|[A-Fa-f0-9]{4})$/', $color) || // hex with optional alpha
           preg_match('/^rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)$/', $color) || // rgb
           preg_match('/^rgba\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*[\d.]+\s*\)$/', $color) || // rgba
           preg_match('/^hsl\(\s*\d+\s*,\s*\d+%?\s*,\s*\d+%?\s*\)$/', $color) || // hsl
           preg_match('/^hsla\(\s*\d+\s*,\s*\d+%?\s*,\s*\d+%?\s*,\s*[\d.]+\s*\)$/', $color); // hsla
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Color Display</title>
	<style>
		.color-container {
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
			margin: 20px 0;
            background: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAMUlEQVQ4T2NkYGAQYcAP3uCTZhw1gGGYhAGBZIA/nYDCgBDAm9BGDWAAJyRCgLaBCAAgXwixzAS0pgAAAABJRU5ErkJggg==") repeat;
		}
		.color-box {
			width: 100px;
			height: 100px;
		}
		textarea {
			width: 100%;
			max-width: 400px;
			height: 200px;
		}
	</style>
</head>
<body>
    <form method="GET">
        <textarea name="colors" placeholder="Enter up to 20 colors (one per line)&#10;Example:&#10;#ff0000&#10;rgba(0, 255, 0, 0.5)&#10;#0080FF80&#10;hsl(240, 100%, 50%)"><?= htmlspecialchars($input) ?></textarea>
        <br>
        <button type="submit">Show Colors</button>
    </form>

    <?php if (!empty($colors)): ?>
        <div class="color-container">
            <?php foreach($colors as $color): ?>
                <?php if (validateColor(trim($color))): ?>
                    <div class="color-box" style="background-color: <?= htmlspecialchars(trim($color)) ?>"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <p><a href="?colors=<?= urlencode($input) ?>">Share these colors</a></p>
    <?php endif; ?>
</body>
</html>
