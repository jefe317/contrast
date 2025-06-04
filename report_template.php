<?php
// report_template.php
// This template expects $parsed_colors and $contrast_method to be available from the calling context
if (!isset($parsed_colors)) {
	die('Error: No color data provided');
}

$current_method = $contrast_method ?? 'wcag';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Jeff's Color Contrast Analyzer Report (<?= $current_method === 'both' ? 'Both' : strtoupper($current_method) ?>) - <?= date('Y-m-d H:i:s') ?></title>
	<style>
		:root { color-scheme: light dark; }
		body { font-family: Arial, sans-serif; margin: 20px; }
		table { border-collapse: collapse; margin: 20px 0; }
		th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; }
		td div { padding: 0 0.5em 0.5em 0; }
		.checkered { background: conic-gradient(hsla(0, 0%, 50%, 20%) 90deg, transparent 90deg 180deg, hsla(0, 0%, 50%, 20%) 180deg 270deg, transparent 270deg); background-repeat: repeat; background-size: 40px 40px; }
		.footer { padding-top: 1em; }
	</style>
</head>
<body>
	<h1>Jeff's Color Contrast Analyzer</h1>
	<p>Generated on: <?= date('Y-m-d H:i:s T') ?> from <a href="https://contrast.jefftml.com/">contrast.jefftml.com</a></p>
	
	<?php if (!empty($parsed_colors)): ?>
		<h2>Summary of Compatible Color Combinations (<?= $current_method === 'both' ? 'Both' : strtoupper($current_method) ?>)</h2>
		<table class="checkered">
			<thead>
				<tr>
					<th>Background</th>
					<?php if ($current_method === 'wcag'): ?>
						<th>AAA ≥ 7.0<br>Best</th>
						<th>AA Normal ≥ 4.5<br>Second Best</th>
						<th>AA Large ≥ 3.0<br>Third Best</th>
					<?php elseif ($current_method === 'apca'): ?>
						<th>Perfect ≥ 90</th>
						<th>Excellent ≥ 75</th>
						<th>Good ≥ 60</th>
						<th>Fair ≥ 45</th>
					<?php else: // both ?>
						<th>Perfect<br>WCAG&nbsp;≥10&nbsp;&<br>APCA&nbsp;≥90</th>
						<th>Excellent<br>WCAG&nbsp;≥7&nbsp;&<br>APCA&nbsp;≥75</th>
						<th>Good<br>WCAG&nbsp;≥4.5&nbsp;&<br>APCA&nbsp;≥60</th>
						<th>Fair<br>WCAG&nbsp;≥3&nbsp;&<br>APCA&nbsp;≥45</th>
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
							
							if ($level !== 'Fail') {
								$combinations[] = [
									'color' => $fg_color,
									'contrast' => $contrast,
									'level' => $level
								];
							}
						} elseif ($current_method === 'apca') {
							// APCA calculations
							$contrast = getAPCAContrast($bg['rgb'], $fg['rgb'], $bg['alpha'], $fg['alpha']);
							$level = getAPCALevel($contrast);
							
							if (!str_contains($level, 'Fail')) {
								$combinations[] = [
									'color' => $fg_color,
									'contrast' => $contrast,
									'level' => $level
								];
							}
						} else { // both
							// Calculate both WCAG and APCA
							$fg_lum = getLuminance($fg['rgb'], $fg['alpha'], $bg['rgb']);
							$bg_lum = $bg['luminance'];
							$wcag_contrast = getContrastRatio($bg_lum, $fg_lum);
							$wcag_level = getWCAGLevel($wcag_contrast);
							
							$apca_contrast = getAPCAContrast($bg['rgb'], $fg['rgb'], $bg['alpha'], $fg['alpha']);
							$apca_level = getAPCALevel($apca_contrast);
							
							$combined_level = getCombinedLevel($wcag_contrast, $apca_contrast);
							
							if ($combined_level !== 'Fail') {
								$combinations[] = [
									'color' => $fg_color,
									'wcag_contrast' => $wcag_contrast,
									'apca_contrast' => $apca_contrast,
									'level' => $combined_level
								];
							}
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

						$has_valid_combinations = !empty($wcag_groups['AAA']) || 
											   !empty($wcag_groups['AA']) || 
											   !empty($wcag_groups['AA Large']);
					} elseif ($current_method === 'apca') {
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
					} else { // both
						// Group by combined level
						$combined_groups = [
							'Perfect' => [],
							'Excellent' => [],
							'Good' => [],
							'Fair' => []
						];
						
						foreach ($combinations as $combo) {
							if (isset($combined_groups[$combo['level']])) {
								$combined_groups[$combo['level']][] = $combo;
							}
						}
						
						// Sort each group by WCAG contrast ratio
						foreach ($combined_groups as &$group) {
							usort($group, function($a, $b) {
								return $b['wcag_contrast'] <=> $a['wcag_contrast'];
							});
						}

						$has_valid_combinations = !empty($combined_groups['Perfect']) || 
												!empty($combined_groups['Excellent']) || 
												!empty($combined_groups['Good']) || 
												!empty($combined_groups['Fair']);
					}
				?>
				<tr style="background-color: <?= htmlspecialchars(getCssColor($bg_color)) ?>;">
					<td>
						<?php 
						// Use alpha-aware luminance for text color calculation
						$bg_text_lum = getLuminance($bg['rgb'], $bg['alpha']); ?>
						<span style="color: <?= $bg_text_lum > 0.5 ? '#000000' : '#FFFFFF' ?>">
							<?= htmlspecialchars(getCleanColorName($bg_color)) ?>
						</span>
					</td>
					<?php if ($has_valid_combinations): ?>
						<?php if ($current_method === 'wcag'): ?>
							<?php foreach (['AAA', 'AA', 'AA Large'] as $level): ?>
								<td><?php foreach ($wcag_groups[$level] as $combo): ?>
									<div style="color: <?= htmlspecialchars(getCssColor($combo['color'])) ?>;"><?= htmlspecialchars($combo['color']) ?>&nbsp;(Ratio:&nbsp;<?= number_format($combo['contrast'], 2) ?>)</div>
								<?php endforeach; ?></td>
							<?php endforeach; ?>
						<?php elseif ($current_method === 'apca'): ?>
							<?php foreach (['Perfect', 'Excellent', 'Good', 'Fair'] as $level): ?>
								<td><?php foreach ($apca_groups[$level] as $combo): ?>
									<div style="color: <?= htmlspecialchars(getCssColor($combo['color'])) ?>;"><?= htmlspecialchars($combo['color']) ?>&nbsp;(Lc:&nbsp;<?= number_format($combo['contrast'], 1) ?>)</div>
								<?php endforeach; ?></td>
							<?php endforeach; ?>
						<?php else: // both ?>
							<?php foreach (['Perfect', 'Excellent', 'Good', 'Fair'] as $level): ?>
								<td><?php foreach ($combined_groups[$level] as $combo): ?>
									<div style="color: <?= htmlspecialchars(getCssColor($combo['color'])) ?>;"><?= htmlspecialchars($combo['color']) ?>&nbsp;(<?= number_format($combo['wcag_contrast'], 2) ?>)&nbsp;(Lc&nbsp;<?= number_format($combo['apca_contrast'], 1) ?>)</div>
								<?php endforeach; ?></td>
							<?php endforeach; ?>
						<?php endif; ?>
					<?php else: ?>
						<td colspan="<?= $current_method === 'wcag' ? '3' : '4' ?>" style="text-align: center; color: <?= $bg_text_lum > 0.5 ? '#000000' : '#FFFFFF' ?>">
							<?php if ($current_method === 'wcag'): ?>
								No valid color combinations found (all contrast ratios below 3.0)
							<?php elseif ($current_method === 'apca'): ?>
								No valid color combinations found (all APCA values below 45)
							<?php else: ?>
								No valid color combinations found (failed both WCAG and APCA thresholds)
							<?php endif; ?>
						</td>
					<?php endif; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2>Complete Contrast Grid (<?= $current_method === 'both' ? 'Both' : strtoupper($current_method) ?>)</h2>
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
						} elseif ($current_method === 'apca') {
							// APCA calculations
							$contrast = getAPCAContrast($bg_data['rgb'], $fg_data['rgb'], $bg_data['alpha'], $fg_data['alpha']);
							$level = getAPCALevel($contrast);
							$display_value = number_format($contrast, 1);
						} else { // both
							// Calculate both WCAG and APCA
							$fg_lum = getLuminance($fg_data['rgb'], $fg_data['alpha'], $bg_data['rgb']);
							$bg_lum = $bg_data['luminance'];
							$wcag_contrast = getContrastRatio($bg_lum, $fg_lum);
							$wcag_level = getWCAGLevel($wcag_contrast);
							
							$apca_contrast = getAPCAContrast($bg_data['rgb'], $fg_data['rgb'], $bg_data['alpha'], $fg_data['alpha']);
							$apca_level = getAPCALevel($apca_contrast);
							
							$combined_level = getCombinedLevel($wcag_contrast, $apca_contrast);
						}
					?>
						<td style="background-color: <?= htmlspecialchars(getCssColor($bg_color)) ?>;">
							<div class="sample-text" style="color: <?= htmlspecialchars(getCssColor($fg_color)) ?>;">
								Sample
								<div style="font-size: 0.8em;">
									<?php if ($current_method === 'wcag'): ?>
										<?= $display_value ?><br><?= $level ?>
									<?php elseif ($current_method === 'apca'): ?>
										Lc&nbsp;<?= $display_value ?><br><?= explode(' - ', $level)[0] ?>
									<?php else: // both ?>
										(<?= number_format($wcag_contrast, 2) ?>) (Lc&nbsp;<?= number_format($apca_contrast, 1) ?>)<br><?= $combined_level ?>
									<?php endif; ?>
								</div>
							</div>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</table>
	<?php endif; ?>
	<div class="footer">
		<?php echo getCopyrightYears(2024); ?>
	</div>
</body>
</html>