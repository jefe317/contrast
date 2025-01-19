<?php
require_once 'functions.php';
require_once 'parse_colors.php';

function generateColorReport($colors) {
    // Process the colors first
    $cleaned_input = preg_split('/(?<=;|\n)/', $colors);
    $cleaned_input = array_map('trim', $cleaned_input);
    $cleaned_input = array_filter($cleaned_input, 'strlen');
    
    $colors_array = array_map(function($color) {
        return substr($color, 0, 50);
    }, $cleaned_input);
    $colors_array = array_slice($colors_array, 0, 50);
    
    // Add black and white if only one color
    if (count($colors_array) === 1) {
        $test_color = parseColor($colors_array[0]);
        if ($test_color !== false) {
            array_push($colors_array, 'black', 'white');
        }
    }
    
    // Parse each color
    $parsed_colors = [];
    foreach ($colors_array as $original_color) {
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
        }
    }
    
    // Now include the template with the processed colors
    ob_start();
    include 'report_template.php';
    return ob_get_clean();
}
?>