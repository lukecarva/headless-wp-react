<?php
/**
 * PHPUnit bootstrap that loads the WordPress test suite and this plugin.
 *
 * Run inside wp-env:
 *   pnpm env:start
 *   wp-env run tests-cli --env-cwd=wp-content/plugins/plugin ./vendor/bin/phpunit
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

$_functions = $_tests_dir . '/includes/functions.php';

if ( ! is_readable( $_functions ) ) {
	echo "Could not find the WordPress test suite at {$_tests_dir}." . PHP_EOL; // phpcs:ignore
	echo 'Set WP_TESTS_DIR or run through wp-env (see README).' . PHP_EOL;
	exit( 1 );
}

require_once $_functions;

/**
 * Load this plugin once the mu-plugins have loaded.
 */
tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require dirname( __DIR__ ) . '/headless-portfolio.php';
	}
);

require $_tests_dir . '/includes/bootstrap.php';
