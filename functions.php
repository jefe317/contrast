<?php
// functions.php
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

/**
 * Convert sRGB to Y (luminance) for APCA
 * Based on the reference JavaScript implementation
 */
function sRGBtoY($rgb) {
	$mainTRC = 2.4;
	$sRco = 0.2126729;
	$sGco = 0.7151522;
	$sBco = 0.0721750;
	
	$simpleExp = function($chan) use ($mainTRC) {
		return pow($chan / 255.0, $mainTRC);
	};
	
	return $sRco * $simpleExp($rgb[0]) + $sGco * $simpleExp($rgb[1]) + $sBco * $simpleExp($rgb[2]);
}

/**
 * Calculate APCA contrast value between two colors
 * Based on the APCA algorithm by Andrew Somers (version 0.1.9 for WCAG 3)
 * Updated to match the JavaScript reference implementation
 * 
 * @param array $bg RGB background color array [r,g,b]
 * @param array $text RGB text color array [r,g,b]
 * @param float $bgAlpha Background alpha (0-1)
 * @param float $textAlpha Text alpha (0-1)
 * @return float APCA contrast value (can be negative or positive)
 */
function getAPCAContrast($bg, $text, $bgAlpha = 1, $textAlpha = 1) {
	// Handle alpha blending if needed
	if ($bgAlpha < 1 || $textAlpha < 1) {
		// For simplicity, assume white backdrop for alpha blending
		$backdrop = [255, 255, 255];
		if ($bgAlpha < 1) {
			$bg = blendColors($bg, $backdrop, $bgAlpha);
		}
		if ($textAlpha < 1) {
			$text = blendColors($text, $bg, $textAlpha);
		}
	}
	
	// APCA constants from the JavaScript reference
	$normBG = 0.56;
	$normTXT = 0.57;
	$revTXT = 0.62;
	$revBG = 0.65;
	$blkThrs = 0.022;
	$blkClmp = 1.414;
	$scaleBoW = 1.14;
	$scaleWoB = 1.14;
	$loBoWoffset = 0.027;
	$loWoBoffset = 0.027;
	$deltaYmin = 0.0005;
	$loClip = 0.1;
	
	// Convert sRGB to Y (luminance)
	$txtY = sRGBtoY($text);
	$bgY = sRGBtoY($bg);
	
	// Validate input range
	$icp = [0.0, 1.1];
	if (is_nan($txtY) || is_nan($bgY) || min($txtY, $bgY) < $icp[0] || max($txtY, $bgY) > $icp[1]) {
		return 0.0;
	}
	
	// Apply soft clip and clamp black levels
	$txtY = ($txtY > $blkThrs) ? $txtY : $txtY + pow($blkThrs - $txtY, $blkClmp);
	$bgY = ($bgY > $blkThrs) ? $bgY : $bgY + pow($blkThrs - $bgY, $blkClmp);
	
	// Check if the difference is too small (same or nearly same colors)
	if (abs($bgY - $txtY) < $deltaYmin) {
		return 0.0;
	}
	
	$SAPC = 0.0;
	$outputContrast = 0.0;
	
	if ($bgY > $txtY) { // Normal polarity (dark text on light background)
		$SAPC = (pow($bgY, $normBG) - pow($txtY, $normTXT)) * $scaleBoW;
		$outputContrast = ($SAPC < $loClip) ? 0.0 : $SAPC - $loBoWoffset;
	} else { // Reverse polarity (light text on dark background)
		$SAPC = (pow($bgY, $revBG) - pow($txtY, $revTXT)) * $scaleWoB;
		$outputContrast = ($SAPC > -$loClip) ? 0.0 : $SAPC + $loWoBoffset;
	}
	
	// Return as percentage
	return $outputContrast * 100.0;
}

/**
 * Get APCA contrast level
 * Based on APCA guidelines
 * 
 * @param float $contrast APCA contrast value
 * @return string APCA compliance level
 */
function getAPCALevel($contrast) {
	$absContrast = abs($contrast);
	
	if ($absContrast >= 90) {
		return 'Perfect - LCf 90+ (All text)';
	} elseif ($absContrast >= 75) {
		return 'Excellent - LCf 75+ (Body text)';
	} elseif ($absContrast >= 60) {
		return 'Good - LCf 60+ (Large text)';
	} elseif ($absContrast >= 45) {
		return 'Fair - LCf 45+ (Large bold text)';
	} elseif ($absContrast >= 30) {
		return 'Poor - LCf 30+ (Spot text only)';
	} else {
		return 'Fail - Insufficient contrast';
	}
}

/**
 * Calculate APCA contrast (convenience function)
 * 
 * @param array $textColor RGB text color array
 * @param array $bgColor RGB background color array
 * @param float $textAlpha Text alpha (0-1)
 * @param float $bgAlpha Background alpha (0-1)
 * @return float APCA contrast value
 */
function calcAPCA($textColor, $bgColor, $textAlpha = 1, $bgAlpha = 1) {
	return getAPCAContrast($bgColor, $textColor, $bgAlpha, $textAlpha);
}

/**
 * Alpha blend function for APCA calculations
 * 
 * @param array $rgbaFG Foreground color with alpha [r,g,b,a]
 * @param array $rgbBG Background color [r,g,b]
 * @param bool $round Whether to round the result
 * @return array Blended RGB color
 */
