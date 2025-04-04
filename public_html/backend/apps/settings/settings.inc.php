<?php

	if (empty($_GET['page']) || !is_numeric($_GET['page']) || $_GET['page'] < 1) {
		$_GET['page'] = 1;
	}

	document::$title[] = language::translate('title_settings', 'Settings');

	breadcrumbs::add(language::translate('title_settings', 'Settings'), document::ilink());

	if (isset($_POST['save'])) {

		try {

			foreach (array_keys($_POST['settings']) as $key) {

				$setting = database::query(
					"select * from ". DB_TABLE_PREFIX ."settings
					where `key` = '". database::input($key) ."'
					limit 1;"
				)->fetch();

				if (!$setting) {
					throw new Exception(language::translate('error_setting_key_does_not_exist', 'The settings key does not exist'));
				}

				if (!empty($setting['required']) && empty($_POST['settings'][$key])) {
					throw new Exception(language::translate('error_cannot_set_empty_value_for_setting', 'You cannot set an empty value for this setting'));
				}

				switch ($setting['datatype']) {

					case 'boolean':
						$value = (int)$_POST['settings'][$key];
						break;

					case 'csv':
						$value = implode(',', array_map(function($value){
							return preg_match('#", \R#', $value) ? '"' . str_replace('"', '""', $value) . '"' : $value;
						}, $_POST['settings'][$key]));
						break;

					case 'array':
						$value = json_encode($_POST['settings'][$key], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
						break;

					case 'json':
						$value = (string)$_POST['settings'][$key];
						break;

					case 'number':
						$value = (int)$_POST['settings'][$key];
						break;

					case 'decimal':
						$value = (float)$_POST['settings'][$key];
						break;
				}

				database::query(
					"update ". DB_TABLE_PREFIX ."settings
					set `value` = '". database::input($value) ."',
						date_updated = '". date('Y-m-d H:i:s') ."'
					where `key` = '". database::input($key) ."'
					limit 1;"
				);

				// Specific operations
				switch ($key) {
					case 'site_timezone':
						$file = 'storage://config.inc.php';
						$contents = file_get_contents($file);
						$contents = preg_replace('#ini_set\(\'date.timezone\'\, [^\)]+\);#', 'ini_set(\'date.timezone\', \''. addcslashes($value)  .'\');', $contents);
						file_put_contents($file, $contents);
						break;
				}
			}

			notices::add('success', language::translate('success_changes_saved', 'Changes saved'));
			header('Location: '. document::ilink(null, [], true, ['action']));
			exit;

		} catch (Exception $e) {
			notices::add('success', $e->getMessage());
		}
	}

	$settings_group = database::query(
		"select * from ". DB_TABLE_PREFIX ."settings_groups
		where `key` = '". database::input(__DOC__) ."'
		order by priority, `key`
		limit 1;"
	)->fetch();

	if (!$settings_group) {
		notices::add('errors', 'Invalid settings group ('. __DOC__ .')');
		return;
	}

	// Table Rows, Total Number of Rows, Total Number of Pages
	$settings = database::query(
		"select * from ". DB_TABLE_PREFIX ."settings
		where `group_key` = '". database::input($settings_group['key']) ."'
		order by priority, `key` asc;"
	)->fetch_page(function($setting){

		// Set Display Value
		switch (true) {

			case (preg_match('#^password#', $setting['function'])):
				$setting['display_value'] = '****************';
				break;

				case (preg_match('#^order_status$#', $setting['function'])):
				$setting['display_value'] = $setting['value'] ? reference::order_status($setting['value'])->name : '';
				break;

				case (preg_match('#^page$#', $setting['function'])):
				$setting['display_value'] = $setting['value'] ? reference::page($setting['value'])->title : '';
				break;

			case (preg_match('#^regional_#', $setting['function'])):
				$setting['value'] = !empty($setting['value']) ? json_decode($setting['value'], true) : [];
				$setting['display_value'] = isset($setting['value'][language::$selected['code']]) ? $setting['value'][language::$selected['code']] : null;
				break;

				case (preg_match('#^toggle$#', $setting['function'])):
				if (in_array($setting['value'], ['1', 'active', 'enabled', 'on', 'true', 'yes'])) {
					$setting['display_value'] = language::translate('title_true', 'True');
				} else if (in_array(($setting['value']), ['', '0', 'inactive', 'disabled', 'off', 'false', 'no'])) {
					$setting['display_value'] = language::translate('title_false', 'False');
				}
				break;

			default:

				switch ($setting['datatype']) {

					case 'array':
					case 'json':
						$setting['display_value'] = json_encode($setting['value'], true);
						break;

					default:
						$setting['display_value'] = $setting['value'];
						break;
				}

				break;
		}

		// Set HTTP POST Value
		switch ($setting['datatype']) {

			case 'boolean':
				$_POST['settings'][$setting['key']] = !empty($setting['value']) ? '1' : '0';
				break;

			case 'csv':
				$_POST['settings'][$setting['key']] = str_getcsv($setting['value']);
				break;

			case 'array':
				$_POST['settings'][$setting['key']] = (array)$setting['value'];
				break;

			case 'json':
				$_POST['settings'][$setting['key']] = json_decode($setting['value'], true);
				break;

			case 'number':
				$_POST['settings'][$setting['key']] = (int)$setting['value'];
				break;

			case 'decimal':
				$_POST['settings'][$setting['key']] = (float)$setting['value'];
				break;

			default:
				$_POST['settings'][$setting['key']] = (string)$setting['value'];
				break;
		}

		return $setting;

	}, null, $_GET['page'], null, $num_rows, $num_pages);

?>
<div class="card">
	<div class="card-header">
		<div class="card-title">
			<?php echo $app_icon; ?> <?php echo language::translate('title_settings', 'Settings').' &ndash; '.$settings_group['name']; ?>
		</div>
	</div>

	<?php echo functions::form_begin('settings_form', 'post'); ?>

		<table class="table data-table">
			<thead>
				<tr>
					<th style="width: 35%;"><?php echo language::translate('title_key', 'Key'); ?></th>
					<th><?php echo language::translate('title_value', 'Value'); ?></th>
					<th></th>
				</tr>
			</thead>

			<tbody>
				<?php foreach ($settings as $setting) { ?>
				<?php if (isset($_GET['action']) && $_GET['action'] == 'edit' && $_GET['key'] == $setting['key']) { ?>
				<tr>
					<td>
						<strong><?php echo language::translate('settings_key:title_'.$setting['key'], $setting['title']); ?></strong><br>
						<?php echo language::translate('settings_key:description_'.$setting['key'], $setting['description']); ?>
					</td>
					<td><?php echo functions::form_function('settings['.$setting['key'].']', $setting['function'], true); ?></td>
					<td class="text-end">
						<?php echo functions::form_button_predefined('save'); ?>
						<?php echo functions::form_button_predefined('cancel'); ?>
					</td>
				</tr>
				<?php } else { ?>
				<tr>
					<td class="text-start"><a class="link" href="<?php echo document::href_ilink(null, ['action' => 'edit', 'key' => $setting['key']]); ?>" title="<?php echo language::translate('title_edit', 'Edit'); ?>"><?php echo language::translate('settings_key:title_'.$setting['key'], $setting['title']); ?></a></td>
					<td style="white-space: normal;">
						<div style="max-height: 200px; overflow-y: auto;" title="<?php echo functions::escape_html(language::translate('settings_key:description_'.$setting['key'], $setting['description'])); ?>">
							<?php echo nl2br($setting['display_value'], false); ?>
						</div>
					</td>
					<td class="text-end"><a class="btn btn-default btn-sm" href="<?php echo document::href_ilink(null, ['action' => 'edit', 'key' => $setting['key']]); ?>" title="<?php echo language::translate('title_edit', 'Edit'); ?>"><?php echo functions::draw_fonticon('edit'); ?></a></td>
				</tr>
				<?php } ?>
				<?php } ?>
			</tbody>
		</table>

	<?php echo functions::form_end(); ?>

	<?php if ($num_pages > 1) { ?>
	<div class="card-footer">
		<?php echo functions::draw_pagination($num_pages); ?>
	</div>
	<?php } ?>
</div>

<script>
	$(':input[name="settings[site_zone_code]"]:disabled').prop('disabled', false);
	$(':input[name="settings[default_zone_code]"]:disabled').prop('disabled', false);
</script>