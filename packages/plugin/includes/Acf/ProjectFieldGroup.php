<?php
/**
 * Registers the Project editing UI as an ACF field group, in code.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio\Acf;

use Hwr\Portfolio\Meta\ProjectMeta;
use Hwr\Portfolio\PostType\ProjectPostType;

/**
 * Provides a polished editing experience for projects through ACF.
 *
 * The field group is registered from code (acf_add_local_field_group), not the
 * database, so it is version controlled and travels with the plugin. Each field
 * maps by name to an existing project meta key, so ACF is purely the editing UI:
 * REST, WPGraphQL and the block renderer keep reading the same meta, and the
 * values stay sanitized by ProjectMeta's registration. It is inert when ACF (or
 * its Secure Custom Fields fork) is not installed, so the plugin still works
 * with the plain post meta fields alone.
 */
final class ProjectFieldGroup {

	/**
	 * Registers the ACF init hook.
	 */
	public function register_hooks(): void {
		add_action( 'acf/init', array( $this, 'register' ) );
	}

	/**
	 * Registers the Project field group when ACF is available.
	 */
	public function register(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'      => 'group_hwr_project',
				'title'    => __( 'Project details', 'headless-portfolio' ),
				'fields'   => array(
					array(
						'key'          => 'field_hwr_role',
						'label'        => __( 'Role', 'headless-portfolio' ),
						'name'         => ProjectMeta::ROLE,
						'type'         => 'text',
						'instructions' => __( 'Role you held on the project, for example Senior Full-stack Developer.', 'headless-portfolio' ),
					),
					array(
						'key'          => 'field_hwr_stack',
						'label'        => __( 'Stack', 'headless-portfolio' ),
						'name'         => ProjectMeta::STACK,
						'type'         => 'text',
						'instructions' => __( 'Technologies used, comma separated.', 'headless-portfolio' ),
						'placeholder'  => 'React, WordPress, TypeScript',
					),
					array(
						'key'          => 'field_hwr_repo_url',
						'label'        => __( 'Repository URL', 'headless-portfolio' ),
						'name'         => ProjectMeta::REPO_URL,
						'type'         => 'url',
						'instructions' => __( 'Public repository URL.', 'headless-portfolio' ),
					),
					array(
						'key'          => 'field_hwr_featured',
						'label'        => __( 'Featured', 'headless-portfolio' ),
						'name'         => ProjectMeta::FEATURED,
						'type'         => 'true_false',
						'ui'           => 1,
						'instructions' => __( 'Highlight this project on the frontend.', 'headless-portfolio' ),
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => ProjectPostType::POST_TYPE,
						),
					),
				),
				'position' => 'normal',
				'style'    => 'default',
				'active'   => true,
			)
		);
	}
}
