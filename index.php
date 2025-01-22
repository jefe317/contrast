<?php
$start = hrtime(true);
date_default_timezone_set('America/Chicago');

// Security headers and settings
ini_set('display_errors', 0);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', 3600);

// Set secure headers
header("Content-Security-Policy: " . 
	"default-src 'self'; " .
	"style-src 'self' 'unsafe-inline'; " .
	"img-src 'self' data: ; " .
	"frame-ancestors 'none'; " .
	"form-action 'self';"
);
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: same-origin");
header("Permissions-Policy: geolocation=(), camera=(), microphone=()");

// Set secure cookie parameters
ini_set('session.cookie_domain', '');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_lifetime', 0);

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

require_once 'functions.php';
require_once 'parse_colors.php';
require_once 'process_form.php';
require_once 'download_report.php';

$parsed_colors = [];
$invalid_colors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['download']) && !empty($_POST['colors'])) {
        $colors = $_POST['colors'];
        $report = generateColorReport($colors);
        
        $timestamp = date('Ymd-His');
        $filename = "color-contrast-report-{$timestamp}.html";
        
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo $report;
        exit;
    } elseif (!empty($_POST['colors'])) {
		$result = processColorForm($_POST['colors']);
		$parsed_colors = $result['parsed_colors'];
		$invalid_colors = $result['invalid_colors'];
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Jeff's Color Contrast Analyzer</title>
	<style>
		:root { color-scheme: light dark; }
		body { font-family: Arial, sans-serif; margin: 20px; }
		table { border-collapse: collapse; margin: 20px 0; }
		th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; }
		td div { padding: 0 0.5em 0.5em 0; }
		textarea { width: 100%; max-width: 400px; height: 200px; }
		.checkered {background: conic-gradient(hsla(0, 0%, 50%, 20%) 90deg, transparent 90deg 180deg, hsla(0, 0%, 50%, 20%) 180deg 270deg, transparent 270deg); background-repeat: repeat; background-size: 40px 40px; }
		.error-message { background-color: hsla(0 100% 63% / 0.5); border: 1px solid #f5c6cb; border-radius: 4px; padding: 12px; margin: 20px 0; width: fit-content;}
		.error-message h3 { margin-top: 0; }
		li { line-height: 1.5; }
		.footer { padding-top: 1em; }
		.format-list { margin: 1em 0; }
		.format-list summary { cursor: pointer; padding: 0.5em 0; }
		.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0; }
		@media (max-width: 768px) { 
		 .format-list[open] { margin-bottom: 1.5em; }
		 .format-list:not([open]) { margin-bottom: 1em; }
		 .format-list { border: 1px solid #ccc; border-radius: 4px; padding: 0.5em; }
		 .format-list[open] summary { margin-bottom: 0.5em; } 
		 .desktop { display: none; }
		}
		@media (min-width: 769px) { 
		 .format-list summary { display: none; }
		 .desktop { display: block; }
		 .format-list { open: true; } 
		}
	</style>
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="manifest" href="/site.webmanifest">
</head>
<body>
	<h1>Jeff's Color Contrast Analyzer</h1>
	<form method="post">
		<p class="desktop">Enter up to 50 colors (one per line) in any of these formats:</p>
		<ul class="desktop">
			<li><strong>Hex</strong>: #FFF, #FFFFFF, #FFFA (with alpha), #FFFFFFAA (with alpha)</li>
			<li><strong>RGB and RGBA</strong>: rgb(255, 255, 255) and rgba(255, 255, 255, 0.5)</li>
			<li><strong>HSL and HSLA</strong>: hsl(360, 100%, 100%) and hsla(360, 100%, 100%, 0.5)</li>
			<li>CSS <strong>Named Colors</strong>, like "black", "white", or "coral"</li>
			<li><strong>Lab</strong>: lab(75.5, 20.3, -15.6) and lab(50% 40 59.5 / 0.5)</li>
			<li><strong>HSB and HSV</strong>: hsb(240, 100%, 100%) and hsb(120, 50, 75)</li>
			<li><strong>LCH</strong>: lch(50% 70 200) and lch(52.2345% 72.2 56.2 / .5)</li>
			<li><strong>Oklab</strong>: oklab(59% 0.1 0.1) and oklab(59% 0.1 0.1 / 0.5)</li>
			<li><strong>Oklch</strong>: oklch(60% 0.15 50) and oklch(60% 0.15 50 / 0.5)</li>
			<li><strong>CMYK</strong>: cmyk(100%, 0%, 0%, 0%) and cmyk(0, 100, 100, 0)</li>
			<li>CSS syntax is also accepted (e.g., "color: #FFF;" or "background-color: rgb(255, 0, 0);")</li>
			<li>Note: <code>from</code> and <code>calc()</code> are not supported.</li>
		</ul>
		<details class="format-list">
		<summary>Enter up to 50 colors (one per line) in any of these formats: (click to expand)</summary>
		<ul>
			<li><strong>Hex</strong>: #FFF, #FFFFFF, #FFFA (with alpha), #FFFFFFAA (with alpha)</li>
			<li><strong>RGB and RGBA</strong>: rgb(255, 255, 255) and rgba(255, 255, 255, 0.5)</li>
			<li><strong>HSL and HSLA</strong>: hsl(360, 100%, 100%) and hsla(360, 100%, 100%, 0.5)</li>
			<li>CSS <strong>Named Colors</strong>, like "black", "white", or "coral"</li>
			<li><strong>HSB and HSV</strong>: hsb(240, 100%, 100%) and hsb(120, 50, 75)</li>
			<li><strong>HWB</strong>: hwb(12 50% 0%) and hwb(0 100% 0% / 50%);</li>
			<li><strong>Lab</strong>: lab(80% 100 50) and lab(50% 40 59.5 / 0.5)</li>
			<li><strong>LCH</strong>: lch(50% 70 200) and lch(52.2345% 72.2 56.2 / .5)</li>
			<li><strong>Oklab</strong>: oklab(59% 0.1 0.1) and oklab(59% 0.1 0.1 / 0.5)</li>
			<li><strong>Oklch</strong>: oklch(60% 0.15 50) and oklch(60% 0.15 50 / 0.5)</li>
			<li><strong>CMYK</strong>: cmyk(100%, 0%, 0%, 0%) and cmyk(0, 100, 100, 0)</li>
			<li>CSS syntax is also accepted (e.g., "color: #FFF;" or "background-color: rgb(255, 0, 0);")</li>
			<li>Note: <code>from</code> and <code>calc()</code> are not supported.</li>
		</ul>
	</details>
		<p>Labels can be added to a color by placing the label before a colon, like "link: #2C5491"</p>
		<textarea name="colors" required><?= isset($_POST['colors']) ? htmlspecialchars($_POST['colors']) : '' ?></textarea>
		<br><br>
		<button type="submit">Calculate Contrast</button>
	</form>
<?php if (!empty($parsed_colors)): ?>
	<div style="margin-top: 1em;"><form method="post">
		<input type="hidden" name="colors" value="<?= htmlspecialchars($_POST['colors']) ?>">
		<button type="submit" name="download" value="1">Download Report</button>
	</form></div>
<?php endif; 
if (!empty($invalid_colors)): ?>
	<div class="error-message">
		<h3>Invalid Colors Detected</h3>
		<p>The following colors could not be parsed:</p>
		<ul class="error-list">
			<?php foreach ($invalid_colors as $color): ?>
			<li><code><?= htmlspecialchars($color) ?></code></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif;
if (!empty($parsed_colors)): ?>
	<h2>Summary of Compatible Color Combinations</h2>
	<table class="checkered">
		<thead>
			<tr>
				<th>Background</th>
				<th>AAA ≥ 7.0<br>Best</th>
				<th>AA Normal ≥ 4.5<br>Second Best </th>
				<th>AA Large ≥ 3.0<br>Third Best</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($parsed_colors as $bg_color => $bg): 
				// Calculate all combinations for this background color
				$combinations = [];
				foreach ($parsed_colors as $fg_color => $fg) {
					if ($fg_color === $bg_color) continue;
					
					// Calculate luminance with alpha blending
					$fg_lum = getLuminance($fg['rgb'], $fg['alpha'], $bg['rgb']);
					$bg_lum = $bg['luminance'];
					$contrast = getContrastRatio($bg_lum, $fg_lum);
					
					$wcag_level = getWCAGLevel($contrast);
					if ($wcag_level !== 'Fail') {
						$combinations[] = [
							'color' => $fg_color,
							'contrast' => $contrast,
							'level' => $wcag_level
						];
					}
				}

				// Group by WCAG level
				$wcag_groups = [
					'AAA' => [],
					'AA' => [],
					'AA Large' => []
				];
				
				foreach ($combinations as $combo) {
					$wcag_groups[$combo['level']][] = $combo;
				}
				
				// Sort each group by contrast ratio
				foreach ($wcag_groups as &$group) {
					usort($group, function($a, $b) {
						return $b['contrast'] <=> $a['contrast'];
					});
				}

				// Check if there are any valid combinations
				$has_valid_combinations = !empty($wcag_groups['AAA']) || 
									   !empty($wcag_groups['AA']) || 
									   !empty($wcag_groups['AA Large']);
			?><tr style="background-color: <?= htmlspecialchars(getCssColor($bg_color)) ?>;">
				<td>
					<?php 
					// Use alpha-aware luminance for text color calculation
					$bg_text_lum = getLuminance($bg['rgb'], $bg['alpha']); ?>
					<span style="color: <?= $bg_text_lum > 0.5 ? '#000000' : '#FFFFFF' ?>"><?= htmlspecialchars(getCleanColorName($bg_color)) ?></span>
				</td>
		<?php if ($has_valid_combinations): ?>
			<?php foreach (['AAA', 'AA', 'AA Large'] as $level): ?>
				<td><?php foreach ($wcag_groups[$level] as $combo): ?>
					<div style="color: <?= htmlspecialchars(getCssColor($combo['color'])) ?>;"><?= htmlspecialchars($combo['color']) ?>(Ratio: <?= number_format($combo['contrast'], 2) ?>)</div>
				<?php endforeach; ?></td>
			<?php endforeach; ?>
			<?php else: ?>
				<td colspan="3" style="text-align: center; color: <?= $bg_text_lum > 0.5 ? '#000000' : '#FFFFFF' ?>">No valid color combinations found (all contrast ratios below 3.0)
				</td>
			<?php endif; ?>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<h2>Complete Contrast Grid</h2>
	<table class="checkered">
	<tr>
		<th>BG \ FG</th>
	<?php foreach ($parsed_colors as $color => $data): ?>
		<th><?= htmlspecialchars(getCleanColorName($color)) ?></th>
	<?php endforeach; ?>
	</tr>
<?php foreach ($parsed_colors as $bg_color => $bg_data): ?>
	<tr>
		<th><?= htmlspecialchars(getCleanColorName($bg_color)) ?></th>
<?php foreach ($parsed_colors as $fg_color => $fg_data): 
			// Calculate luminance with alpha blending
			$fg_lum = getLuminance($fg_data['rgb'], $fg_data['alpha'], $bg_data['rgb']);
			$bg_lum = $bg_data['luminance'];
			$contrast = getContrastRatio($bg_lum, $fg_lum);
			$wcag_level = getWCAGLevel($contrast); ?>
			<td style="background-color: <?= htmlspecialchars(getCssColor($bg_color)) ?>;">
				<div class="sample-text" style="color: <?= htmlspecialchars(getCssColor($fg_color)) ?>;">Sample
				<div style="font-size: 0.8em;"><?= number_format($contrast, 2) ?><br><?= $wcag_level ?></div>
				</div>
			</td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
	<div style="font-size: 0.8em; color: rgba(150, 150, 150, 0.5); padding-top: 2em;"><?php 
	$end = hrtime(true);
	$nanoseconds = $end - $start;
	// convert time to readable values
		// Create an array of time units and their nanosecond conversions
	$units = [
		'second' => 1e9,
		'millisecond' => 1e6,
		'microsecond' => 1e3,
		'nanosecond' => 1
	];

	// Find the most appropriate unit
	$value = $nanoseconds;
	$unit = 'nanosecond';

	foreach ($units as $name => $divisor) {
		if ($nanoseconds >= $divisor) {
			$value = $nanoseconds / $divisor;
			$unit = $name;
			break;
		}
	}

	// Format the output with appropriate pluralization
	$plural = $value != 1 ? 's' : '';
	$seconds = $nanoseconds / 1e9; // Always show seconds as well if using a different unit

	if ($unit !== 'second') {
		echo sprintf("This code took %.6f seconds to execute, which is %.0f %s%s.", 
			$seconds, $value, $unit, $plural);
	} else {
		echo sprintf("This code took %.6f seconds to execute.", $seconds);
	}
	?></div>
	<div class="footer"><?php echo getCopyrightYears(2024); ?>
	</div>
</body>
</html>