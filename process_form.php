<?php
// process_form.php
require_once 'functions.php';
require_once 'parse_colors.php';

define('MAX_COLORS', 50);

function processColorForm($colors, $skip_black_white = false) {
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
		} else {
			$invalid_colors[] = $color;
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
		}
	}
	
	// Add black and white for single color case, excluding duplicates
	// Skip this in advanced mode to avoid adding black and white for dark/light mode background colors
	if (!$skip_black_white && count($parsed_colors) === 1) {
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

function processAdvancedModeColors($light_text, $light_primary, $light_bg, $dark_text, $dark_primary, $dark_bg) {
	// Helper function to parse multiple colors from textarea input
	$parseColorField = function($input) {
		if (empty(trim($input))) {
			return ['colors' => [], 'invalid' => []];
		}
		$colors = [];
		$invalid = [];
		$lines = preg_split('/\r\n|\r|\n/', trim($input));
		foreach ($lines as $line) {
			$line = trim($line);
			if (empty($line)) continue;
			$rgb = parseColor($line);
			if ($rgb !== false) {
				$colors[] = $line;
			} else {
				$invalid[] = $line;
			}
		}
		return ['colors' => $colors, 'invalid' => $invalid];
	};
	
	// Collect light mode background colors
	$light_bg_result = $parseColorField($light_bg);
	$light_bg_colors = $light_bg_result['colors'];
	$light_bg_invalid = $light_bg_result['invalid'];
	
	// Collect light mode foreground colors (text + primary + always include black and white)
	$light_text_result = $parseColorField($light_text);
	$light_primary_result = $parseColorField($light_primary);
	$light_fg_colors = array_merge($light_text_result['colors'], $light_primary_result['colors'], ['#FFFFFF', '#000000']);
	$light_fg_invalid = array_merge($light_text_result['invalid'], $light_primary_result['invalid']);

	// Add primary colors to background colors (primary colors function as both foreground and background)
	$light_bg_colors = array_merge($light_bg_colors, $light_primary_result['colors']);
	
	// Collect dark mode background colors
	$dark_bg_result = $parseColorField($dark_bg);
	$dark_bg_colors = $dark_bg_result['colors'];
	$dark_bg_invalid = $dark_bg_result['invalid'];
	
	// Collect dark mode foreground colors (text + primary + always include black and white)
	$dark_text_result = $parseColorField($dark_text);
	$dark_primary_result = $parseColorField($dark_primary);
	$dark_fg_colors = array_merge($dark_text_result['colors'], $dark_primary_result['colors'], ['#FFFFFF', '#000000']);
	$dark_fg_invalid = array_merge($dark_text_result['invalid'], $dark_primary_result['invalid']);

	// Add primary colors to background colors (primary colors function as both foreground and background)
	$dark_bg_colors = array_merge($dark_bg_colors, $dark_primary_result['colors']);
	
	// Process light mode background colors
	$light_bg_result = processColorForm(implode("\n", $light_bg_colors), true);
	
	// Process light mode foreground colors
	$light_fg_result = processColorForm(implode("\n", $light_fg_colors), true);
	
	// Process dark mode background colors
	$dark_bg_result = processColorForm(implode("\n", $dark_bg_colors), true);
	
	// Process dark mode foreground colors
	$dark_fg_result = processColorForm(implode("\n", $dark_fg_colors), true);
	
	return [
		'light_mode' => [
			'background_colors' => $light_bg_result['parsed_colors'],
			'foreground_colors' => $light_fg_result['parsed_colors'],
			'invalid_colors' => array_merge($light_bg_result['invalid_colors'], $light_fg_result['invalid_colors'], $light_bg_invalid, $light_fg_invalid),
			'duplicate_colors' => array_merge($light_bg_result['duplicate_colors'], $light_fg_result['duplicate_colors']),
			'semantic_duplicates' => array_merge($light_bg_result['semantic_duplicates'], $light_fg_result['semantic_duplicates']),
			'excess_colors' => $light_bg_result['excess_colors'] || $light_fg_result['excess_colors']
		],
		'dark_mode' => [
			'background_colors' => $dark_bg_result['parsed_colors'],
			'foreground_colors' => $dark_fg_result['parsed_colors'],
			'invalid_colors' => array_merge($dark_bg_result['invalid_colors'], $dark_fg_result['invalid_colors'], $dark_bg_invalid, $dark_fg_invalid),
			'duplicate_colors' => array_merge($dark_bg_result['duplicate_colors'], $dark_fg_result['duplicate_colors']),
			'semantic_duplicates' => array_merge($dark_bg_result['semantic_duplicates'], $dark_fg_result['semantic_duplicates']),
			'excess_colors' => $dark_bg_result['excess_colors'] || $dark_fg_result['excess_colors']
		]
	];
}
?>