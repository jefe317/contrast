<?php
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

function lchToRgb($l, $c, $h, $alpha = 1) {
	$a = $c * cos(deg2rad($h));
	$b = $c * sin(deg2rad($h));
	return array_merge(labToRgb($l, $a, $b), [$alpha]);
}

function hwbToRgb($h, $w, $b) {
	$h = $h % 360;
	if ($h < 0) {
		$h += 360;
	}
	
	$w = min(100, max(0, $w)) / 100;
	$b = min(100, max(0, $b)) / 100;
	
	if ($w + $b >= 1) {
		$gray = $w / ($w + $b) * 255;
		return [round($gray), round($gray), round($gray)];
	}
	
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
	
	$factor = (1 - $w - $b);
	$r = $r * $factor + $w;
	$g = $g * $factor + $w;
	$b_val = $b_val * $factor + $w;
	
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

function cmykToRgb($c, $m, $y, $k) {
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
	$y = ($l + 16) / 116;
	$x = $a / 500 + $y;
	$z = $y - $b / 200;
	
	$lab2xyz = function($v) {
		$v3 = $v * $v * $v;
		return $v3 > 0.008856 ? $v3 : ($v - 16/116) / 7.787;
	};
	
	$x = 0.95047 * $lab2xyz($x);
	$y = 1.00000 * $lab2xyz($y);
	$z = 1.08883 * $lab2xyz($z);
	
	$r = $x *  3.2406 + $y * -1.5372 + $z * -0.4986;
	$g = $x * -0.9689 + $y *  1.8758 + $z *  0.0415;
	$b = $x *  0.0557 + $y * -0.2040 + $z *  1.0570;
	
	$clip = function($v) {
		$v = $v > 0.0031308 ? 1.055 * pow($v, 1/2.4) - 0.055 : 12.92 * $v;
		return round(max(0, min(1, $v)) * 255);
	};
	
	return [$clip($r), $clip($g), $clip($b)];
}

function hsbToRgb($h, $s, $v) {
	$s = $s / 100;
	$v = $v / 100;
	$h = $h / 60;
	
	$i = floor($h);
	$f = $h - $i;
	$p = $v * (1 - $s);
	$q = $v * (1 - $s * $f);
	$t = $v * (1 - $s * (1 - $f));
	
	switch ($i) {
		case 0: $r = $v; $g = $t; $b = $p; break;
		case 1: $r = $q; $g = $v; $b = $p; break;
		case 2: $r = $p; $g = $v; $b = $t; break;
		case 3: $r = $p; $g = $q; $b = $v; break;
		case 4: $r = $t; $g = $p; $b = $v; break;
		default: $r = $v; $g = $p; $b = $q; break;
	}
	
	return [
		round($r * 255),
		round($g * 255),
		round($b * 255)
	];
}

function oklabToRgb($L, $a, $b, $alpha = 1) {
	$l = $L + 0.3963377774 * $a + 0.2158037573 * $b;
	$m = $L - 0.1055613458 * $a - 0.0638541728 * $b;
	$s = $L - 0.0894841775 * $a - 1.2914855480 * $b;

	$l = $l * $l * $l;
	$m = $m * $m * $m;
	$s = $s * $s * $s;

	$r = +4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s;
	$g = -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s;
	$b = -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s;

	$toSRGB = function($x) {
		if ($x <= 0) return 0;
		if ($x >= 1) return 255;
		return round(($x >= 0.0031308 ? 1.055 * pow($x, 1/2.4) - 0.055 : 12.92 * $x) * 255);
	};

	return [
		$toSRGB($r),
		$toSRGB($g),
		$toSRGB($b),
		$alpha
	];
}

function oklchToRgb($L, $c, $h, $alpha = 1) {
	$a = $c * cos(deg2rad($h));
	$b = $c * sin(deg2rad($h));
	return oklabToRgb($L, $a, $b, $alpha);
}

function blendColors($fg, $bg, $alpha) {
	return [
		($fg[0] * $alpha) + ($bg[0] * (1 - $alpha)),
		($fg[1] * $alpha) + ($bg[1] * (1 - $alpha)),
		($fg[2] * $alpha) + ($bg[2] * (1 - $alpha))
	];
}

function getLuminance($rgb, $alpha = 1, $bg = null) {
	if ($alpha < 1 && $bg !== null) {
		$rgb = blendColors($rgb, $bg, $alpha);
	}
	
	$rgb = array_map(function($val) {
		$val = $val / 255;
		return $val <= 0.03928 ? $val / 12.92 : pow(($val + 0.055) / 1.055, 2.4);
	}, $rgb);
	
	return $rgb[0] * 0.2126 + $rgb[1] * 0.7152 + $rgb[2] * 0.0722;
}

function getCleanColorName($color) {
	$color = preg_replace('/^(color|background-color|border-color):\s*/i', '', $color);
	$color = preg_replace('/[;{}].*$/', '', $color);
	return trim($color);
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

function getCssColor($color) {
	$rgba = parseColor($color);
	if ($rgba === false) {
		return '#000000';
	}
	
	if (!isset($rgba[3]) || $rgba[3] >= 0.999) {
		return sprintf('rgb(%d, %d, %d)', $rgba[0], $rgba[1], $rgba[2]);
	}
	
	return sprintf('rgba(%d, %d, %d, %.3f)', $rgba[0], $rgba[1], $rgba[2], $rgba[3]);
}

function getCopyrightYears($foundedYear) {
	$currentYear = date('Y');
	$site = "&copy; <a href=\"https://jefftml.com\">Jeff Lange</a>";
	if ($foundedYear > $currentYear) {
		return $site;
	}
	
	if ($foundedYear == $currentYear) {
		return $site . " " . $currentYear;
	}
	
	return $site . " " . $foundedYear . " - " . $currentYear;
}
?>