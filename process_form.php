<?php
require_once 'functions.php';
require_once 'parse_colors.php';

define('MAX_COLORS', 50);

function processColorForm($colors) {
	$parsed_colors = [];
	$invalid_colors = [];
	$duplicate_colors = [];
	$semantic_duplicates = [];
	$excess_colors = false;
	
	// Split and clean input
	$cleaned_input = preg_split('/\n/', $colors);
	$cleaned_input = array_map(function($line) {
		return array_filter(preg_split('/;+/', $line), 'strlen');
	}, $cleaned_input);
	$cleaned_input = array_merge(...$cleaned_input);
	$cleaned_input = array_map('trim', array_filter($cleaned_input, 'strlen'));
	
	// Track unique colors, duplicates, and semantic duplicates
	$seen_colors = [];
	$seen_rgb_values = [];
	$unique_colors = [];
	
	foreach ($cleaned_input as $color) {
		$clean_color = trim(preg_replace(['/\/\*.*?\*\//s', '/\/\/[^;\n]*[;\n]/'], '', $color));
		if (empty($clean_color)) continue;
		
		$normalized = strtoupper($clean_color);
		$rgb = parseColor($clean_color);
		
		if ($rgb !== false) {
			// Create a normalized RGB string for comparison
			$rgb_key = implode(',', array_slice($rgb, 0, 3)) . ',' . (isset($rgb[3]) ? $rgb[3] : 1);
			
			if (isset($seen_colors[$normalized])) {
				// Exact duplicate (same format)
				if (!in_array($clean_color, $duplicate_colors)) {
					$duplicate_colors[] = $seen_colors[$normalized];
				}
			} elseif (isset($seen_rgb_values[$rgb_key])) {
				// Semantic duplicate (different format, same color)
				if (!isset($semantic_duplicates[$rgb_key])) {
					$semantic_duplicates[$rgb_key] = [
						'original' => $seen_rgb_values[$rgb_key],
						'duplicates' => []
					];
				}
				$semantic_duplicates[$rgb_key]['duplicates'][] = $clean_color;
			} else {
				$seen_colors[$normalized] = $clean_color;
				$seen_rgb_values[$rgb_key] = $clean_color;
				$unique_colors[] = $color;
			}
		}
	}
	
	// Process max colors limit
	if (count($unique_colors) > MAX_COLORS) {
		$excess_colors = true;
		$unique_colors = array_slice($unique_colors, 0, MAX_COLORS);
	}
	
	// Process colors for output
	$has_white = false;
	$has_black = false;
	
	foreach ($unique_colors as $color) {
		$clean_color = trim(preg_replace(['/\/\*.*?\*\//s', '/\/\/[^;\n]*[;\n]/'], '', $color));
		if (empty($clean_color)) continue;
		
		$rgb = parseColor($clean_color);
		if ($rgb !== false) {
			$parsed_colors[$color] = [
				'rgb' => array_slice($rgb, 0, 3),
				'alpha' => isset($rgb[3]) ? $rgb[3] : 1,
				'luminance' => getLuminance(array_slice($rgb, 0, 3))
			];
			
			// Check if color is white or black
			$rgb_values = array_slice($rgb, 0, 3);
			if ($rgb_values == [255, 255, 255] && (isset($rgb[3]) ? $rgb[3] : 1) == 1) {
				$has_white = true;
			}
			if ($rgb_values == [0, 0, 0] && (isset($rgb[3]) ? $rgb[3] : 1) == 1) {
				$has_black = true;
			}
		} else {
			$invalid_colors[] = $color;
		}
	}
	
	// Add black and white for single color case, excluding duplicates
	if (count($parsed_colors) === 1) {
		if (!$has_black) {
			$parsed_colors['black'] = [
				'rgb' => [0, 0, 0],
				'alpha' => 1,
				'luminance' => getLuminance([0, 0, 0])
			];
		}
		if (!$has_white) {
			$parsed_colors['white'] = [
				'rgb' => [255, 255, 255],
				'alpha' => 1,
				'luminance' => getLuminance([255, 255, 255])
			];
		}
	}
	
	return [
		'parsed_colors' => $parsed_colors,
		'invalid_colors' => $invalid_colors,
		'duplicate_colors' => $duplicate_colors,
		'semantic_duplicates' => $semantic_duplicates,
		'excess_colors' => $excess_colors,
		'original_input' => $unique_colors
	];
}
?>