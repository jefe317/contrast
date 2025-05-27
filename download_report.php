<?php
// download_report.php
require_once 'functions.php';
require_once 'parse_colors.php';

function generateColorReport($colors, $contrast_method = 'wcag') {
	if (!empty($colors)) {
		$result = processColorForm($colors);
		$parsed_colors = $result['parsed_colors'];
		$excess_colors = $result['excess_colors'];
		$invalid_colors = $result['invalid_colors'];
		$duplicate_colors = $result['duplicate_colors'];
		$semantic_duplicates = $result['semantic_duplicates'];
		// log a usage for our stats.txt
		date_default_timezone_set('America/Chicago');
		$datetime = date('Y-m-d h:i:s A');
		$file = 'stats.txt';
		$value = count($parsed_colors)." colors, ".$contrast_method." method";
		// Create the log entry
		$logEntry = $datetime . ", " . $value . ", download" . PHP_EOL;
		file_put_contents($file, $logEntry, FILE_APPEND);
		ob_start();
		include 'report_template.php';
		return ob_get_clean();
	}
}
?>