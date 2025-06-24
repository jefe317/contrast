<?php
// parse_colors.php
require_once 'functions.php';

function parseColor($color) {
	if (strlen($color) > 100) { // prevent long inputs that might mess up the code
		return false;
	}
	
	// Add CSS color name mapping
	$cssColors = [
		'aliceblue' => [240, 248, 255], 'antiquewhite' => [250, 235, 215], 'aqua' => [0, 255, 255], 'aquamarine' => [127, 255, 212], 'azure' => [240, 255, 255], 'beige' => [245, 245, 220], 'bisque' => [255, 228, 196], 'black' => [0, 0, 0], 'blanchedalmond' => [255, 235, 205], 'blue' => [0, 0, 255], 'blueviolet' => [138, 43, 226], 'brown' => [165, 42, 42], 'burlywood' => [222, 184, 135], 'cadetblue' => [95, 158, 160], 'chartreuse' => [127, 255, 0], 'chocolate' => [210, 105, 30], 'coral' => [255, 127, 80], 'cornflowerblue' => [100, 149, 237], 'cornsilk' => [255, 248, 220], 'crimson' => [220, 20, 60], 'cyan' => [0, 255, 255], 'darkblue' => [0, 0, 139], 'darkcyan' => [0, 139, 139], 'darkgoldenrod' => [184, 134, 11], 'darkgray' => [169, 169, 169], 'darkgreen' => [0, 100, 0], 'darkgrey' => [169, 169, 169], 'darkkhaki' => [189, 183, 107], 'darkmagenta' => [139, 0, 139], 'darkolivegreen' => [85, 107, 47], 'darkorange' => [255, 140, 0], 'darkorchid' => [153, 50, 204], 'darkred' => [139, 0, 0], 'darksalmon' => [233, 150, 122], 'darkseagreen' => [143, 188, 143], 'darkslateblue' => [72, 61, 139], 'darkslategray' => [47, 79, 79], 'darkslategrey' => [47, 79, 79], 'darkturquoise' => [0, 206, 209], 'darkviolet' => [148, 0, 211], 'deeppink' => [255, 20, 147], 'deepskyblue' => [0, 191, 255], 'dimgray' => [105, 105, 105], 'dimgrey' => [105, 105, 105], 'dodgerblue' => [30, 144, 255], 'firebrick' => [178, 34, 34], 'floralwhite' => [255, 250, 240], 'forestgreen' => [34, 139, 34], 'fuchsia' => [255, 0, 255], 'gainsboro' => [220, 220, 220], 'ghostwhite' => [248, 248, 255], 'gold' => [255, 215, 0], 'goldenrod' => [218, 165, 32], 'gray' => [128, 128, 128], 'grey' => [128, 128, 128], 'green' => [0, 128, 0], 'greenyellow' => [173, 255, 47], 'honeydew' => [240, 255, 240], 'hotpink' => [255, 105, 180], 'indianred' => [205, 92, 92], 'indigo' => [75, 0, 130], 'ivory' => [255, 255, 240], 'khaki' => [240, 230, 140], 'lavender' => [230, 230, 250], 'lavenderblush' => [255, 240, 245], 'lawngreen' => [124, 252, 0], 'lemonchiffon' => [255, 250, 205], 'lightblue' => [173, 216, 230], 'lightcoral' => [240, 128, 128], 'lightcyan' => [224, 255, 255], 'lightgoldenrodyellow' => [250, 250, 210], 'lightgray' => [211, 211, 211], 'lightgreen' => [144, 238, 144], 'lightgrey' => [211, 211, 211], 'lightpink' => [255, 182, 193], 'lightsalmon' => [255, 160, 122], 'lightseagreen' => [32, 178, 170], 'lightskyblue' => [135, 206, 250], 'lightslategray' => [119, 136, 153], 'lightslategrey' => [119, 136, 153], 'lightsteelblue' => [176, 196, 222], 'lightyellow' => [255, 255, 224], 'lime' => [0, 255, 0], 'limegreen' => [50, 205, 50], 'linen' => [250, 240, 230], 'magenta' => [255, 0, 255], 'maroon' => [128, 0, 0], 'mediumaquamarine' => [102, 205, 170], 'mediumblue' => [0, 0, 205], 'mediumorchid' => [186, 85, 211], 'mediumpurple' => [147, 112, 219], 'mediumseagreen' => [60, 179, 113], 'mediumslateblue' => [123, 104, 238], 'mediumspringgreen' => [0, 250, 154], 'mediumturquoise' => [72, 209, 204], 'mediumvioletred' => [199, 21, 133], 'midnightblue' => [25, 25, 112], 'mintcream' => [245, 255, 250], 'mistyrose' => [255, 228, 225], 'moccasin' => [255, 228, 181], 'navajowhite' => [255, 222, 173], 'navy' => [0, 0, 128], 'oldlace' => [253, 245, 230], 'olive' => [128, 128, 0], 'olivedrab' => [107, 142, 35], 'orange' => [255, 165, 0], 'orangered' => [255, 69, 0], 'orchid' => [218, 112, 214], 'palegoldenrod' => [238, 232, 170], 'palegreen' => [152, 251, 152], 'paleturquoise' => [175, 238, 238], 'palevioletred' => [219, 112, 147], 'papayawhip' => [255, 239, 213], 'peachpuff' => [255, 218, 185], 'peru' => [205, 133, 63], 'pink' => [255, 192, 203], 'plum' => [221, 160, 221], 'powderblue' => [176, 224, 230], 'purple' => [128, 0, 128], 'rebeccapurple' => [102, 51, 153], 'red' => [255, 0, 0], 'rosybrown' => [188, 143, 143], 'royalblue' => [65, 105, 225], 'saddlebrown' => [139, 69, 19], 'salmon' => [250, 128, 114], 'sandybrown' => [244, 164, 96], 'seagreen' => [46, 139, 87], 'seashell' => [255, 245, 238], 'sienna' => [160, 82, 45], 'silver' => [192, 192, 192], 'skyblue' => [135, 206, 235], 'slateblue' => [106, 90, 205], 'slategray' => [112, 128, 144], 'slategrey' => [112, 128, 144], 'snow' => [255, 250, 250], 'springgreen' => [0, 255, 127], 'steelblue' => [70, 130, 180], 'tan' => [210, 180, 140], 'teal' => [0, 128, 128], 'thistle' => [216, 191, 216], 'tomato' => [255, 99, 71], 'turquoise' => [64, 224, 208], 'violet' => [238, 130, 238], 'wheat' => [245, 222, 179], 'white' => [255, 255, 255], 'whitesmoke' => [245, 245, 245], 'yellow' => [255, 255, 0], 'yellowgreen' => [154, 205, 50]
	];

	// Remove multiline comments
	$color = preg_replace('/\/\*.*?\*\//s', '', $color);
	// Remove inline comments until newline or semicolon
	$color = preg_replace('/\/\/[^;\n]*[;\n]/', '', $color);
	// Strip // comments
	$color = preg_replace('|//.*$|m', '', $color);
	
	// Extract everything after the last colon, handling both CSS properties and variables
	if (preg_match('/^.*:\s*(.+)$/', $color, $matches)) {
		$color = $matches[1];
	}
	
	// Strip any remaining CSS syntax or semicolons
	$color = preg_replace('/;+.*/', '', $color);   // Remove multiple semicolons and anything after
	$color = preg_replace('/}.*/', '', $color);    // Remove closing brace and anything after
	$color = strtolower(trim($color));             // Normalize to lowercase
	
	// Check if it's a named CSS color
	if (isset($cssColors[$color])) {
		return array_merge($cssColors[$color], [1]); // Add alpha = 1 for named colors
	}

	// Validate hex format length before processing
	if (preg_match('/^#?[A-Fa-f0-9]+$/', $color) && strlen(ltrim($color, '#')) != 3 && strlen(ltrim($color, '#')) != 4 && strlen(ltrim($color, '#')) != 6 && strlen(ltrim($color, '#')) != 8) {
		return false;
	}

	// Process valid hex colors
	if (preg_match('/^#?([A-Fa-f0-9]{3,8})$/', $color, $matches)) {
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
	} elseif (preg_match('/^rgba?\(\s*([\d.]+)(?:\s+|\s*,\s*)([\d.]+)(?:\s+|\s*,\s*)([\d.]+)(?:\s*(?:\/|\s*,)\s*([\d.]+%?))?\s*\)$/', $color, $matches)) {
		$alpha = isset($matches[4]) ? (str_ends_with($matches[4], '%') ? 
			floatval(rtrim($matches[4], '%')) / 100 : 
			floatval($matches[4])) : 1;
		$rgb = [
			intval(floatval($matches[1])),
			intval(floatval($matches[2])),
			intval(floatval($matches[3]))
		];
		return array_merge($rgb, [$alpha]);
	} elseif (preg_match('/^hsla?\(\s*([\d.]+)(?:deg|turn)?(?:(?:\s+|\s*,\s*)|\s+)(\d+\.?\d*)%(?:\s+|\s*,\s*)(\d+\.?\d*)%(?:\s*(?:\/|\s*,)\s*([\d.]+%?))?\s*\)$/', $color, $matches)) {
		$alpha = isset($matches[4]) ? (str_ends_with($matches[4], '%') ? 
			floatval(rtrim($matches[4], '%')) / 100 : 
			floatval($matches[4])) : 1;
		$rgb = hslToRgb($matches[1], floatval($matches[2]), floatval($matches[3]));
		return array_merge($rgb, [$alpha]);
	} elseif (preg_match('/^lab\(\s*(\d+\.?\d*)%?\s+(-?\d+\.?\d*)\s+(-?\d+\.?\d*)(?:\s*\/\s*([\d.]+))?\s*\)$/', $color, $matches)) {
		return array_merge(labToRgb(
			floatval($matches[1]),
			floatval($matches[2]),
			floatval($matches[3])
		), [isset($matches[4]) ? floatval($matches[4]) : 1]);
	} elseif (preg_match('/^hsb\((\d+),\s*(\d+)%?,\s*(\d+)%?\)$/', $color, $matches)) {
		return hsbToRgb(
			floatval($matches[1]),
			floatval($matches[2]),
			floatval($matches[3])
		);
	} elseif (preg_match('/^hwb\(\s*([\d.]+)(deg|turn)?\s+(\d+(?:\.\d+)?%)\s+(\d+(?:\.\d+)?%)(?:\s*(?:\/|,)\s*([\d.]+%?))?\s*\)$/', $color, $matches)) {
		// Convert turn to degrees if specified
		$hue = floatval($matches[1]);
		if (isset($matches[2]) && $matches[2] === 'turn') {
			$hue *= 360;
		}
		
		// Extract whiteness and blackness percentages
		$whiteness = floatval(rtrim($matches[3], '%'));
		$blackness = floatval(rtrim($matches[4], '%'));
		
		// Handle alpha value
		$alpha = 1;
		if (isset($matches[5])) {
			$alpha = str_ends_with($matches[5], '%') ? 
				floatval(rtrim($matches[5], '%')) / 100 : 
				floatval($matches[5]);
		}
		
		$rgb = hwbToRgb($hue, $whiteness, $blackness);
		return array_merge($rgb, [$alpha]);
	} elseif (preg_match('/^lch\(\s*(\d*\.?\d+)%?\s+(\d*\.?\d+)\s+(\d*\.?\d+)(?:\s*\/\s*([\d.]+))?\s*\)$/', $color, $matches)) {
		// Extract values
		$lightness = floatval($matches[1]);
		$chroma = floatval($matches[2]);
		$hue = floatval($matches[3]);
		$alpha = isset($matches[4]) ? floatval($matches[4]) : 1;
		
		// If lightness wasn't specified with %, scale it to 0-100 range if it's in 0-1 range
		if (strpos($matches[1], '%') === false && $lightness <= 1) {
			$lightness *= 100;
		}
		
		$rgb = lchToRgb($lightness, $chroma, $hue, $alpha);
		return $rgb;
	} elseif (preg_match('/^oklab\(\s*(\d*\.?\d+)%?\s+(-?\d*\.?\d+)\s+(-?\d*\.?\d+)(?:\s*\/\s*([\d.]+))?\s*\)$/', $color, $matches)) {
		// Extract values
		$lightness = floatval($matches[1]);
		$a = floatval($matches[2]);
		$b = floatval($matches[3]);
		$alpha = isset($matches[4]) ? floatval($matches[4]) : 1;
		
		// If lightness wasn't specified with %, scale it to 0-100 range if it's in 0-1 range
		if (strpos($matches[1], '%') === false && $lightness <= 1) {
			$lightness *= 100;
		}
		// Convert to 0-1 range
		$lightness = $lightness / 100;
		
		return oklabToRgb($lightness, $a, $b, $alpha);

	} elseif (preg_match('/^oklch\(\s*(\d*\.?\d+)%?\s+(\d*\.?\d+)\s+(\d*\.?\d+)(?:\s*\/\s*([\d.]+)%?)?\s*\)$/i', $color, $matches)) {
		// Extract values
		$lightness = floatval($matches[1]);
		$chroma = floatval($matches[2]);
		$hue = floatval($matches[3]);

		// Handle alpha: may be null, a number, or a percentage
		$alpha = 1;
		if (isset($matches[4])) {
			$alphaStr = trim($matches[4]);
			if (str_ends_with($alphaStr, '%')) {
				$alpha = floatval(rtrim($alphaStr, '%')) / 100;
			} else {
				$alpha = floatval($alphaStr);
			}
		}

		// If lightness wasn't specified with %, scale if in 0–1 range
		if (strpos($matches[1], '%') === false && $lightness <= 1) {
			$lightness *= 100;
		}

		// Convert lightness to 0–1
		$lightness = $lightness / 100;

		return oklchToRgb($lightness, $chroma, $hue, $alpha);
	}

	return false;
}
?>