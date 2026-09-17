<?php
/**
 * Custom capabilities for the project post type.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio;

/**
 * Manages the dedicated project capabilities.
 *
 * Custom post type capabilities are not granted to any role automatically, not
 * even to administrators. This class grants them to the roles that manage
 * projects and removes them on uninstall.
 */
final class Capabilities {

	/**
	 * Roles allowed to manage projects.
	 *
	 * @var string[]
	 */
	private const ROLES = array( 'administrator', 'editor' );

	/**
	 * Returns the primitive capabilities for the project post type.
	 *
	 * The singular meta caps (edit_project, read_project, delete_project) are
	 * omitted, since map_meta_cap resolves them from these primitives.
	 *
	 * @return string[]
	 */
	public static function primitive_caps(): array {
		return array(
			'edit_projects',
			'edit_others_projects',
			'edit_private_projects',
			'edit_published_projects',
			'publish_projects',
			'read_private_projects',
			'delete_projects',
			'delete_others_projects',
			'delete_private_projects',
			'delete_published_projects',
		);
	}

	/**
	 * Grant the project capabilities to the managing roles. Idempotent.
	 */
	public static function add(): void {
		foreach ( self::ROLES as $role_name ) {
			$role = get_role( $role_name );
			if ( null === $role ) {
				continue;
			}
			foreach ( self::primitive_caps() as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}

	/**
	 * Remove the project capabilities from every role that has them.
	 */
	public static function remove(): void {
		foreach ( wp_roles()->role_objects as $role ) {
			foreach ( self::primitive_caps() as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}
}
