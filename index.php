<?php
// index.php
date_default_timezone_set('America/Chicago');
$start = hrtime(true);

// require_once 'security.php';
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
		$contrast_method = $_POST['contrast_method'] ?? 'wcag';
		$report = generateColorReport($colors, $contrast_method);
		
		$timestamp = date('Ymd-His');
		$method_suffix = $contrast_method === 'apca' ? '-apca' : '-wcag';
		$filename = "color-contrast-report{$method_suffix}-{$timestamp}.html";
		
		header('Content-Type: text/html');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		
		echo $report;
		exit;
	} elseif (!empty($_POST['colors'])) {
		$result = processColorForm($_POST['colors']);
		$parsed_colors = $result['parsed_colors'];
		$excess_colors = $result['excess_colors'];
		$invalid_colors = $result['invalid_colors'];
		$duplicate_colors = $result['duplicate_colors'];
		$semantic_duplicates = $result['semantic_duplicates'];
		$contrast_method = $_POST['contrast_method'] ?? 'wcag';
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Jeff's Color Contrast Analyzer</title>
	<link rel="stylesheet" href="style.css">
	<link rel="apple-touch-icon" sizes="180x180" href="https://contrast.jefftml.com/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="https://contrast.jefftml.com/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="https://contrast.jefftml.com/favicon-16x16.png">
	<link rel="manifest" href="https://contrast.jefftml.com/site.webmanifest">
	<meta name="description" content="Calculate contrast ratios for multiple colors for WCAG and APCA guidelines. Hex, RGB, HSL, and all CSS color types supported. Download reports for offline use for free.">
</head>
<body>
	<input type="checkbox" id="toggle" class="menu-toggle">
	<label for="toggle" class="menu-label">Menu</label>
	<ul class="topnav controlled">
		<li><a class="active" href="https://contrast.jefftml.com/">Home</a></li>
		<li><a href="https://contrast.jefftml.com/help.html">Help</a></li>
	</ul>
	<div class="content">
	<h1>Jeff&rsquo;s Color Contrast Analyzer</h1>
	<form method="post">
		<p>Analyze color contrast between multiple colors, up to <?php echo MAX_COLORS; ?> colors total, in tons of formats. Hex, RGB and RGBA, HSL and HSLA, CSS Named Colors, HSB, HWB, Lab, LCH, Oklab, Oklch, and CMYK are fully supported. CSS syntax is also accepted <code>color: #FFF</code> or <code>background-color: rgb(255, 0, 0);</code>. <a href="https://contrast.jefftml.com/help.html#color-formats">More details and specifications</a> are included in the help documentation.</p>
		<p><code>from</code>, <code>calc()</code>, and <code>color()</code> are not supported. Labels can be added to a color by placing the label before a colon, like <code>link: #2C5491</code></p>
		<label for="colors">Input your colors:<br><textarea name="colors" id="colors" required autocapitalize="off" autocomplete="off" spellcheck="false" placeholder="Enter colors here, 
one color per line"><?= isset($_POST['colors']) ? htmlspecialchars($_POST['colors']) : '' ?></textarea></label>
		
		<div style="margin: 1em 0;">
			<fieldset style="margin: 0; border: 1px solid hsla(0, 0%, 50%, 0.3); padding: 1em; max-width: 400px;">
				<legend style="padding: 0 0.5em;">Contrast Method:</legend>
				<label style="display: block; margin: 0.5em 0;">
					<input type="radio" name="contrast_method" value="wcag" <?= (!isset($contrast_method) || $contrast_method === 'wcag') ? 'checked' : '' ?>> WCAG - current standard
				</label>
				<label style="display: block; margin: 0.5em 0;">
					<input type="radio" name="contrast_method" value="apca" <?= (isset($contrast_method) && $contrast_method === 'apca') ? 'checked' : '' ?>> APCA</strong> - potential standard
				</label>
			</fieldset>
		</div>
		<button type="submit">Calculate Contrast</button>
	</form>
<?php if (!empty($parsed_colors)): ?>
	<div style="margin-top: 1em;"><form method="post">
		<input type="hidden" name="colors" value="<?= htmlspecialchars($_POST['colors']) ?>">
		<input type="hidden" name="contrast_method" value="<?= htmlspecialchars($contrast_method ?? 'wcag') ?>">
		<button type="submit" name="download" value="1">Download Report</button>
	</form></div>
<?php endif;
if (!empty($excess_colors)): ?>
	<div class="error-message">
		<h3>Too Many Colors</h3>
		<p>Only the first <?php echo MAX_COLORS; ?> colors were processed. Please reduce the number of colors in your input.</p>
	</div>
	<br>
<?php endif;
if (!empty($invalid_colors)): ?>
	<div class="error-message">
		<h3>Invalid Colors</h3>
		<p>The following colors could not be parsed.</p>
		<ul class="error-list">
			<?php foreach ($invalid_colors as $color): ?>
			<li><code><?= htmlspecialchars($color) ?></code></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<br>
<?php endif;
if (!empty($duplicate_colors)): ?>
	<div class="warning-message">
		<h3>Duplicate Colors</h3>
		<p>The following colors were entered more than once. Only the first occurrence was processed.</p>
		<ul class="error-list">
			<?php foreach ($duplicate_colors as $color): ?>
			<li><code><?= htmlspecialchars($color) ?></code></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<br>
<?php endif;
if (!empty($semantic_duplicates)): ?>
	<div class="warning-message">
		<h3>Equivalent Colors</h3>
		<p>The following colors represent the same values in different formats. Only the first occurrence was processed.</p>
		<ul class="warning-list">
			<?php foreach ($semantic_duplicates as $group): ?>
				<li>
					Original: <code><?= htmlspecialchars($group['original']) ?></code>
					<br>
					Equivalent formats:
						<?= implode(', ', array_map(function($duplicate) {
							return '<code>' . htmlspecialchars($duplicate) . '</code>';
						}, $group['duplicates'])) ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<br>
<?php endif;
if (!empty($parsed_colors)): 
	// log a usage for our stats.txt
	date_default_timezone_set('America/Chicago');
	$datetime = date('Y-m-d h:i:s A');
	$file = 'stats.txt';
	$value = count($parsed_colors)." colors, ".($contrast_method ?? 'wcag')." method, ";
	// Create the log entry
	$logEntry = $datetime . ", " . $value . PHP_EOL;
	file_put_contents($file, $logEntry, FILE_APPEND);
	
	$current_method = $contrast_method ?? 'wcag';
	?>
	<div class="table-wrapper">
		<h2>Summary of Compatible Color Combinations (<?= strtoupper($current_method) ?>)</h2>
		<table class="checkered">
			<thead>
				<tr>
					<th>Background</th>
					<?php if ($current_method === 'wcag'): ?>
						<th>AAA ≥ 7.0<br>Best</th>
						<th>AA Normal ≥ 4.5<br>Second Best</th>
						<th>AA Large ≥ 3.0<br>Third Best</th>
					<?php else: ?>
						<th>Perfect ≥ 90</th>
						<th>Excellent ≥ 75</th>
						<th>Good ≥ 60</th>
						<th>Fair ≥ 45</th>
					<?php endif; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($parsed_colors as $bg_color => $bg): 
					// Calculate all combinations for this background color
					$combinations = [];
					foreach ($parsed_colors as $fg_color => $fg) {
						if ($fg_color === $bg_color) continue;
						
						if ($current_method === 'wcag') {
							// Calculate luminance with alpha blending
							$fg_lum = getLuminance($fg['rgb'], $fg['alpha'], $bg['rgb']);
							$bg_lum = $bg['luminance'];
							$contrast = getContrastRatio($bg_lum, $fg_lum);
							$level = getWCAGLevel($contrast);
						} else {
							// APCA calculations
							$contrast = getAPCAContrast($bg['rgb'], $fg['rgb'], $bg['alpha'], $fg['alpha']);
							$level = getAPCALevel($contrast);
						}
						
						if (($current_method === 'wcag' && $level !== 'Fail') || 
							($current_method === 'apca' && !str_contains($level, 'Fail'))) {
							$combinations[] = [
								'color' => $fg_color,
								'contrast' => $contrast,
								'level' => $level
							];
						}
					}

					if ($current_method === 'wcag') {
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
					} else {
						// Group by APCA level
						$apca_groups = [
							'Perfect' => [],
							'Excellent' => [],
							'Good' => [],
							'Fair' => []
						];
						
						foreach ($combinations as $combo) {
							$level_key = explode(' - ', $combo['level'])[0];
							if (isset($apca_groups[$level_key])) {
								$apca_groups[$level_key][] = $combo;
							}
						}
						
						// Sort each group by absolute contrast value
						foreach ($apca_groups as &$group) {
							usort($group, function($a, $b) {
								return abs($b['contrast']) <=> abs($a['contrast']);
							});
						}

						$has_valid_combinations = !empty($apca_groups['Perfect']) || 
											   !empty($apca_groups['Excellent']) || 
											   !empty($apca_groups['Good']) || 
											   !empty($apca_groups['Fair']);
					}
				?><tr style="background-color: <?= htmlspecialchars(getCssColor($bg_color)) ?>;">
					<td>
						<?php 
						// Use alpha-aware luminance for text color calculation
						$bg_text_lum = getLuminance($bg['rgb'], $bg['alpha']); ?>
						<span style="color: <?= $bg_text_lum > 0.5 ? '#000000' : '#FFFFFF' ?>"><?= htmlspecialchars(getCleanColorName($bg_color)) ?></span>
					</td>
			<?php if ($has_valid_combinations): ?>
				<?php if ($current_method === 'wcag'): ?>
					<?php foreach (['AAA', 'AA', 'AA Large'] as $level): ?>
						<td><?php foreach ($wcag_groups[$level] as $combo): ?>
							<div style="color: <?= htmlspecialchars(getCssColor($combo['color'])) ?>;"><?= htmlspecialchars($combo['color']) ?>&nbsp;(Ratio:&nbsp;<?= number_format($combo['contrast'], 2) ?>)</div>
						<?php endforeach; ?></td>
					<?php endforeach; ?>
				<?php else: ?>
					<?php foreach (['Perfect', 'Excellent', 'Good', 'Fair'] as $level): ?>
						<td><?php foreach ($apca_groups[$level] as $combo): ?>
							<div style="color: <?= htmlspecialchars(getCssColor($combo['color'])) ?>;"><?= htmlspecialchars($combo['color']) ?>&nbsp;(Lc:&nbsp;<?= number_format($combo['contrast'], 1) ?>)</div>
						<?php endforeach; ?></td>
					<?php endforeach; ?>
				<?php endif; ?>
			<?php else: ?>
				<td colspan="<?= $current_method === 'wcag' ? '3' : '4' ?>" style="text-align: center; color: <?= $bg_text_lum > 0.5 ? '#000000' : '#FFFFFF' ?>">
					<?php if ($current_method === 'wcag'): ?>
						No valid color combinations found (all contrast ratios below 3.0)
					<?php else: ?>
						No valid color combinations found (all APCA values below 45)
					<?php endif; ?>
				</td>
			<?php endif; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="table-wrapper">
	<h2>Complete Contrast Grid (<?= strtoupper($current_method) ?>)</h2>
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
			if ($current_method === 'wcag') {
				// Calculate luminance with alpha blending
				$fg_lum = getLuminance($fg_data['rgb'], $fg_data['alpha'], $bg_data['rgb']);
				$bg_lum = $bg_data['luminance'];
				$contrast = getContrastRatio($bg_lum, $fg_lum);
				$level = getWCAGLevel($contrast);
				$display_value = number_format($contrast, 2);
			} else {
				// APCA calculations
				$contrast = getAPCAContrast($bg_data['rgb'], $fg_data['rgb'], $bg_data['alpha'], $fg_data['alpha']);
				$level = getAPCALevel($contrast);
				$display_value = number_format($contrast, 1);
			}
		?>
			<td style="background-color: <?= htmlspecialchars(getCssColor($bg_color)) ?>;">
				<div class="sample-text" style="color: <?= htmlspecialchars(getCssColor($fg_color)) ?>;">Sample
				<div style="font-size: 0.8em;">
					<?php if ($current_method === 'wcag'): ?>
						<?= $display_value ?><br><?= $level ?>
					<?php else: ?>
						Lc&nbsp;<?= $display_value ?><br><?= explode(' - ', $level)[0] ?>
					<?php endif; ?>
				</div>
				</div>
			</td>
	<?php endforeach; ?>
	</tr>
	<?php endforeach; ?>
	</table>
</div>
<?php endif; ?>
	<div class="timer"><?php 
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
	<script src="https://contrast.jefftml.com/tablewidth.js"></script>
	</div>
</body>
</html>