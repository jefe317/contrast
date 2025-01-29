<?php
// download_report.php
require_once 'functions.php';
require_once 'parse_colors.php';

function generateColorReport($colors) {
	if (!empty($colors)) {
		$result = processColorForm($colors);
		$parsed_colors = $result['parsed_colors'];
		$excess_colors = $result['excess_colors'];
		$invalid_colors = $result['invalid_colors'];
		$duplicate_colors = $result['duplicate_colors'];
		$semantic_duplicates = $result['semantic_duplicates'];

		ob_start();
		include 'report_template.php';
		return ob_get_clean();
	}
}
?>