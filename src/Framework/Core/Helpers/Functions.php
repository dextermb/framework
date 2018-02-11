<?php

if (!function_exists('env')) {
	function env(string $key, string $fallback = null)
	{
		return getenv($key) ?: $fallback;
	}
}

if (!function_exists('dump')) {
	function dump(...$items)
	{
		if (strtolower(env('ENVIRONMENT', 'PRODUCTION')) === 'development') {
			if (count($items) === 1) {
				$items = $items[ 0 ];
			}

			echo '<pre>' . PHP_EOL;
			var_dump($items);
			echo '</pre>';
			die;
		}
	}
}

if (!function_exists('acronym')) {
	function acronym(string $input, array &$safety = [])
	{
		$bits   = preg_split('/[\s,_-]+/', $input);
		$output = '';

		foreach ($bits as $bit) {
			$output .= $bit[ 0 ];
		}

		$output = strtolower($output);

		if (!!count($safety)) {
			$tmp     = $output;
			$retries = 0;

			while (in_array($output, $safety)) {
				$output = $tmp . $retries++;
			}

			$safety[] = $output;
		}

		return $output;
	}
}

if (!function_exists('log')) {
	function log()
	{
		\Framework\Core\Helpers\Logger::put(func_get_args());
	}
}