<?php
	document::$layout = 'blank';

	http_response_code(503);

	$_page = new ent_view('app://frontend/template/pages/maintenance_mode.inc.php');

	//echo $_page;
	extract($_page);
?>
<style>
body {
	padding: 60px 15px;
}
#box-maintenance-mode {
	display: block;
	text-align: center;
	padding: 30px;
	border-radius: 0px 25px 0px 25px;
	background: #fff;
	box-shadow: 0px 0px 60px rgba(0,0,0,0.25);
	margin: 0 auto;
	max-width: 640px;
}
#box-maintenance-mode img {
	max-width: 250px;
	max-height: 60px;
}
</style>

<section id="box-maintenance-mode">
	<img src="<?php echo document::href_rlink('storage://images/logotype.png'); ?>" alt="<?php echo settings::get('site_name'); ?>" title="<?php echo settings::get('site_name'); ?>">
	<hr>
	<h1><?php echo language::translate('maintenance_mode:title', 'Maintenance Mode'); ?></h1>
	<p><?php echo language::translate('maintenance_mode:description', 'This site is currently in maintenance mode. We\'ll be back shortly.'); ?></p>
</section>
