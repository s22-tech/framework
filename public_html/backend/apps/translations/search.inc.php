<?php

	if (empty($_GET['page']) || !is_numeric($_GET['page'])) {
		$_GET['page'] = 1;
	}

	if (empty($_GET['languages'])) {
		$_GET['languages'] = array_slice(array_keys(language::$languages), 0, 2);
	}

	if (!empty($_GET['languages'])) {
		foreach (array_keys($_GET['languages']) as $key) {
			if (!in_array($_GET['languages'][$key], array_keys(language::$languages))) unset($_GET['languages'][$key]);
		}
	}

	document::$title[] = language::translate('title_search_translations', 'Search Translations');

	breadcrumbs::add(language::translate('title_translations', 'Translations'));
	breadcrumbs::add(language::translate('title_search_translations', 'Search Translations'));

	if (isset($_POST['save']) && !empty($_POST['translations'])) {

		foreach ($_POST['translations'] as $translation) {
			$sql_update_fields = '';
			foreach ($_GET['languages'] as $language_code) {
				$sql_update_fields .= "text_".database::input($language_code) ." = '". database::input(trim($translation['text_'.database::input($language_code)]), !empty($translation['html'])) ."', " . PHP_EOL;
			}
			database::query(
				"update ". DB_TABLE_PREFIX ."translations
				set html = ". (!empty($translation['html']) ? 1 : 0) .",
					". $sql_update_fields ."
					date_updated = '". date('Y-m-d H:i:s') ."'
				where id = ". (int)$translation['id'] ."
				limit 1;"
			);
		}

		cache::clear_cache('translations');

		notices::add('success', language::translate('success_changes_saved', 'Changes saved'));

		header('Location: '. document::ilink());
		exit;
	}

	if (isset($_POST['delete']) && !empty($_POST['translation_id'])) {

		database::query(
			"delete from ". DB_TABLE_PREFIX ."translations
			where id = '". database::input($_POST['translation_id']) ."'
			limit 1;"
		);

		cache::clear_cache('translations');

		echo json_encode(['status' => 'ok']);
		exit;
	}

	// Table Rows, Total Number of Rows, Total Number of Pages
	$translations = database::query(
		"select * from ". DB_TABLE_PREFIX ."translations
		where id
		". ((!empty($_GET['endpoint']) && $_GET['endpoint'] == 'frontend') ? "and frontend = 1" : null) ."
		". ((!empty($_GET['endpoint']) && $_GET['endpoint'] == 'backend') ? "and backend = 1" : null) ."
		". (!empty($_GET['query']) ? "and (code like '%". str_replace('%', "\\%", database::input($_GET['query'])) ."%' or " . implode(" or ", array_map(function($s){ return "`text_$s` like '%". database::input($_GET['query']) ."%'";}, database::input($_GET['languages']))) .")" : "") ."
		". (!empty($_GET['untranslated']) ? "and (". implode(" or ", array_map(function($s){ return "(text_$s is null or text_$s = '')"; }, database::input($_GET['languages']))) .")" : null) ."
		". (empty($_GET['modules']) ? " and code not regexp '^(job)_'" : null) ."
		order by date_updated desc;"
	)->fetch_page($_GET['page'], null, $num_rows, $num_pages);

	// Languages
	$languages = database::query(
		"select id, code, name
		from ". DB_TABLE_PREFIX ."languages
		where code in ('". implode("', '", database::input($_GET['languages'])) ."')
		order by priority;"
	)->fetch_all(null, 'code');

	// Language Options
	$language_options = array_column($languages, 'name', 'code');

?>
<style>
ul.filter li {
	display: table-cell;
	vertical-align: middle;
}
th:not(:last-child) {
	min-width: 250px;
}
</style>

