<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function parseColor($color) {
    // Strip any CSS syntax or semicolons
    $color = preg_replace('/.*:\s*/', '', $color); // Remove anything before and including ':'
    $color = preg_replace('/;.*/', '', $color);    // Remove semicolon and anything after
    $color = preg_replace('/}.*/', '', $color);    // Remove closing brace and anything after
    $color = trim($color);
    
    // Convert all colors to RGB array format [r, g, b]
    if (preg_match('/^#([A-Fa-f0-9]{3,8})$/', $color, $matches)) {
        // Hex color (including alpha)
        $hex = ltrim($color, '#');
        $length = strlen($hex);
        
        if ($length == 3) {
            // #RGB format
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        } elseif ($length == 4) {
            // #RGBA format
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; // Ignore alpha channel
        } elseif ($length == 8) {
            // #RRGGBBAA format
            $hex = substr($hex, 0, 6); // Ignore alpha channel
        }
        
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
    } elseif (preg_match('/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/', $color, $matches)) {
        return [
            intval($matches[1]),
            intval($matches[2]),
            intval($matches[3])
        ];
    } elseif (preg_match('/^rgba\((\d+),\s*(\d+),\s*(\d+),\s*([\d.]+)\)$/', $color, $matches)) {
        return [
            intval($matches[1]),
            intval($matches[2]),
            intval($matches[3])
        ];
    } elseif (preg_match('/^hsl\((\d+),\s*(\d+)%,\s*(\d+)%\)$/', $color, $matches)) {
        return hslToRgb($matches[1], $matches[2], $matches[3]);
    } elseif (preg_match('/^hsla\((\d+),\s*(\d+)%,\s*(\d+)%,\s*([\d.]+)\)$/', $color, $matches)) {
        return hslToRgb($matches[1], $matches[2], $matches[3]);
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
    $rgb = array_map(function($val) {
        $val = $val / 255;
        return $val <= 0.03928 ? $val / 12.92 : pow(($val + 0.055) / 1.055, 2.4);
    }, $rgb);
    
    return $rgb[0] * 0.2126 + $rgb[1] * 0.7152 + $rgb[2] * 0.0722;
}

function getContrastRatio($l1, $l2) {
    $lighter = max($l1, $l2);
    $darker = min($l1, $l2);
    return ($lighter + 0.05) / ($darker + 0.05);
}

function getWCAGLevel($ratio) {
    if ($ratio >= 7) return 'AAA';
    if ($ratio >= 4.5) return 'AA';
    if ($ratio >= 3) return 'AA Large';
    return 'Fail';
}

// Process form submission
$parsed_colors = [];
$summary = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['colors'])) {
    $colors = array_filter(array_map('trim', explode("\n", $_POST['colors'])));
    $colors = array_slice($colors, 0, 20); // Limit to 20 colors
    
    foreach ($colors as $color) {
        $rgb = parseColor($color);
        if ($rgb !== false) {
            $parsed_colors[$color] = [
                'rgb' => $rgb,
                'luminance' => getLuminance($rgb)
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WCAG Color Contrast Analyzer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; margin: 20px 0; width: 100%; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; }
        textarea { width: 100%; max-width: 400px; height: 200px; }
        .checkered {background: conic-gradient(#f3f3f3 90deg, #ffffff 90deg 180deg, #f3f3f3 180deg 270deg, #ffffff 270deg); background-repeat: repeat; background-size: 40px 40px; }
    </style>
</head>
<body>
    <h1>WCAG Color Contrast Analyzer</h1>
    
    <form method="post">
        <p>Enter up to 20 colors (one per line) in any of these formats:</p>
        <ul>
            <li>Hex: #FFF, #FFFFFF, #FFFF (with alpha), #FFFFFFFF (with alpha)</li>
            <li>RGB: rgb(255, 255, 255)</li>
            <li>RGBA: rgba(255, 255, 255, 0.5)</li>
            <li>HSL: hsl(360, 100%, 100%)</li>
            <li>HSLA: hsla(360, 100%, 100%, 0.5)</li>
            <li>CSS syntax is also accepted (e.g., "color: #FFF;" or "background-color: rgb(255, 0, 0);")</li>
        </ul>
        <textarea name="colors" required><?= isset($_POST['colors']) ? htmlspecialchars($_POST['colors']) : '' ?></textarea>
        <br><br>
        <button type="submit">Calculate Contrast</button>
    </form>

    <?php if (!empty($parsed_colors)): ?>
        <h2>Summary of Compatible Color Combinations</h2>
        <table class="checkered">
            <thead>
                <tr>
                    <th>Background</th>
                    <th>AAA ≥ 7.0</th>
                    <th>AA Normal ≥ 4.5</th>
                    <th>AA Large ≥ 3.0</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($parsed_colors as $bg_color => $bg): 
                    // Calculate all combinations for this background color
                    $combinations = [];
                    foreach ($parsed_colors as $fg_color => $fg) {
                        if ($fg_color === $bg_color) continue;
                        $contrast = getContrastRatio($bg['luminance'], $fg['luminance']);
                        $wcag_level = getWCAGLevel($contrast);
                        if ($wcag_level !== 'Fail') {
                            $combinations[] = [
                                'color' => $fg_color,
                                'contrast' => $contrast,
                                'level' => $wcag_level
                            ];
                        }
                    }

                    // Group by WCAG level
                    $wcag_groups = [
                        'AAA' => [],
                        'AA' => [],
                        'AA Large' => []
                    ];
                    
                    foreach ($combinations as $combo) {
                        $wcag_groups[$combo['level']][] = $combo;
                    }
                    
                    // Sort each group by contrast ratio
                    foreach ($wcag_groups as &$group) {
                        usort($group, function($a, $b) {
                            return $b['contrast'] <=> $a['contrast'];
                        });
                    }
                ?>
                <tr>
                    <td style="background-color: <?= $bg_color ?>; padding: 1em;">
                        <span style="color: <?= getLuminance($bg['rgb']) > 0.5 ? '#000000' : '#FFFFFF' ?>">
                            <?= htmlspecialchars($bg_color) ?><br>
                        </span>
                    </td>
                    <?php foreach (['AAA', 'AA', 'AA Large'] as $level): ?>
                        <td style="background-color: <?= $bg_color ?>;">
                            <?php foreach ($wcag_groups[$level] as $combo): ?>
                                <div style="color: <?= htmlspecialchars($combo['color']) ?>; padding: 0.5em;">
                                    <?= htmlspecialchars($combo['color']) ?> 
                                    (Ratio: <?= number_format($combo['contrast'], 3) ?>)
                                </div>
                            <?php endforeach; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Complete Contrast Grid</h2>
        <table class="checkered">
            <tr>
                <th>BG \ FG</th>
                <?php foreach ($parsed_colors as $color => $data): ?>
                    <th><?= htmlspecialchars($color) ?></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($parsed_colors as $bg_color => $bg_data): ?>
                <tr>
                    <th><?= htmlspecialchars($bg_color) ?></th>
                    <?php foreach ($parsed_colors as $fg_color => $fg_data): 
                        $contrast = getContrastRatio($bg_data['luminance'], $fg_data['luminance']);
                        $wcag_level = getWCAGLevel($contrast);
                    ?>
                        <td style="background-color: <?= $bg_color ?>;">
                            <div class="sample-text" style="color: <?= $fg_color ?>;">
                                Sample Text
                                <div style="font-size: 0.8em;">
                                    <?= number_format($contrast, 2) ?><br>
                                    <?= $wcag_level ?>
                                </div>
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>
