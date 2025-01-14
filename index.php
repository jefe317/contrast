<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('America/Chicago');

// TODO:
// colorblindness sim?
// black and white output, text only output
// try and break the code, improve testing data below
// help / instructions / documentation / explanation page, linked up top
// competitor analysis
// use supplied colors in code instead of rgba, make CSS classes? no benefit i can think of

// Ensure secure session handling
ini_set('session.cookie_httponly', 1);     // Prevent JavaScript access to session cookie
ini_set('session.cookie_secure', 1);       // Only transmit session cookie over HTTPS
ini_set('session.cookie_samesite', 'Strict'); // Prevent CSRF attacks
ini_set('session.use_strict_mode', 1);     // Only use session IDs generated by PHP
ini_set('session.use_only_cookies', 1);    // Don't allow session ID in URLs
ini_set('session.gc_maxlifetime', 3600);   // Session timeout after 1 hour

// Set secure headers
header("Content-Security-Policy: " . 
	"default-src 'self'; " .              // Only allow resources from same origin
	"style-src 'self' 'unsafe-inline'; ". // Allow inline styles (needed for color display)
	"img-src 'self' data: ; " .           // Allow data URIs for images
	"frame-ancestors 'none'; " .          // Prevent clickjacking
	"form-action 'self';"                 // Only allow forms to submit to same origin
);
header("X-Content-Type-Options: nosniff"); // Prevent MIME type sniffing
header("X-Frame-Options: DENY");           // Legacy clickjacking protection
header("X-XSS-Protection: 1; mode=block"); // Enable XSS filtering
header("Referrer-Policy: same-origin");    // Only send referrer to same origin
header("Permissions-Policy: geolocation=(), camera=(), microphone=()"); // Restrict browser features

// Set secure cookie parameters for all cookies
ini_set('session.cookie_domain', '');      // Current domain only
ini_set('session.cookie_path', '/');       // Root path
ini_set('session.cookie_lifetime', 0);     // Cookie expires when browser closes

// Optional: Force HTTPS (uncomment in production)
// if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
//     header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
//     exit;
// }

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

function blendColors($fg, $bg, $alpha) {
	// Alpha blend colors using the formula: result = fg * alpha + bg * (1 - alpha)
	return [
		($fg[0] * $alpha) + ($bg[0] * (1 - $alpha)),
		($fg[1] * $alpha) + ($bg[1] * (1 - $alpha)),
		($fg[2] * $alpha) + ($bg[2] * (1 - $alpha))
	];
}

function getLuminance($rgb, $alpha = 1, $bg = null) {
	// If we have alpha and background, blend first
	if ($alpha < 1 && $bg !== null) {
		$rgb = blendColors($rgb, $bg, $alpha);
	}
	
	// Convert to relative luminance
	$rgb = array_map(function($val) {
		$val = $val / 255;
		return $val <= 0.03928 ? $val / 12.92 : pow(($val + 0.055) / 1.055, 2.4);
	}, $rgb);
	
	return $rgb[0] * 0.2126 + $rgb[1] * 0.7152 + $rgb[2] * 0.0722;
}