<div class="card card-app">
	<div class="card-header">
		<div class="card-title">
			<?php echo $app_icon; ?> <?php echo language::translate('title_search_translations', 'Search Translations'); ?>
		</div>
	</div>

	<?php echo functions::form_begin('search_form', 'get'); ?>
		<div class="card-filter">
			<div class="expandable"><?php echo functions::form_input_search('query', true, 'placeholder="'. language::translate('text_search_phrase_or_keyword', 'Search phrase or keyword') .'"'); ?></div>
			<?php echo functions::form_checkbox('untranslated', ['1', language::translate('text_only_untranslated', 'Only untranslated')]); ?>
			<?php echo functions::form_checkbox('modules', ['1', language::translate('text_inlcude_modules', 'Include modules')]); ?>
			<?php echo functions::form_dropdown('languages[]', $language_options, true); ?>
			<div style="max-width: max-content;"><?php echo functions::form_select('endpoint', ['' => '-- '. language::translate('title_all', 'All') .' --', 'frontend' => language::translate('title_frontend', 'Frontend'), 'backend' => language::translate('title_backend', 'Backend')]); ?></div>
			<?php echo functions::form_button('filter', language::translate('title_search', 'Search'), 'submit'); ?>
		</div>
	<?php echo functions::form_end(); ?>

	<?php echo functions::form_begin('translation_form', 'post'); ?>

		<div class="table-responsive">
			<table class="table table-striped">
				<thead>
					<tr>
						<th><?php echo language::translate('title_code', 'Code'); ?></th>
						<?php foreach ($_GET['languages'] as $language_code) echo '<th style="width: 480px;">'. $languages[$language_code]['name'] .'</th>'; ?>
						<th></th>
					</tr>
				</thead>

				<tbody>
					<?php $tab_index = 0; foreach ($translations as $translation) { ?>
					<tr>
						<td>
							<code class="code"><?php echo $translation['code']; ?></code><br>
							<span style="color: #999;"><?php echo functions::form_checkbox('translations['. $translation['code'] .'][html]', ['1', language::translate('text_enable_html', 'Enable HTML')], (isset($_POST['translations'][$translation['code']]['html']) ? $_POST['translations'][$translation['code']]['html'] : $translation['html'])); ?></span>
						</td>
						<?php foreach ($_GET['languages'] as $key => $language_code) { ?>
						<td>
							<?php echo functions::form_input_hidden('translations['. $translation['code'] .'][id]', $translation['id']); ?>
							<?php echo functions::form_textarea('translations['. $translation['code'] .'][text_'.$language_code.']', $translation['text_'.$language_code], 'rows="2" dir="'. language::$languages[$language_code]['direction'] .'" tabindex="'. $key.str_pad(++$tab_index, 2, '0', STR_PAD_LEFT) .'"'); ?>
						</td>
						<?php } ?>
						<td class="text-end"><a class="btn btn-danger btn-sm delete" href="#" title="<?php echo language::translate('title_remove', 'Remove'); ?>"><?php echo functions::draw_fonticon('fa-trash'); ?></a></td>
					</tr>
					<?php } ?>
				</tbody>

				<tfoot>
					<tr>
						<td colspan="<?php echo 3 + count($_GET['languages']); ?>"><?php echo language::translate('title_translations', 'Translations'); ?>: <?php echo language::number_format($num_rows); ?></td>
					</tr>
				</tfoot>
			</table>
		</div>

		<div class="card-action">
			<?php echo functions::form_button('save', language::translate('title_save', 'Save'), 'submit', 'class="btn btn-success"', 'save'); ?>
		</div>

	<?php echo functions::form_end(); ?>

	<?php if ($num_pages > 1) { ?>
	<div class="card-footer">
		<?php echo functions::draw_pagination($num_pages); ?>
	</div>
	<?php } ?>
</div>

<script>
	$('.delete').click(function(e){
		e.preventDefault();

		if (!window.confirm('<?php echo language::translate('text_are_you_sure', 'Are you sure?'); ?>')) return false;

		let row = $(this).closest('tr');

		$.ajax({
			type: 'post',
			data: 'translation_id=' + $(row).find('input[name$="[id]"]').val() + '&delete=true',
			cache: false,
			async: true,
			dataType: 'json',
			beforeSend: function(jqXHR) {
				jqXHR.overrideMimeType('text/html;charset=' + $('meta[charset]').attr('charset'));
			},
			error: function(jqXHR, textStatus, errorThrown) {
				alert('An error occurred');
			},
			success: function(json) {
				if (json['status'] && json['status'] == 'ok') {
					$(row).remove();
				}
			}
		});
	});
</script>