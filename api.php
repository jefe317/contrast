<?php
// api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit();
}

// Include required files
require_once 'functions.php';
require_once 'parse_colors.php';
require_once 'process_form.php';

// Set timezone
date_default_timezone_set('America/Chicago');
$start_time = microtime(true);

try {
    // Get raw input
    $raw_input = file_get_contents('php://input');
    
    // Try to decode as JSON first
    $json_data = json_decode($raw_input, true);
    
    $colors = null;
    $contrast_method = 'wcag'; // Default method
    $format = 'auto'; // auto, text, json
    
    if ($json_data !== null && json_last_error() === JSON_ERROR_NONE) {
        // Handle JSON input
        if (isset($json_data['colors'])) {
            if (is_array($json_data['colors'])) {
                // Array of colors
                $colors = implode("\n", $json_data['colors']);
                $format = 'json';
            } else {
                // String with newlines
                $colors = $json_data['colors'];
                $format = 'text';
            }
        }
        
        if (isset($json_data['contrast_method'])) {
            $contrast_method = $json_data['contrast_method'];
        }
        
        if (isset($json_data['format'])) {
            $format = $json_data['format'];
        }
    } else {
        // Handle form data or plain text
        if (isset($_POST['colors'])) {
            $colors = $_POST['colors'];
            $format = 'form';
            if (isset($_POST['contrast_method'])) {
                $contrast_method = $_POST['contrast_method'];
            }
        } else {
            // Treat as plain text input
            $colors = $raw_input;
            $format = 'text';
        }
    }
    
    // Validate input
    if (empty($colors)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'No colors provided',
            'message' => 'Please provide colors in the request body'
        ]);
        exit();
    }
    
    // Validate contrast method
    $valid_methods = ['wcag', 'apca', 'both'];
    if (!in_array($contrast_method, $valid_methods)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid contrast method',
            'message' => 'Valid methods are: ' . implode(', ', $valid_methods)
        ]);
        exit();
    }
    
    // Process colors
    $result = processColorForm($colors);
    $parsed_colors = $result['parsed_colors'];
    
    if (empty($parsed_colors)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'No valid colors found',
            'invalid_colors' => $result['invalid_colors'],
            'duplicate_colors' => $result['duplicate_colors']
        ]);
        exit();
    }
    
    // Generate contrast data
    $contrast_data = generateContrastData($parsed_colors, $contrast_method);
    
    // Calculate processing time
    $processing_time = microtime(true) - $start_time;
    
    // Build response
    $response = [
        'success' => true,
        'meta' => [
            'colors_processed' => count($parsed_colors),
            'contrast_method' => $contrast_method,
            'processing_time_seconds' => round($processing_time, 6),
            'input_format' => $format,
            'timestamp' => date('c')
        ],
        'colors' => array_keys($parsed_colors),
        'contrast_data' => $contrast_data
    ];
    
    // Add warnings if any
    $warnings = [];
    if (!empty($result['invalid_colors'])) {
        $warnings['invalid_colors'] = $result['invalid_colors'];
    }
    if (!empty($result['duplicate_colors'])) {
        $warnings['duplicate_colors'] = $result['duplicate_colors'];
    }
    if (!empty($result['semantic_duplicates'])) {
        $warnings['semantic_duplicates'] = $result['semantic_duplicates'];
    }
    if ($result['excess_colors']) {
        $warnings['excess_colors'] = 'Only first ' . MAX_COLORS . ' colors were processed';
    }
    
    if (!empty($warnings)) {
        $response['warnings'] = $warnings;
    }
    
    // Log usage
    $datetime = date('Y-m-d h:i:s A');
    $log_entry = $datetime . ", " . count($parsed_colors) . " colors, " . $contrast_method . " method, API" . PHP_EOL;
    file_put_contents('stats.txt', $log_entry, FILE_APPEND);
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Generate contrast data for all color combinations
 */
function generateContrastData($parsed_colors, $contrast_method) {
    $contrast_data = [];
    
    foreach ($parsed_colors as $bg_color => $bg_data) {
        $background_data = [
            'color' => $bg_color,
            'rgb' => $bg_data['rgb'],
            'alpha' => $bg_data['alpha'],
            'luminance' => $bg_data['luminance'],
            'combinations' => []
        ];
        
        foreach ($parsed_colors as $fg_color => $fg_data) {
            if ($fg_color === $bg_color) continue;
            
            $combination = [
                'foreground_color' => $fg_color,
                'foreground_rgb' => $fg_data['rgb'],
                'foreground_alpha' => $fg_data['alpha']
            ];
            
            if ($contrast_method === 'wcag') {
                $fg_lum = getLuminance($fg_data['rgb'], $fg_data['alpha'], $bg_data['rgb']);
                $contrast_ratio = getContrastRatio($bg_data['luminance'], $fg_lum);
                $wcag_level = getWCAGLevel($contrast_ratio);
                
                $combination['wcag'] = [
                    'contrast_ratio' => round($contrast_ratio, 2),
                    'level' => $wcag_level,
                    'passes_aa' => $contrast_ratio >= 4.5,
                    'passes_aa_large' => $contrast_ratio >= 3.0,
                    'passes_aaa' => $contrast_ratio >= 7.0
                ];
                
            } elseif ($contrast_method === 'apca') {
                $apca_contrast = getAPCAContrast($bg_data['rgb'], $fg_data['rgb'], $bg_data['alpha'], $fg_data['alpha']);
                $apca_level = getAPCALevel($apca_contrast);
                
                $combination['apca'] = [
                    'contrast_value' => round($apca_contrast, 1),
                    'level' => $apca_level,
                    'absolute_value' => round(abs($apca_contrast), 1)
                ];
                
            } else { // both
                // WCAG
                $fg_lum = getLuminance($fg_data['rgb'], $fg_data['alpha'], $bg_data['rgb']);
                $wcag_contrast = getContrastRatio($bg_data['luminance'], $fg_lum);
                $wcag_level = getWCAGLevel($wcag_contrast);
                
                // APCA
                $apca_contrast = getAPCAContrast($bg_data['rgb'], $fg_data['rgb'], $bg_data['alpha'], $fg_data['alpha']);
                $apca_level = getAPCALevel($apca_contrast);
                
                // Combined
                $combined_level = getCombinedLevel($wcag_contrast, $apca_contrast);
                
                $combination['wcag'] = [
                    'contrast_ratio' => round($wcag_contrast, 2),
                    'level' => $wcag_level,
                    'passes_aa' => $wcag_contrast >= 4.5,
                    'passes_aa_large' => $wcag_contrast >= 3.0,
                    'passes_aaa' => $wcag_contrast >= 7.0
                ];
                
                $combination['apca'] = [
                    'contrast_value' => round($apca_contrast, 1),
                    'level' => $apca_level,
                    'absolute_value' => round(abs($apca_contrast), 1)
                ];
                
                $combination['combined'] = [
                    'level' => $combined_level
                ];
            }
            
            $background_data['combinations'][] = $combination;
        }
        
        $contrast_data[] = $background_data;
    }
    
    return $contrast_data;
}
?>