<?php
/**
 * Plugin Name:       Headless Portfolio
 * Plugin URI:        https://github.com/lukecarva/headless-wp-react
 * Description:       Headless content model for a project portfolio. Custom post type, meta, REST and WPGraphQL surfaces, and a Gutenberg block. Backend half of the headless-wp-react reference.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            Math Rosa
 * License:           MIT
 * Text Domain:       headless-portfolio
 * Domain Path:       /languages
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio;

defined( 'ABSPATH' ) || exit;

define( 'HWR_PLUGIN_FILE', __FILE__ );
define( 'HWR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HWR_PLUGIN_VERSION', '1.0.0' );

// PSR-4 autoloader: Composer in production, a tiny fallback so the plugin
// boots even before `composer install` (e.g. a fresh clone in wp-env).
if ( is_readable( HWR_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require HWR_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	spl_autoload_register(
		static function ( string $class_name ): void {
			$prefix = __NAMESPACE__ . '\\';
			if ( ! str_starts_with( $class_name, $prefix ) ) {
				return;
			}
			$relative = substr( $class_name, strlen( $prefix ) );
			$path     = HWR_PLUGIN_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
			if ( is_readable( $path ) ) {
				require $path;
			}
		}
	);
}

// Activation: register the post type, grant the project capabilities to the
// managing roles, then flush rewrite rules once so the CPT's routes work.
register_activation_hook(
	__FILE__,
	static function (): void {
		( new PostType\ProjectPostType() )->register();
		Capabilities::add();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		flush_rewrite_rules();
	}
);

// Boot on `plugins_loaded` so WPGraphQL (a dependency) is available to hook into.
add_action(
	'plugins_loaded',
	static function (): void {
		Plugin::instance()->boot();
	}
);
