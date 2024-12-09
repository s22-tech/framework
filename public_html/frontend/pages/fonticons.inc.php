<?php

	/*!
	 * If you would like to maintain visual changes in a separate file, create the following template file for your HTML:
	 *
	 *   ~/frontend/template/pages/fonticons.inc.php
	 */

	document::$snippets['head_tags']['fonticons'] = '<link rel="stylesheet" href="'. document::href_rlink('app://assets/fonticons/fonticons.css') .'">';

	document::$title[] = language::translate('title_font_icons', 'Font Icons');
	document::$description = language::translate('meta_description:font_icons', '');

	breadcrumbs::add(language::translate('title_font_icons', 'Font Icons'), document::ilink('fonticons'));

	$_page = new ent_view('app://frontend/template/pages/font_icons.inc.php');

	$font_icons = [];

	foreach (file('app://assets/fonticons/fonticons.css') as $line) {
		if (preg_match('#^\.(icon-[a-z0-9-]+):before\s*{#', $line, $matches)) {
			$font_icons[] =  $matches[1];
		}
	}

	$_page->snippets['font_icons'] = $font_icons;

	if (is_file($_page->view)) {
		echo $_page->render();
		return;
	} else {
		extract($_page->snippets);
	}
?>
<style>
	.font-icons {
		columns: 200px auto;
		gap: 1em;
		margin-bottom: 2em;
	}

	.icon {
		display: flex;
		flex-direction: row;
		align-items: center;
		margin-bottom: 1em;
	}

	.icon [class^="icon-"] {
		aspect-ratio: 1;
		border: 1px solid var(--default-border-color);
		border-radius: var(--border-radius);
		padding: 1em;
		margin-right: 1em;
	}

	.icon .name {
		margin-top: 0.5em;
		font-family: monospace;
	}
</style>

<main class="container">
	<div class="card">
		<div class="card-header">
			<div class="card-title"><?php echo language::translate('title_font_icons', 'Font Icons'); ?></div>
			<p><?php echo language::translate('description_font_icons', 'Below is a list of all available font icons.'); ?></p>
		</div>

		<div class="card-body">
			<div class="font-icons">
				<?php foreach ($font_icons as $icon) { ?>
				<div class="icon">
					<?php echo functions::draw_fonticon($icon); ?>
					<div class="name">
						<?php echo $icon; ?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>