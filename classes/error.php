<?php

class PeepSoError
{
	const TABLE = 'peepso_errors';

	// PeepSoError("User {$owner} does not allow {$author} to write on wall");
	public function __construct($msg)
	{
		$trace = debug_backtrace();
		$caller = $trace[1];
		$code_file = str_replace('\\', '/', $caller['file']);

		$func = $caller['function'];
		if (!empty($caller['class'])) {
			$type = '->';
			if (isset($caller['type']) && !empty($caller['type']))
				$type = $caller['type'];
			$func = $caller['class'] . $type . $func;
		}
		$file = str_replace('\\', '/', plugin_dir_path(dirname(dirname(__FILE__)))); //), '', $code_file);
		$file = str_replace($file, '', $code_file);
		$line = $caller['line'];

		$code = 'Function ' . $func . ' at ' . $file . ':' . $line . ' - ';

		$data = array(
			'err_msg' => $code . $msg,
			'err_user_id' => PeepSo::get_user_id(),
			'err_ip' => PeepSo::get_ip_address(),
		);

		global $wpdb;
		$wpdb->insert($wpdb->prefix . self::TABLE, $data);
	}
}

// EOF