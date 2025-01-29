<?php
// report_template.php
// This template expects $parsed_colors to be available from the calling context
if (!isset($parsed_colors)) {
	die('Error: No color data provided');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Jeff's Color Contrast Analyzer Report - <?= date('Y-m-d H:i:s') ?></title>
	<style>
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
					$combinations = [];
					foreach ($parsed_colors as $fg_color => $fg) {
						if ($fg_color === $bg_color) continue;
						
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

					$wcag_groups = [
						'AAA' => [],
						'AA' => [],
						'AA Large' => []
					];
					
					foreach ($combinations as $combo) {
						$wcag_groups[$combo['level']][] = $combo;
					}
					
					foreach ($wcag_groups as &$group) {
						usort($group, function($a, $b) {
							return $b['contrast'] <=> $a['contrast'];
						});
					}

					$has_valid_combinations = !empty($wcag_groups['AAA']) || 
										   !empty($wcag_groups['AA']) || 
										   !empty($wcag_groups['AA Large']);
				?>
				<tr style="background-color: <?= htmlspecialchars(getCssColor($bg_color)) ?>;">
					<td>
						<?php 
						$bg_text_lum = getLuminance($bg['rgb'], $bg['alpha']);
						?>
						<span style="color: <?= $bg_text_lum > 0.5 ? '#000000' : '#FFFFFF' ?>">
							<?= htmlspecialchars(getCleanColorName($bg_color)) ?><br>
						</span>
					</td>
					<?php if ($has_valid_combinations): ?>
						<?php foreach (['AAA', 'AA', 'AA Large'] as $level): ?>
							<td>
								<?php foreach ($wcag_groups[$level] as $combo): ?>
									<div style="color: <?= htmlspecialchars(getCssColor($combo['color'])) ?>;">
										<?= htmlspecialchars($combo['color']) ?> 
										(Ratio: <?= number_format($combo['contrast'], 2) ?>)
									</div>
								<?php endforeach; ?>
							</td>
						<?php endforeach; ?>
					<?php else: ?>
						<td colspan="3" style="text-align: center; color: <?= $bg_text_lum > 0.5 ? '#000000' : '#FFFFFF' ?>">
							No valid color combinations found (all contrast ratios below 3.0)
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
						$fg_lum = getLuminance($fg_data['rgb'], $fg_data['alpha'], $bg_data['rgb']);
						$bg_lum = $bg_data['luminance'];
						$contrast = getContrastRatio($bg_lum, $fg_lum);
						$wcag_level = getWCAGLevel($contrast);
					?>
						<td style="background-color: <?= htmlspecialchars(getCssColor($bg_color)) ?>;">
							<div class="sample-text" style="color: <?= htmlspecialchars(getCssColor($fg_color)) ?>;">
								Sample
								<div style="font-size: 0.8em;">
									<?= number_format($contrast, 2) ?><br>
									<?= $wcag_level ?>
								</div>
							</div>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</table>
	<?php endif; ?>
	<div class="footer">
		<?php
		echo getCopyrightYears(2024);
		?>
	</div>
</body>
</html>