function parseColor($color) {
	if (strlen($color) > 100) { // prevent long inputs that might mess up the code
		return false;
	}
	
	// Add CSS color name mapping
	$cssColors = [
		'aliceblue' => [240, 248, 255], 'antiquewhite' => [250, 235, 215], 'aqua' => [0, 255, 255], 'aquamarine' => [127, 255, 212], 'azure' => [240, 255, 255], 'beige' => [245, 245, 220], 'bisque' => [255, 228, 196], 'black' => [0, 0, 0], 'blanchedalmond' => [255, 235, 205], 'blue' => [0, 0, 255], 'blueviolet' => [138, 43, 226], 'brown' => [165, 42, 42], 'burlywood' => [222, 184, 135], 'cadetblue' => [95, 158, 160], 'chartreuse' => [127, 255, 0], 'chocolate' => [210, 105, 30], 'coral' => [255, 127, 80], 'cornflowerblue' => [100, 149, 237], 'cornsilk' => [255, 248, 220], 'crimson' => [220, 20, 60], 'cyan' => [0, 255, 255], 'darkblue' => [0, 0, 139], 'darkcyan' => [0, 139, 139], 'darkgoldenrod' => [184, 134, 11], 'darkgray' => [169, 169, 169], 'darkgreen' => [0, 100, 0], 'darkgrey' => [169, 169, 169], 'darkkhaki' => [189, 183, 107], 'darkmagenta' => [139, 0, 139], 'darkolivegreen' => [85, 107, 47], 'darkorange' => [255, 140, 0], 'darkorchid' => [153, 50, 204], 'darkred' => [139, 0, 0], 'darksalmon' => [233, 150, 122], 'darkseagreen' => [143, 188, 143], 'darkslateblue' => [72, 61, 139], 'darkslategray' => [47, 79, 79], 'darkslategrey' => [47, 79, 79], 'darkturquoise' => [0, 206, 209], 'darkviolet' => [148, 0, 211], 'deeppink' => [255, 20, 147], 'deepskyblue' => [0, 191, 255], 'dimgray' => [105, 105, 105], 'dimgrey' => [105, 105, 105], 'dodgerblue' => [30, 144, 255], 'firebrick' => [178, 34, 34], 'floralwhite' => [255, 250, 240], 'forestgreen' => [34, 139, 34], 'fuchsia' => [255, 0, 255], 'gainsboro' => [220, 220, 220], 'ghostwhite' => [248, 248, 255], 'gold' => [255, 215, 0], 'goldenrod' => [218, 165, 32], 'gray' => [128, 128, 128], 'grey' => [128, 128, 128], 'green' => [0, 128, 0], 'greenyellow' => [173, 255, 47], 'honeydew' => [240, 255, 240], 'hotpink' => [255, 105, 180], 'indianred' => [205, 92, 92], 'indigo' => [75, 0, 130], 'ivory' => [255, 255, 240], 'khaki' => [240, 230, 140], 'lavender' => [230, 230, 250], 'lavenderblush' => [255, 240, 245], 'lawngreen' => [124, 252, 0], 'lemonchiffon' => [255, 250, 205], 'lightblue' => [173, 216, 230], 'lightcoral' => [240, 128, 128], 'lightcyan' => [224, 255, 255], 'lightgoldenrodyellow' => [250, 250, 210], 'lightgray' => [211, 211, 211], 'lightgreen' => [144, 238, 144], 'lightgrey' => [211, 211, 211], 'lightpink' => [255, 182, 193], 'lightsalmon' => [255, 160, 122], 'lightseagreen' => [32, 178, 170], 'lightskyblue' => [135, 206, 250], 'lightslategray' => [119, 136, 153], 'lightslategrey' => [119, 136, 153], 'lightsteelblue' => [176, 196, 222], 'lightyellow' => [255, 255, 224], 'lime' => [0, 255, 0], 'limegreen' => [50, 205, 50], 'linen' => [250, 240, 230], 'magenta' => [255, 0, 255], 'maroon' => [128, 0, 0], 'mediumaquamarine' => [102, 205, 170], 'mediumblue' => [0, 0, 205], 'mediumorchid' => [186, 85, 211], 'mediumpurple' => [147, 112, 219], 'mediumseagreen' => [60, 179, 113], 'mediumslateblue' => [123, 104, 238], 'mediumspringgreen' => [0, 250, 154], 'mediumturquoise' => [72, 209, 204], 'mediumvioletred' => [199, 21, 133], 'midnightblue' => [25, 25, 112], 'mintcream' => [245, 255, 250], 'mistyrose' => [255, 228, 225], 'moccasin' => [255, 228, 181], 'navajowhite' => [255, 222, 173], 'navy' => [0, 0, 128], 'oldlace' => [253, 245, 230], 'olive' => [128, 128, 0], 'olivedrab' => [107, 142, 35], 'orange' => [255, 165, 0], 'orangered' => [255, 69, 0], 'orchid' => [218, 112, 214], 'palegoldenrod' => [238, 232, 170], 'palegreen' => [152, 251, 152], 'paleturquoise' => [175, 238, 238], 'palevioletred' => [219, 112, 147], 'papayawhip' => [255, 239, 213], 'peachpuff' => [255, 218, 185], 'peru' => [205, 133, 63], 'pink' => [255, 192, 203], 'plum' => [221, 160, 221], 'powderblue' => [176, 224, 230], 'purple' => [128, 0, 128], 'rebeccapurple' => [102, 51, 153], 'red' => [255, 0, 0], 'rosybrown' => [188, 143, 143], 'royalblue' => [65, 105, 225], 'saddlebrown' => [139, 69, 19], 'salmon' => [250, 128, 114], 'sandybrown' => [244, 164, 96], 'seagreen' => [46, 139, 87], 'seashell' => [255, 245, 238], 'sienna' => [160, 82, 45], 'silver' => [192, 192, 192], 'skyblue' => [135, 206, 235], 'slateblue' => [106, 90, 205], 'slategray' => [112, 128, 144], 'slategrey' => [112, 128, 144], 'snow' => [255, 250, 250], 'springgreen' => [0, 255, 127], 'steelblue' => [70, 130, 180], 'tan' => [210, 180, 140], 'teal' => [0, 128, 128], 'thistle' => [216, 191, 216], 'tomato' => [255, 99, 71], 'turquoise' => [64, 224, 208], 'violet' => [238, 130, 238], 'wheat' => [245, 222, 179], 'white' => [255, 255, 255], 'whitesmoke' => [245, 245, 245], 'yellow' => [255, 255, 0], 'yellowgreen' => [154, 205, 50]
	];

	// Extract everything after the last colon, handling both CSS properties and variables
	if (preg_match('/^.*:\s*(.+)$/', $color, $matches)) {
		$color = $matches[1];
	}
	
	// Strip any remaining CSS syntax or semicolons
	$color = preg_replace('/;.*/', '', $color);    // Remove semicolon and anything after
	$color = preg_replace('/}.*/', '', $color);    // Remove closing brace and anything after
	$color = strtolower(trim($color));             // Normalize to lowercase
	
	// Check if it's a named CSS color
	if (isset($cssColors[$color])) {
		return array_merge($cssColors[$color], [1]); // Add alpha = 1 for named colors
	}

	// Validate hex format length before processing
    if (preg_match('/^#[A-Fa-f0-9]+$/', $color) && strlen($color) != 4 && strlen($color) != 5 && strlen($color) != 7 && strlen($color) != 9) {
        return false;
    }

    // Process valid hex colors
    if (preg_match('/^#([A-Fa-f0-9]{3,8})$/', $color, $matches)) {
        $hex = ltrim($color, '#');
        $length = strlen($hex);
        
        switch($length) {
            case 3: // #RGB
                $r = hexdec($hex[0].$hex[0]);
                $g = hexdec($hex[1].$hex[1]);
                $b = hexdec($hex[2].$hex[2]);
                return [$r, $g, $b, 1];
            case 4: // #RGBA
                $r = hexdec($hex[0].$hex[0]);
                $g = hexdec($hex[1].$hex[1]);
                $b = hexdec($hex[2].$hex[2]);
                $a = hexdec($hex[3].$hex[3]) / 255;
                return [$r, $g, $b, $a];
            case 6: // #RRGGBB
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                return [$r, $g, $b, 1];
            case 8: // #RRGGBBAA
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                $a = hexdec(substr($hex, 6, 2)) / 255;
                return [$r, $g, $b, $a];
        }
    }
	
	if (preg_match('/^cmyk\((\d+)%?,\s*(\d+)%?,\s*(\d+)%?,\s*(\d+)%?\)$/', $color, $matches)) {
		return cmykToRgb(
			floatval($matches[1]),
			floatval($matches[2]),
			floatval($matches[3]),
			floatval($matches[4])
		);
	} elseif (preg_match('/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/', $color, $matches)) {
		return [
			intval($matches[1]),
			intval($matches[2]),
			intval($matches[3])
		];
	} elseif (preg_match('/^rgba?\(\s*(\d+)(?:\s+|\s*,\s*)(\d+)(?:\s+|\s*,\s*)(\d+)(?:\s*(?:\/|\s*,)\s*([\d.]+))?\s*\)$/', $color, $matches)) {
		return [
			intval($matches[1]),
			intval($matches[2]),
			intval($matches[3]),
			isset($matches[4]) ? floatval($matches[4]) : 1
		];
	} elseif (preg_match('/^hsl\((\d+),\s*(\d+)%,\s*(\d+)%\)$/', $color, $matches)) {
		return hslToRgb($matches[1], $matches[2], $matches[3]);
	} elseif (preg_match('/^hsla?\(\s*(\d+)(?:\s+|\s*,\s*)(\d+)%(?:\s+|\s*,\s*)(\d+)%(?:\s*(?:\/|\s*,)\s*([\d.]+))?\s*\)$/', $color, $matches)) {
		$rgb = hslToRgb($matches[1], $matches[2], $matches[3]);
		return array_merge($rgb, [isset($matches[4]) ? floatval($matches[4]) : 1]);
	} elseif (preg_match('/^lab\((\d+\.?\d*),\s*(-?\d+\.?\d*),\s*(-?\d+\.?\d*)\)$/', $color, $matches)) {
		return labToRgb(
			floatval($matches[1]),
			floatval($matches[2]),
			floatval($matches[3])
		);
	} elseif (preg_match('/^hsb\((\d+),\s*(\d+)%?,\s*(\d+)%?\)$/', $color, $matches)) {
		return hsbToRgb(
			floatval($matches[1]),
			floatval($matches[2]),
			floatval($matches[3])
		);
	} elseif (preg_match('/^hwb\(\s*(\d+)\s+(\d+)%\s+(\d+)%(?:\s*\/\s*([\d.]+))?\s*\)$/', $color, $matches)) {
		$rgb = hwbToRgb(
			floatval($matches[1]), 
			floatval($matches[2]), 
			floatval($matches[3])
		);
		return array_merge($rgb, [isset($matches[4]) ? floatval($matches[4]) : 1]);
	}
	return false;
}

