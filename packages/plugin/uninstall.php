<?php
/**
 * Uninstall routine for Headless Portfolio.
 *
 * Runs when the plugin is deleted from wp-admin. WordPress includes this file
 * in isolation, without bootstrapping the plugin, so it must be self-contained
 * and cannot rely on the plugin's classes or constants.
 *
 * Removes plugin-specific data (the demo-seed flag, capabilities, and
 * registered meta) but leaves the project posts in place, since they are user
 * content.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

// Bail unless invoked by WordPress's uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Demo-seed guard flag set by the local mu-plugin.
delete_option( 'hwr_demo_seeded' );

// Remove the custom project capabilities from every role. The class is
// dependency-free, so it is safe to load in the isolated uninstall context.
require_once __DIR__ . '/includes/Capabilities.php';
Hwr\Portfolio\Capabilities::remove();

// Registered Project meta. These keys mirror Hwr\Portfolio\Meta\ProjectMeta;
// kept as literals here because that class is not loaded during uninstall.
$hwr_meta_keys = array( 'hwr_role', 'hwr_stack', 'hwr_repo_url', 'hwr_featured' );

foreach ( $hwr_meta_keys as $hwr_meta_key ) {
	delete_post_meta_by_key( $hwr_meta_key );
}
