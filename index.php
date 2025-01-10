<?php
ini_set('display_errors', 1);
function hslToRgb($hsl)
{
    // Parse H, S, L values
    $hsl = array_map('trim', explode(',', $hsl));
    $h = (float) rtrim($hsl[0], '%') / 360; // Normalize to 0-1
    $s = (float) rtrim($hsl[1], '%') / 100; // Normalize to 0-1
    $l = (float) rtrim($hsl[2], '%') / 100; // Normalize to 0-1

    $convert = function ($p, $q, $t) {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1 / 2) return $q;
        if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;
        return $p;
    };

    if ($s === 0) {
        $r = $g = $b = $l; // Achromatic
    } else {
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        $r = $convert($p, $q, $h + 1 / 3);
        $g = $convert($p, $q, $h);
        $b = $convert($p, $q, $h - 1 / 3);
    }

    return [round($r * 255), round($g * 255), round($b * 255)];
}

function hexToRgb($hex)
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];
}

function contrastRatio($rgb1, $rgb2)
{
    $luminance = function ($rgb) {
        foreach ($rgb as &$value) {
            $value /= 255;
            $value = $value <= 0.03928 ? $value / 12.92 : pow(($value + 0.055) / 1.055, 2.4);
        }
        return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
    };

    $lum1 = $luminance($rgb1);
    $lum2 = $luminance($rgb2);

    return ($lum1 > $lum2 ? ($lum1 + 0.05) / ($lum2 + 0.05) : ($lum2 + 0.05) / ($lum1 + 0.05));
}

function parseColor($color)
{
    if (preg_match('/^#/', $color)) {
        return hexToRgb($color);
    } elseif (preg_match('/^rgba?\((.+?)\)$/i', $color, $matches)) {
        return array_map('intval', explode(',', $matches[1]));
    } elseif (preg_match('/^hsla?\((.+?)\)$/i', $color, $matches)) {
        return hslToRgb(explode(',', $matches[1]));
    }
    return null;
}

function generateSummaryTable($colors)
{
    $summary = "<table border='1'><tr><th>Background</th><th>Foregrounds (Pass)</th></tr>";
    foreach ($colors as $bg => $bgRgb) {
        $passing = [];
        foreach ($colors as $fg => $fgRgb) {
            if ($bg === $fg) continue;
            $contrast = contrastRatio($bgRgb, $fgRgb);
            if ($contrast >= 4.5) {
                $passing[$fg] = $contrast;
            }
        }
        arsort($passing);
        $summary .= "<tr><td style='background-color: $bg; color: black;'>$bg</td><td>" . implode(', ', array_keys($passing)) . "</td></tr>";
    }
    $summary .= "</table>";
    return $summary;
}

function generateContrastGrid($colors)
{
    $grid = "<table border='1'>";
    $grid .= "<tr><th>\</th>";
    foreach ($colors as $fg => $_) {
        $grid .= "<th>$fg</th>";
    }
    $grid .= "</tr>";

    foreach ($colors as $bg => $bgRgb) {
        $grid .= "<tr><td>$bg</td>";
        foreach ($colors as $fg => $fgRgb) {
            $contrast = contrastRatio($bgRgb, $fgRgb);
            $wcag = $contrast >= 7 ? 'AAA' : ($contrast >= 4.5 ? 'AA' : ($contrast >= 3 ? 'UI' : 'Fail'));
            $grid .= "<td style='background-color: $bg; color: $fg;' title='Contrast: $contrast (WCAG: $wcag)'>$wcag</td>";
        }
        $grid .= "</tr>";
    }

    $grid .= "</table>";
    return $grid;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputColors = preg_split("/\r\n|\r|\n/", trim($_POST['colors']));
    $parsedColors = [];

    foreach ($inputColors as $color) {
        $parsed = parseColor($color);
        if ($parsed) {
            $parsedColors[$color] = $parsed;
        }
    }

    $summaryTable = generateSummaryTable($parsedColors);
    $contrastGrid = generateContrastGrid($parsedColors);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WCAG Contrast Checker</title>
</head>
<body>
    <h1>WCAG Contrast Checker</h1>
    <form method="post">
        <textarea name="colors" rows="10" cols="30" placeholder="Enter up to 20 colors (one per line)"></textarea><br>
        <button type="submit">Calculate Contrast</button>
    </form>

    <?php if (!empty($summaryTable) && !empty($contrastGrid)): ?>
        <h2>Summary Table</h2>
        <?= $summaryTable ?>

        <h2>Contrast Grid</h2>
        <?= $contrastGrid ?>
    <?php endif; ?>
</body>
</html>