function getCleanColorName($color) {
	// Remove CSS property names (case insensitive)
	$color = preg_replace('/^(color|background-color|border-color):\s*/i', '', $color);    
	// Remove any trailing semicolons or braces
	$color = preg_replace('/[;{}].*$/', '', $color);
	// Trim any whitespace
	return trim($color);
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

function hwbToRgb($h, $w, $b) {
	// HWB to RGB conversion
	// H = 0-360, W = 0-100, B = 0-100
	
	// Make sure hue is between 0 and 360
	$h = $h % 360;
	if ($h < 0) {
		$h += 360;
	}
	
	// Convert whiteness and blackness to 0-1 scale
	$w = min(100, max(0, $w)) / 100;
	$b = min(100, max(0, $b)) / 100;
	
	// If whiteness + blackness >= 1, it's a grey
	if ($w + $b >= 1) {
		$gray = $w / ($w + $b) * 255;
		return [round($gray), round($gray), round($gray)];
	}
	
	// Convert base hue to RGB first (with full saturation)
	$h = $h / 360;
	
	$i = floor($h * 6);
	$f = $h * 6 - $i;
	$p = 1 * (1 - $f);
	$q = 1 * $f;
	
	switch ($i % 6) {
		case 0: $r = 1; $g = $q; $b_val = 0; break;
		case 1: $r = $p; $g = 1; $b_val = 0; break;
		case 2: $r = 0; $g = 1; $b_val = $q; break;
		case 3: $r = 0; $g = $p; $b_val = 1; break;
		case 4: $r = $q; $g = 0; $b_val = 1; break;
		case 5: $r = 1; $g = 0; $b_val = $p; break;
	}
	
	// Mix with white and black
	$factor = (1 - $w - $b);
	$r = $r * $factor + $w;
	$g = $g * $factor + $w;
	$b_val = $b_val * $factor + $w;
	
	// Scale to 0-255 range
	return [
		round($r * 255),
		round($g * 255),
		round($b_val * 255)
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

function getContrastRatio($l1, $l2) {
	// https://www.w3.org/TR/2008/REC-WCAG20-20081211/#contrast-ratiodef
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

function getCssColor($color) {
	$rgba = parseColor($color);
	if ($rgba === false) {
		return '#000000'; // fallback color
	}
	
	// If alpha is 1 or not specified, use rgb() format
	if (!isset($rgba[3]) || $rgba[3] >= 0.999) {
		return sprintf('rgb(%d, %d, %d)', $rgba[0], $rgba[1], $rgba[2]);
	}
	
	// Otherwise use rgba() format
	return sprintf('rgba(%d, %d, %d, %.3f)', $rgba[0], $rgba[1], $rgba[2], $rgba[3]);
}

function cmykToRgb($c, $m, $y, $k) {
	// CMYK values are in percentages (0-100)
	$c = $c / 100;
	$m = $m / 100;
	$y = $y / 100;
	$k = $k / 100;
	
	$r = 255 * (1 - $c) * (1 - $k);
	$g = 255 * (1 - $m) * (1 - $k);
	$b = 255 * (1 - $y) * (1 - $k);
	
	return [round($r), round($g), round($b)];
}

function labToRgb($l, $a, $b) {
	// First convert Lab to XYZ
	$y = ($l + 16) / 116;
	$x = $a / 500 + $y;
	$z = $y - $b / 200;
	
	// Helper function for Lab to XYZ conversion
	$lab2xyz = function($v) {
		$v3 = $v * $v * $v;
		return $v3 > 0.008856 ? $v3 : ($v - 16/116) / 7.787;
	};
	
	$x = 0.95047 * $lab2xyz($x);
	$y = 1.00000 * $lab2xyz($y);
	$z = 1.08883 * $lab2xyz($z);
	
	// Then convert XYZ to RGB
	$r = $x *  3.2406 + $y * -1.5372 + $z * -0.4986;
	$g = $x * -0.9689 + $y *  1.8758 + $z *  0.0415;
	$b = $x *  0.0557 + $y * -0.2040 + $z *  1.0570;
	
	// Clip and convert to 8-bit values
	$clip = function($v) {
		$v = $v > 0.0031308 ? 1.055 * pow($v, 1/2.4) - 0.055 : 12.92 * $v;
		return round(max(0, min(1, $v)) * 255);
	};
	
	return [$clip($r), $clip($g), $clip($b)];
}

function hsbToRgb($h, $s, $v) {
	// HSB/HSV values: H = 0-360, S = 0-100, V = 0-100
	$s = $s / 100;
	$v = $v / 100;
	$h = $h / 60;
	
	$i = floor($h);
	$f = $h - $i;
	$p = $v * (1 - $s);
	$q = $v * (1 - $s * $f);
	$t = $v * (1 - $s * (1 - $f));
	
	switch ($i) {
		case 0:
			$r = $v; $g = $t; $b = $p;
			break;
		case 1:
			$r = $q; $g = $v; $b = $p;
			break;
		case 2:
			$r = $p; $g = $v; $b = $t;
			break;
		case 3:
			$r = $p; $g = $q; $b = $v;
			break;
		case 4:
			$r = $t; $g = $p; $b = $v;
			break;
		default:
			$r = $v; $g = $p; $b = $q;
			break;
	}
	
	return [
		round($r * 255),
		round($g * 255),
		round($b * 255)
	];
}

// Process download request
if (isset($_POST['download']) && !empty($_POST['colors'])) {
	// Re-process the colors since we're in a new request
	$colors = array_map('trim', explode("\n", $_POST['colors']));
	$colors = array_filter($colors, 'strlen');
	$colors = array_slice($colors, 0, 20);
	
	if (count($colors) === 1) {
		$test_color = parseColor($colors[0]);
		if ($test_color !== false) {
			array_push($colors, 'black', 'white');
		}
	}
	
	$parsed_colors = [];
	foreach ($colors as $original_color) {
		$rgb = parseColor($original_color);
		if ($rgb !== false) {
			$parsed_colors[$original_color] = [
				'rgb' => array_slice($rgb, 0, 3),
				'alpha' => isset($rgb[3]) ? $rgb[3] : 1,
				'luminance' => getLuminance(array_slice($rgb, 0, 3))
			];
		}
	}
	
	$timestamp = date('Ymd-His');
	$filename = "color-contrast-report-{$timestamp}.html";
	
	header('Content-Type: text/html');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	
	// Generate report HTML
	ob_start();
	?>
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Color Contrast Report - <?= date('Y-m-d H:i:s') ?></title>
		<style>
			body { font-family: Arial, sans-serif; margin: 20px; }
			table { border-collapse: collapse; margin: 20px 0; }
			th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; }
			td div { padding: 0 0.5em 0.5em 0; }
			.checkered { background: conic-gradient(hsla(0, 0%, 50%, 20%) 90deg, transparent 90deg 180deg, hsla(0, 0%, 50%, 20%) 180deg 270deg, transparent 270deg); background-repeat: repeat; background-size: 40px 40px; }
		</style>
	</head>
	<body>
		<h1>Color Contrast Report</h1>
		<p>Generated on: <?= date('Y-m-d H:i:s T') ?></p>
		
		<?php if (!empty($parsed_colors)): ?>
			<h2>Summary of Compatible Color Combinations</h2>
			<table class="checkered">
				<thead>
					<tr>
						<th>Background</th>
						<th>AAA ≥ 7.0<br>Best</th>
						<th>AA Normal ≥ 4.5<br>Second Best </th>
						<th>AA Large ≥ 3.0<br>Third Best</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($parsed_colors as $bg_color => $bg): 
						$combinations = [];
						foreach ($parsed_colors as $fg_color => $fg) {
							if ($fg_color === $bg_color) continue;
							
							$fg_lum = getLuminance($fg['rgb'], $fg['alpha'], $bg['rgb']);
							$bg_lum = $bg['luminance'];
							$contrast = getContrastRatio($bg_lum, $fg_lum);
							
							$wcag_level = getWCAGLevel($contrast);
							if ($wcag_level !== 'Fail') {
								$combinations[] = [
									'color' => $fg_color,
									'contrast' => $contrast,
									'level' => $wcag_level
								];
							}
						}

						$wcag_groups = [
							'AAA' => [],
							'AA' => [],
							'AA Large' => []
						];
						
						foreach ($combinations as $combo) {
							$wcag_groups[$combo['level']][] = $combo;
						}
						
						foreach ($wcag_groups as &$group) {
							usort($group, function($a, $b) {
								return $b['contrast'] <=> $a['contrast'];
							});
						}

						$has_valid_combinations = !empty($wcag_groups['AAA']) || 
											   !empty($wcag_groups['AA']) || 
											   !empty($wcag_groups['AA Large']);
					?>
					<tr style="background-color: <?= htmlspecialchars(getCssColor($bg_color)) ?>;">
						<td>
							<?php 
							$bg_text_lum = getLuminance($bg['rgb'], $bg['alpha']);
							?>
							<span style="color: <?= $bg_text_lum > 0.5 ? '#000000' : '#FFFFFF' ?>">
								<?= htmlspecialchars(getCleanColorName($bg_color)) ?><br>
							</span>
						</td>
						<?php if ($has_valid_combinations): ?>
							<?php foreach (['AAA', 'AA', 'AA Large'] as $level): ?>
								<td>
									<?php foreach ($wcag_groups[$level] as $combo): ?>
										<div style="color: <?= htmlspecialchars(getCssColor($combo['color'])) ?>;">
											<?= htmlspecialchars($combo['color']) ?> 
											(Ratio: <?= number_format($combo['contrast'], 2) ?>)
										</div>
									<?php endforeach; ?>
								</td>
							<?php endforeach; ?>
						<?php else: ?>
							<td colspan="3" style="text-align: center; color: <?= $bg_text_lum > 0.5 ? '#000000' : '#FFFFFF' ?>">
								No valid color combinations found (all contrast ratios below 3.0)
							</td>
						<?php endif; ?>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2>Complete Contrast Grid</h2>
			<table class="checkered">
				<tr>
					<th>BG \ FG</th>
					<?php foreach ($parsed_colors as $color => $data): ?>
						<th><?= htmlspecialchars(getCleanColorName($color)) ?></th>
					<?php endforeach; ?>
				</tr>
				<?php foreach ($parsed_colors as $bg_color => $bg_data): ?>
					<tr>
						<th><?= htmlspecialchars(getCleanColorName($bg_color)) ?></th>
						<?php foreach ($parsed_colors as $fg_color => $fg_data): 
							$fg_lum = getLuminance($fg_data['rgb'], $fg_data['alpha'], $bg_data['rgb']);
							$bg_lum = $bg_data['luminance'];
							$contrast = getContrastRatio($bg_lum, $fg_lum);
							$wcag_level = getWCAGLevel($contrast);
						?>
							<td style="background-color: <?= htmlspecialchars(getCssColor($bg_color)) ?>;">
								<div class="sample-text" style="color: <?= htmlspecialchars(getCssColor($fg_color)) ?>;">
									Sample
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
	<?php
	$html = ob_get_clean();
	echo $html;
	exit;
}

// Process form submission
$parsed_colors = [];
$invalid_colors = [];
$summary = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['colors'])) {
	if (strlen($_POST['colors']) > 1000) { // Limit total input size
		die("Input too large");
	}
	
	// Split on both newlines and commas, then clean up each entry
	$cleaned_input = preg_split('/(?<=;|\n)/', $_POST['colors']);
	$cleaned_input = array_map('trim', $cleaned_input);
	$cleaned_input = array_filter($cleaned_input, 'strlen');  // Remove empty lines
	$_POST['colors'] = implode("\n", $cleaned_input);  // Put it back together with newlines
	
	$colors = array_map(function($color) {
		return substr($color, 0, 50); // Additional length check
	}, $cleaned_input);
	$colors = array_slice($colors, 0, 20); // Limit to 20 colors
	
	// If only one valid color is entered, automatically add black and white
	if (count($colors) === 1) {
		$test_color = parseColor($colors[0]);
		if ($test_color !== false) {
			array_push($colors, 'black', 'white');
		}
	}
	
	foreach ($colors as $original_color) {
		$rgb = parseColor($original_color);
		if ($rgb !== false) {
			$parsed_colors[$original_color] = [
				'rgb' => array_slice($rgb, 0, 3), // First 3 elements for RGB
				'alpha' => isset($rgb[3]) ? $rgb[3] : 1, // Get alpha if it exists
				'luminance' => getLuminance(array_slice($rgb, 0, 3))
			];
		} else {
			// Store the invalid color and the line number it appeared on
			$invalid_colors[] = $original_color;
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
		:root { color-scheme: light dark; }
		body { font-family: Arial, sans-serif; margin: 20px; }
		table { border-collapse: collapse; margin: 20px 0; }
		th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; }
		td div { padding: 0 0.5em 0.5em 0; }
		textarea { width: 100%; max-width: 400px; height: 200px; }
		.checkered {background: conic-gradient(hsla(0, 0%, 50%, 20%) 90deg, transparent 90deg 180deg, hsla(0, 0%, 50%, 20%) 180deg 270deg, transparent 270deg); background-repeat: repeat; background-size: 40px 40px; }
		.error-message { background-color: hsla(0 100% 63% / 0.5); border: 1px solid #f5c6cb; border-radius: 4px; padding: 12px; margin: 20px 0; width: fit-content;}
		.error-message h3 { margin-top: 0; }
	</style>
</head>
<body>
	<h1>WCAG Color Contrast Analyzer</h1>
	<form method="post">
		<p>Enter up to 20 colors (one per line) in any of these formats:</p>
		<ul>
			<li><strong>Hex</strong>: #FFF, #FFFFFF, #FFFF (with alpha), #FFFFFFFF (with alpha)</li>
			<li><strong>RGB and RGBA</strong>: rgb(255, 255, 255) rgba(255, 255, 255, 0.5)</li>
			<li><strong>HSL and HSLA</strong>: hsl(360, 100%, 100%) hsla(360, 100%, 100%, 0.5)</li>
			<li>CSS <strong>Named colors</strong>, like "black", "white", or "coral"</li>
			<li><strong>CMYK</strong>: cmyk(100%, 0%, 0%, 0%) or cmyk(0, 100, 100, 0)</li>
			<li><strong>Lab</strong>: lab(75.5, 20.3, -15.6)</li>
				<li><strong>HSB and HSV</strong>: hsb(240, 100%, 100%) or hsb(120, 50, 75)</li>
			<li>CSS syntax is also accepted (e.g., "color: #FFF;" or "background-color: rgb(255, 0, 0);")</li>
		</ul>
		<textarea name="colors" required><?= isset($_POST['colors']) ? htmlspecialchars($_POST['colors']) : '' ?></textarea>
		<br><br>
		<button type="submit">Calculate Contrast</button>
	</form>
	
	<?php if (!empty($parsed_colors)): ?>
		<div style="margin-top: 1em;"><form method="post">
			<input type="hidden" name="colors" value="<?= htmlspecialchars($_POST['colors']) ?>">
			<button type="submit" name="download" value="1">Download Report</button>
		</form></div>
	<?php endif; ?>

	<?php if (!empty($invalid_colors)): ?>
		<div class="error-message">
			<h3>Invalid Colors Detected</h3>
			<p>The following colors could not be parsed:</p>
			<ul class="error-list">
				<?php foreach ($invalid_colors as $color): ?>
					<li><code><?= htmlspecialchars($color) ?></code></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if (!empty($parsed_colors)): ?>
		<h2>Summary of Compatible Color Combinations</h2>
		<table class="checkered">
			<thead>
				<tr>
					<th>Background</th>
					<th>AAA ≥ 7.0<br>Best</th>
					<th>AA Normal ≥ 4.5<br>Second Best </th>
					<th>AA Large ≥ 3.0<br>Third Best</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($parsed_colors as $bg_color => $bg): 
					// Calculate all combinations for this background color
					$combinations = [];
					foreach ($parsed_colors as $fg_color => $fg) {
						if ($fg_color === $bg_color) continue;
						
						// Calculate luminance with alpha blending
						$fg_lum = getLuminance($fg['rgb'], $fg['alpha'], $bg['rgb']);
						$bg_lum = $bg['luminance'];
						$contrast = getContrastRatio($bg_lum, $fg_lum);
						
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

					// Check if there are any valid combinations
					$has_valid_combinations = !empty($wcag_groups['AAA']) || 
										   !empty($wcag_groups['AA']) || 
										   !empty($wcag_groups['AA Large']);
				?>
				<tr style="background-color: <?= htmlspecialchars(getCssColor($bg_color)) ?>;">
					<td>
						<?php 
						// Use alpha-aware luminance for text color calculation
						$bg_text_lum = getLuminance($bg['rgb'], $bg['alpha']);
						?>
						<span style="color: <?= $bg_text_lum > 0.5 ? '#000000' : '#FFFFFF' ?>">
							<?= htmlspecialchars(getCleanColorName($bg_color)) ?><br>
						</span>
					</td>
					<?php if ($has_valid_combinations): ?>
						<?php foreach (['AAA', 'AA', 'AA Large'] as $level): ?>
							<td>
								<?php foreach ($wcag_groups[$level] as $combo): ?>
									<div style="color: <?= htmlspecialchars(getCssColor($combo['color'])) ?>;">
										<?= htmlspecialchars($combo['color']) ?> 
										(Ratio: <?= number_format($combo['contrast'], 2) ?>)
									</div>
								<?php endforeach; ?>
							</td>
						<?php endforeach; ?>
					<?php else: ?>
						<td colspan="3" style="text-align: center; color: <?= $bg_text_lum > 0.5 ? '#000000' : '#FFFFFF' ?>">
							No valid color combinations found (all contrast ratios below 3.0)
						</td>
					<?php endif; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2>Complete Contrast Grid</h2>
		<table class="checkered">
	<tr>
		<th>BG \ FG</th>
		<?php foreach ($parsed_colors as $color => $data): ?>
			<th><?= htmlspecialchars(getCleanColorName($color)) ?></th>
		<?php endforeach; ?>
		</tr>
		<?php foreach ($parsed_colors as $bg_color => $bg_data): ?>
			<tr>
				<th><?= htmlspecialchars(getCleanColorName($bg_color)) ?></th>
				<?php foreach ($parsed_colors as $fg_color => $fg_data): 
					// Calculate luminance with alpha blending
					$fg_lum = getLuminance($fg_data['rgb'], $fg_data['alpha'], $bg_data['rgb']);
					$bg_lum = $bg_data['luminance'];
					$contrast = getContrastRatio($bg_lum, $fg_lum);
					$wcag_level = getWCAGLevel($contrast);
				?>
					<td style="background-color: <?= htmlspecialchars(getCssColor($bg_color)) ?>;">
						<div class="sample-text" style="color: <?= htmlspecialchars(getCssColor($fg_color)) ?>;">
							Sample
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
