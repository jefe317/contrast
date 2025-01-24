<?php
require_once 'functions.php';
require_once 'parse_colors.php';

define('MAX_COLORS', 50);

function processColorForm($colors) {
	$parsed_colors = [];
	$invalid_colors = [];
	$duplicate_colors = [];
	$excess_colors = false;
	
	// Split on newlines first
	$cleaned_input = preg_split('/\n/', $colors);
	// Handle multiple semicolons and clean up each line
	$cleaned_input = array_map(function($line) {
		// Split on one or more semicolons and take only non-empty parts
		$parts = preg_split('/;+/', $line);
		return array_filter($parts, 'strlen');
	}, $cleaned_input);
	// Flatten the array and remove empty items
	$cleaned_input = array_merge(...$cleaned_input);
	$cleaned_input = array_map('trim', $cleaned_input);
	$cleaned_input = array_filter($cleaned_input, 'strlen');
	
	// Check for duplicates after cleaning each color
	$seen_colors = [];
	$unique_colors = [];
	foreach ($cleaned_input as $color) {
		$clean_color = preg_replace('/\/\*.*?\*\//s', '', $color);
		$clean_color = preg_replace('/\/\/[^;\n]*[;\n]/', '', $clean_color);
		$clean_color = trim($clean_color);
		
		if (empty($clean_color)) continue;
		
		if (in_array($clean_color, $seen_colors)) {
			$duplicate_colors[] = $color;
		} else {
			$seen_colors[] = $clean_color;
			$unique_colors[] = $color;
		}
	}
	
	$cleaned_input = $unique_colors;
	
	// Check if we have excess colors
	if (count($cleaned_input) > MAX_COLORS) {
		$excess_colors = true;
		$cleaned_input = array_slice($cleaned_input, 0, MAX_COLORS);
	}
	
	$colors = array_map(function($color) {
		return substr($color, 0, 50);
	}, $cleaned_input);
	
	foreach ($colors as $original_color) {
		$clean_color = preg_replace('/\/\*.*?\*\//s', '', $original_color);
		$clean_color = preg_replace('/\/\/[^;\n]*[;\n]/', '', $clean_color);
		$clean_color = trim($clean_color);
		
		if (empty($clean_color)) {
			continue;
		}
		
		$rgb = parseColor($clean_color);
		if ($rgb !== false) {
			$parsed_colors[$original_color] = [
				'rgb' => array_slice($rgb, 0, 3),
				'alpha' => isset($rgb[3]) ? $rgb[3] : 1,
				'luminance' => getLuminance(array_slice($rgb, 0, 3))
			];
		} else {
			$invalid_colors[] = $original_color;
		}
	}
	
	// Add black and white if only one valid color was parsed
	if (count($parsed_colors) === 1) {
		$parsed_colors['black'] = [
			'rgb' => [0, 0, 0],
			'alpha' => 1,
			'luminance' => getLuminance([0, 0, 0])
		];
		$parsed_colors['white'] = [
			'rgb' => [255, 255, 255],
			'alpha' => 1,
			'luminance' => getLuminance([255, 255, 255])
		];
	}
	
	return [
		'parsed_colors' => $parsed_colors,
		'invalid_colors' => $invalid_colors,
		'duplicate_colors' => $duplicate_colors,
		'excess_colors' => $excess_colors,
		'original_input' => $colors
	];
}
?>