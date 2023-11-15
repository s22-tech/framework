<?php

	if (empty($_REQUEST['page'])) $_REQUEST['page'] = 1;

	if (!empty($_REQUEST['query'])) {
		$sql_find = [
			"id = '". database::input($_REQUEST['query']) ."'",
			"email like '%". database::input($_REQUEST['query']) ."%'",
			"tax_id like '%". database::input($_REQUEST['query']) ."%'",
			"company like '%". database::input($_REQUEST['query']) ."%'",
			"concat(firstname, ' ', lastname) like '%". database::input($_REQUEST['query']) ."%'",
		];
	}

// Rows, Total Number of Rows, Total Number of Pages
	$users = database::query(
		"select id, if(company, company, concat(firstname, ' ', lastname) as name, email, date_created from ". DB_TABLE_PREFIX ."users
		". (!empty($sql_find) ? "where (". implode(" or ", $sql_find) .")" : "") ."
		order by firstname, lastname
		limit 15;"
	)->fetch_page($_GET['page'], null, $num_rows, $num_pages);

	ob_end_clean();
	header('Content-Type: application/json');
	echo json_encode($users, JSON_UNESCAPED_SLASHES);
	exit;