function alphaBlend($rgbaFG, $rgbBG, $round = true) {
	$alpha = max(min($rgbaFG[3], 1.0), 0.0);
	$compBlend = 1.0 - $alpha;
	$rgbOut = [0, 0, 0];
	
	for ($i = 0; $i < 3; $i++) {
		$rgbOut[$i] = $rgbBG[$i] * $compBlend + $rgbaFG[$i] * $alpha;
		if ($round) {
			$rgbOut[$i] = min(round($rgbOut[$i]), 255);
		}
	}
	
	return $rgbOut;
}

/**
 * Reverse APCA - find unknown color given contrast and known color
 * 
 * @param float $contrast Target APCA contrast
 * @param float $knownY Known color luminance
 * @param string $knownType Either 'bg' or 'text'
 * @param string $returnAs Return format: 'hex', 'rgb', or 'Y'
 * @return mixed The calculated color in requested format, or false if impossible
 */
function reverseAPCA($contrast = 0, $knownY = 1.0, $knownType = 'bg', $returnAs = 'hex') {
	if (abs($contrast) < 9) {
		return false;
	}
	
	$normBG = 0.56;
	$normTXT = 0.57;
	$revTXT = 0.62;
	$revBG = 0.65;
	$blkThrs = 0.022;
	$blkClmp = 1.414;
	$scaleBoW = 1.14;
	$scaleWoB = 1.14;
	$loBoWoffset = 0.027;
	$loWoBoffset = 0.027;
	$mainTRCencode = 1 / 2.4;
	
	// Constants for reverse calculation
	$mFactor = 1.94685544331710;
	$mFactInv = 1 / $mFactor;
	$mOffsetIn = 0.03873938165714010;
	$mExpAdj = 0.2833433964208690;
	$mExp = $mExpAdj / $blkClmp;
	$mOffsetOut = 0.3128657958707580;
	
	$unknownY = $knownY;
	
	$scale = $contrast > 0 ? $scaleBoW : $scaleWoB;
	$offset = $contrast > 0 ? $loBoWoffset : -$loWoBoffset;
	$contrast = ($contrast * 0.01 + $offset) / $scale;
	
	$knownY = ($knownY > $blkThrs) ? $knownY : $knownY + pow($blkThrs - $knownY, $blkClmp);
	
	if ($knownType == 'bg' || $knownType == 'background') {
		$knownExp = $contrast > 0 ? $normBG : $revBG;
		$unknownExp = $contrast > 0 ? $normTXT : $revTXT;
		$unknownY = pow(pow($knownY, $knownExp) - $contrast, 1 / $unknownExp);
		if (is_nan($unknownY)) return false;
	} elseif ($knownType == 'txt' || $knownType == 'text') {
		$knownExp = $contrast > 0 ? $normTXT : $revTXT;
		$unknownExp = $contrast > 0 ? $normBG : $revBG;
		$unknownY = pow($contrast + pow($knownY, $knownExp), 1 / $unknownExp);
		if (is_nan($unknownY)) return false;
	} else {
		return false;
	}
	
	if ($unknownY > 1.06 || $unknownY < 0) {
		return false;
	}
	
	$unknownY = ($unknownY > $blkThrs) ? $unknownY : (pow((($unknownY + $mOffsetIn) * $mFactor), $mExp) * $mFactInv) - $mOffsetOut;
	
	$unknownY = max(min($unknownY, 1.0), 0.0);
	
	if ($returnAs === 'hex') {
		$hexB = dechex(round(pow($unknownY, $mainTRCencode) * 255));
		$hexB = str_pad($hexB, 2, '0', STR_PAD_LEFT);
		return '#' . $hexB . $hexB . $hexB;
	} elseif ($returnAs === 'rgb') {
		$colorB = round(pow($unknownY, $mainTRCencode) * 255);
		return [$colorB, $colorB, $colorB];
	} elseif ($returnAs === 'Y' || $returnAs === 'y') {
		return max(0.0, $unknownY);
	} else {
		return false;
	}
}

/**
 * Get combined WCAG and APCA level for "Best of Both" mode
 * Maps WCAG AAA/Perfect, AA/Good+, AA Large/Fair+ as equivalent levels
 */
function getCombinedLevel($wcag_level, $apca_level) {
    $apca_base = explode(' - ', $apca_level)[0];
    
    // Tier 1: AAA + Perfect
    if ($wcag_level === 'AAA' && $apca_base === 'Perfect') {
        return 'Excellent';
    }
    
    // Tier 2: AA + (Good or better)
    if ($wcag_level === 'AA' && in_array($apca_base, ['Perfect', 'Excellent', 'Good'])) {
        return 'Good';
    }
    
    // Tier 3: AA Large + (Fair or better)
    if ($wcag_level === 'AA Large' && in_array($apca_base, ['Perfect', 'Excellent', 'Good', 'Fair'])) {
        return 'Fair';
    }
    
    return 'Fail';
}

/**
 * Get display name for combined levels
 */
function getCombinedLevelDisplay($level) {
    switch ($level) {
        case 'Excellent': return 'Excellent - AAA + Perfect';
        case 'Good': return 'Good - AA + Good+';
        case 'Fair': return 'Fair - AA Large + Fair+';
        default: return 'Fail - Below thresholds';
    }
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