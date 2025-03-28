<?php

	switch (__DOC__) {

		case 'jobs':

			$title = language::translate('title_job_modules', 'Job Modules');
			$files = functions::file_search('app://includes/modules/jobs/*.inc.php');
			$mod_class = new mod_jobs();
			$type = 'job';
			$edit_doc = 'edit_job';
			break;

		default:
			trigger_error('Unknown module type ('. __DOC__ .')', E_USER_ERROR);
	}

	if (isset($_POST['enable']) || isset($_POST['disable'])) {

		try {

			if (empty($_POST['modules'])) {
				throw new Exception(language::translate('error_must_select_modules', 'You must select modules'));
			}

			foreach ($_POST['modules'] as $module_id) {
				$module = new ent_module($module_id);
				$module->data['settings']['status'] = !empty($_POST['enable']) ? 1 : 0;
				$module->save();
			}

			notices::add('success', language::translate('success_changes_saved', 'Changes saved'));
			header('Location: '. document::link());
			exit;

		} catch (Exception $e) {
			notices::add('errors', $e->getMessage());
		}
	}

	document::$title[] = $title;

	breadcrumbs::add(language::translate('title_modules', 'Modules'));
	breadcrumbs::add($title, document::ilink());

	// Installed Modules
	$installed_modules = database::query(
		"select module_id from ". DB_TABLE_PREFIX ."modules
		where type = '". database::input($type) ."';"
	)->fetch_all('module_id');

	// Table Rows
	$modules = [];

	if (is_array($mod_class->modules) && count($mod_class->modules)) {
		foreach ($mod_class->modules as $module) {
			$modules[] = [
				'id' => $module->id,
				'status' => $module->status,
				'name' => $module->name,
				'version' => $module->version,
				'priority' => $module->priority,
				'author' => $module->author,
				'website' => $module->website,
				'installed' => true,
			];
		}
	}

	foreach ($files as $file) {
		$module_id = substr(basename($file), 0, -8);
		if (in_array($module_id, $installed_modules)) continue;

		$module = new $module_id;

		$modules[] = [
			'id' => $module_id,
			'status' => null,
			'name' => $module->name,
			'version' => $module->version,
			'priority' => null,
			'author' => $module->author,
			'website' => $module->website,
			'installed' => false,
		];
	}

	// Number of Rows
	$num_rows = count($modules);
?>
<div class="card">
	<div class="card-header">
		<div class="card-title">
			<?php echo $app_icon; ?> <?php echo $title; ?>
		</div>
	</div>

	<div class="card-action">

		<?php if ($type == 'job') { ?>
		<button id="cron-example" class="btn btn-default" type="button" style="margin-right: 1em;">
			<?php echo functions::draw_fonticon('icon-info'); ?> <?php echo language::translate('title_cron_job', 'Cron Job'); ?>
		</button>
		<?php } ?>

	</div>

	<?php echo functions::form_begin('modules_form', 'post'); ?>

		<table class="table data-table">
			<thead>
				<tr>
					<th><?php echo functions::draw_fonticon('icon-square-check', 'data-toggle="checkbox-toggle"'); ?></th>
					<th></th>
					<th class="main"><?php echo language::translate('title_name', 'Name'); ?></th>
					<th></th>
					<th><?php echo language::translate('title_id', 'ID'); ?></th>
					<th><?php echo language::translate('title_version', 'Version'); ?></th>
					<th><?php echo language::translate('title_developer', 'Developer'); ?></th>
					<th class="text-center"><?php echo language::translate('title_priority', 'Priority'); ?></th>
					<th></th>
				</tr>
			</thead>

			<tbody>
				<?php foreach ($modules as $module) { ?>
				<?php if (!empty($module['installed'])) { ?>
				<tr class="<?php echo empty($module['status']) ? 'semi-transparent' : ''; ?>">
					<td><?php echo functions::form_checkbox('modules[]', $module['id']); ?></td>
					<td><?php echo functions::draw_fonticon($module['status'] ? 'on' : 'off'); ?></td>
					<td><a class="link" href="<?php echo document::href_ilink(__APP__.'/edit_'.$type, ['module_id' => $module['id']]); ?>"><?php echo $module['name']; ?></a></td>
					<?php if (__DOC__ == 'jobs' && !empty($module['status'])) { ?>
					<td class="text-center">
						<a class="btn btn-default btn-sm" href="<?php echo document::href_ilink(__APP__.'/run_job', ['module_id' => $module['id']]); ?>" target="_blank">
							<strong><?php echo language::translate('title_run_now', 'Run Now'); ?></strong>
						</a>
					</td>
					<?php } else { ?>
					<td class="text-center"></td>
					<?php } ?>
					<td><?php echo $module['id']; ?></td>
					<td class="text-end"><?php echo $module['version']; ?></td>
					<td><?php echo !empty($module['website']) ? '<a href="'. functions::escape_attr($module['website']) .'" target="_blank">'. $module['author'] .'</a>' : $module['author']; ?></td>
					<td class="text-center"><?php echo $module['priority']; ?></td>
					<td class="text-end"><a class="btn btn-default btn-sm" href="<?php echo document::href_ilink(__APP__.'/'.$edit_doc, ['module_id' => $module['id']]); ?>" title="<?php echo language::translate('title_edit', 'Edit'); ?>"><?php echo functions::draw_fonticon('edit'); ?></a></td>
				</tr>
				<?php } else { ?>
				<tr class="semi-transparent">
					<td></td>
					<td></td>
					<td><?php echo $module['name']; ?></td>
					<td class="text-center"></td>
					<td><?php echo $module['id']; ?></td>
					<td class="text-end"><?php echo $module['version']; ?></td>
					<td><?php echo !empty($module['website']) ? '<a href="'. functions::escape_attr($module['website']) .'" target="_blank">'. $module['author'] .'</a>' : $module['author']; ?></td>
					<td class="text-center">-</td>
					<td class="text-end"><a href="<?php echo document::href_ilink(__APP__.'/edit_'.$type, ['module_id' => $module['id']]); ?>"><?php echo functions::draw_fonticon('add'); ?> <?php echo language::translate('title_install', 'Install'); ?></a></td>
				</tr>
				<?php } ?>
				<?php } ?>
			</tbody>

			<tfoot>
				<tr>
					<td colspan="9"><?php echo language::translate('title_modules', 'Modules'); ?>: <?php echo language::number_format($num_rows); ?></td>
				</tr>
			</tfoot>
		</table>

		<div class="card-body">
			<fieldset id="actions" disabled>
				<legend><?php echo language::translate('text_with_selected', 'With selected'); ?>:</legend>

				<div class="btn-group">
					<?php echo functions::form_button('enable', language::translate('title_enable', 'Enable'), 'submit', '', 'on'); ?>
					<?php echo functions::form_button('disable', language::translate('title_disable', 'Disable'), 'submit', '', 'off'); ?>
				</div>
			</fieldset>
		</div>

	<?php echo functions::form_end(); ?>
</div>

<script>
	$('#cron-example').on('click', function(){
		prompt("<?php echo language::translate('title_cron_job_configuration', 'Cron Job Configuration'); ?>", "*/5 * * * * curl --silent <?php echo document::ilink('f:push_jobs'); ?> &>/dev/null");
	});

	$('.data-table :checkbox').change(function() {
		$('#actions').prop('disabled', !$('.data-table :checked').length);
	}).first().trigger('change');
</script>