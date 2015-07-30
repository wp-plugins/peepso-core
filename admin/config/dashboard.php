<?php
	$size = number_format((100 / $tab_count) - 1, 2);
	if ($size > 15)
		$size = 15;
?>

<div id="peepso" class="wrap">
	<h2><img src="<?php echo PeepSo::get_asset('images/logo-icon.png'); ?>" height="30" width="30" /></h2>
	<div class="row-fluid">
		<div class="dashtab">
		<?php
			foreach ($tabs as $section => $data) {
				echo	'<div class="infobox infobox-blue tab-', $section, ' infobox-dark" style="width:', $size, '%">', PHP_EOL;

				if ('/' === substr($data['slug'], 0, 1))
					echo	'<a href="', get_admin_url(NULL, $data['slug']), '">', PHP_EOL;
				else
					echo	'<a href="admin.php?page=', $slug, '&section=', $data['slug'], '">', PHP_EOL;

				echo			'<div class="infobox-icon dashicons dashicons-', $data['icon'], '"></div>', PHP_EOL;
				echo			'<div class="infobox-caption">', $data['menu'], '</div>', PHP_EOL;
				echo	'</a>', PHP_EOL;
				echo	'</div>';
			}
		?>
		</div>
	</div>
</div>