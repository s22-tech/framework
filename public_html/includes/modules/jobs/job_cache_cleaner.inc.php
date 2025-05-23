<?php

	#[AllowDynamicProperties]
	class job_cache_cleaner extends abs_module {
		public $name = 'Cache Cleaner';
		public $description = 'Wipe out old cache files that are starting to collect dust.';
		public $author = 'LiteCore Dev Team';
		public $version = '1.0';
		public $website = 'https://litecore.dev';
		public $priority = 0;

		public function process($force, $last_run) {

			if (!$this->settings['status']) return;

			if ($last_run || !$force) {
				if (strtotime($last_run) > functions::datetime_last_by_interval('Hourly', $last_run)) return;
			}

			echo 'Wipe out old cache files...' . PHP_EOL;

			$timestamp = strtotime('-24 hours');
			$deleted = 0;

			clearstatcache();

			foreach (glob(FS_DIR_STORAGE .'cache/*', GLOB_ONLYDIR) as $dir) {
				$search = '/*.cache';
				foreach (glob($dir.$search) as $file) {
					if (filemtime($file) > $timestamp) continue;
					echo '  Deleting ' . basename($file) . PHP_EOL;
					unlink($file);
					$deleted++;
				}
			}

			echo PHP_EOL . "Cleaned up $deleted files" . PHP_EOL;
		}

		function settings() {

			return [
				[
					'key' => 'status',
					'default_value' => '1',
					'title' => language::translate(__CLASS__.':title_status', 'Status'),
					'description' => language::translate(__CLASS__.':description_status', 'Enables or disables the module.'),
					'function' => 'toggle("e/d")',
				],
				[
					'key' => 'priority',
					'default_value' => '0',
					'title' => language::translate(__CLASS__.':title_priority', 'Priority'),
					'description' => language::translate(__CLASS__.':description_priority', 'Process this module in the given priority order.'),
					'function' => 'number()',
				],
			];
		}
	}
