<?php
require_once 'functions.php';
require_once 'parse_colors.php';

function processColorForm($colors) {
	$parsed_colors = [];
	$invalid_colors = [];
	
	if (strlen($colors) > 1000) {
		die("Input too large");
	}
	
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
	
	$colors = array_map(function($color) {
		return substr($color, 0, 50);
	}, $cleaned_input);
	$colors = array_slice($colors, 0, 50);
	
	if (count($colors) === 1) {
		$test_color = parseColor($colors[0]);
		if ($test_color !== false) {
			array_push($colors, 'black', 'white');
		}
	}
	
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
	
	return [
		'parsed_colors' => $parsed_colors,
		'invalid_colors' => $invalid_colors,
		'original_input' => $colors
	];
}
?>