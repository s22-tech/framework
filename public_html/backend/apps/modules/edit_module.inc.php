<?php

	if (empty($_GET['module_id'])) {
		die('Unknown module id');
	}

	$module_id = basename($_GET['module_id']);

	switch (true) {

		case (preg_match('#^job_#', $module_id)):
			$type = 'job';
			$return_doc = 'jobs';
			break;

		default:
			throw new Error('Unknown module type');
	}

	$module = new ent_module($module_id);
	$object = new $module_id();

	document::$title[] = !empty($module->data['id']) ? language::translate('title_edit_module', 'Edit Module') : language::translate('title_install_module', 'Install Module');

	breadcrumbs::add(language::translate('title_modules', 'Modules'));
	breadcrumbs::add(!empty($module->data['id']) ? language::translate('title_edit_module', 'Edit Module') : language::translate('title_install_module', 'Install Module'), document::ilink());

	if (!$_POST) {
		$_POST['settings'] = $module->data['settings'];
	}

	if (isset($_POST['save'])) {

		try {

			foreach (array_keys($module->data['settings']) as $key) {
				if (in_array($key, ['id', 'date_updated', 'date_created'])) continue;
				$module->data['settings'][$key] = isset($_POST['settings'][$key]) ? $_POST['settings'][$key] : '';
			}

			$module->save();

			notices::add('success', language::translate('success_changes_saved', 'Changes saved'));
			header('Location: '. document::ilink(__APP__.'/'.$return_doc));
			exit;

		} catch (Exception $e) {
			notices::add('errors', $e->getMessage());
		}
	}

	if (isset($_POST['uninstall'])) {

		try {
			$module->delete();

			notices::add('success', language::translate('success_changes_saved', 'Changes saved'));
			header('Location: '. document::ilink(__APP__.'/'.$return_doc));
			exit;

		} catch (Exception $e) {
			notices::add('errors', $e->getMessage());
		}
	}

	if (!$_POST && !empty($module->data['id'])) {
		notices::add('notices', language::translate('text_make_changes_necessary_to_install', 'Make any changes necessary to continue installation'));
	}

?>
<style>
#box-last-log pre {
	max-height: 800px;
	overflow-y: auto;
}
</style>

<div class="card">
	<div class="card-header">
		<div class="card-title">
			<?php echo $app_icon; ?> <?php echo !empty($module->data['id']) ? language::translate('title_edit_module', 'Edit Module') : language::translate('title_install_module', 'Install Module'); ?>
		</div>
	</div>

	<div class="card-body">
		<h2><?php echo $object->name; ?></h2>

		<?php echo !empty($object->author) ? '<p><strong>'. language::translate('title_developed_by', 'Developed by') .'</strong> <a href="'. $object->website .'" target="_blank">'. $object->author .'</a></p>' : false; ?>

		<?php echo !empty($object->description) ? '<p style="max-width: 960px;">'. $object->description .'</p>' : ''; ?>

		<?php echo functions::form_begin('module_form', 'post', false, false, 'autocomplete="off" style="max-width: 960px;"'); ?>

			<table class="table">
				<tbody>
					<?php foreach ($object->settings() as $setting) { ?>
					<tr>
						<td style="width: 50%">
							<strong><?php echo $setting['title']; ?></strong>
							<?php echo !empty($setting['description']) ? '<div>'. $setting['description'] .'</div>' : ''; ?>
						</td>
						<td style="width: 50%">
							<?php
								if (!empty($setting['multiple'])) {
									echo functions::form_function('settings['.$setting['key'].'][]', $setting['function'], true, !empty($setting['placeholder']) ? ' placeholder="'. functions::escape_attr($setting['placeholder']) .'"' : '');
								} else {
									echo functions::form_function('settings['.$setting['key'].']', $setting['function'], true, !empty($setting['placeholder']) ? ' placeholder="'. functions::escape_attr($setting['placeholder']) .'"' : '');
								}
							?>
						</td>
					</tr>
					<?php } ?>
					<tr>
						<td>
							<label><?php echo language::translate('title_translations', 'Translations'); ?></label>
						</td>
						<td>
							<a href="<?php echo document::href_ilink('translations/search', ['query' => $module_id . ':', 'modules' => 'true']); ?>"><?php echo language::translate('title_edit_translations', 'Edit Translations'); ?></a>
						</td>
					</tr>
				</tbody>
			</table>

			<div class="card-action">
				<?php echo functions::form_button_predefined('save'); ?>
				<?php if (!empty($module->data['id'])) echo functions::form_button('uninstall', language::translate('title_uninstall', 'Uninstall'), 'submit', 'class="btn btn-danger" onclick="if (!confirm(&quot;'. language::translate('text_are_you_sure', 'Are you sure?') .'&quot;)) return false;"', 'delete'); ?>
				<?php echo functions::form_button_predefined('cancel'); ?>
			</div>

		<?php echo functions::form_end(); ?>

		<?php if (!empty($module->data['last_log'])) { ?>
		<div id="box-last-log">
			<h2><?php echo language::translate('title_last_log', 'Last Log'); ?></h2>
			<pre class="form-input"><?php echo $module->data['last_log']; ?></pre>
		</div>
		<?php } ?>
	</div>
</div>
