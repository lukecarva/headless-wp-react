<?php
/**
 * The "project" custom post type.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio\PostType;

use WP_Post;

/**
 * Registers the project custom post type.
 *
 * The post type is public so the REST and WPGraphQL APIs expose it to the
 * decoupled frontend. The WordPress theme never renders it: front-end requests
 * for a project are redirected to the frontend, and permalinks point there too,
 * so there is no duplicate content.
 */
final class ProjectPostType {

	public const POST_TYPE = 'project';

	public const GRAPHQL_SINGLE = 'project';

	public const GRAPHQL_PLURAL = 'projects';

	/**
	 * Registers the post type and its routing hooks.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'template_redirect', array( $this, 'redirect_to_frontend' ) );
		add_filter( 'post_type_link', array( $this, 'filter_permalink' ), 10, 2 );
		add_filter( 'allowed_redirect_hosts', array( $this, 'allow_frontend_redirect_host' ) );
	}

	/**
	 * Registers the post type.
	 */
	public function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Projects', 'headless-portfolio' ),
					'singular_name' => __( 'Project', 'headless-portfolio' ),
					'add_new_item'  => __( 'Add New Project', 'headless-portfolio' ),
					'edit_item'     => __( 'Edit Project', 'headless-portfolio' ),
					'menu_name'     => __( 'Projects', 'headless-portfolio' ),
				),

				// Public so REST and WPGraphQL expose projects to unauthenticated
				// clients. The theme output is redirected (see redirect_to_frontend).
				'public'              => true,
				'publicly_queryable'  => true,
				'show_in_nav_menus'   => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => array( 'slug' => 'projects' ),
				'menu_icon'           => 'dashicons-portfolio',
				'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),

				// Dedicated capabilities: only administrator and editor (granted via
				// Hwr\Portfolio\Capabilities) can manage projects. map_meta_cap
				// resolves edit_post/delete_post/read_post from them.
				'capability_type'     => array( 'project', 'projects' ),
				'map_meta_cap'        => true,

				// Headless essentials: expose to REST and the block editor.
				'show_in_rest'        => true,
				'rest_base'           => 'projects',

				// WPGraphQL: opt in and name the query fields explicitly.
				'show_in_graphql'     => true,
				'graphql_single_name' => self::GRAPHQL_SINGLE,
				'graphql_plural_name' => self::GRAPHQL_PLURAL,
			)
		);
	}

	/**
	 * Redirects a front-end project request to the decoupled frontend.
	 */
	public function redirect_to_frontend(): void {
		// Never redirect editor previews: the draft has no published page on the
		// frontend, and the preview belongs in WordPress.
		if ( is_preview() || ! is_singular( self::POST_TYPE ) ) {
			return;
		}

		$frontend = $this->frontend_url();

		// No distinct frontend configured; do not redirect to ourselves.
		if ( untrailingslashit( home_url() ) === $frontend ) {
			return;
		}

		$post = get_queried_object();
		if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
			// 302 (temporary): the frontend URL comes from configuration and can
			// change, so the mapping must not be cached permanently by clients.
			wp_safe_redirect( $frontend . '/projects/' . $post->post_name, 302 );
			exit;
		}
	}

	/**
	 * Points project permalinks at the decoupled frontend.
	 *
	 * @param string  $permalink The post's permalink.
	 * @param WP_Post $post      The post object.
	 */
	public function filter_permalink( string $permalink, WP_Post $post ): string {
		if ( self::POST_TYPE !== $post->post_type ) {
			return $permalink;
		}

		return $this->frontend_url() . '/projects/' . $post->post_name;
	}

	/**
	 * Adds the frontend host to the wp_safe_redirect() allowlist.
	 *
	 * @param string[] $hosts Allowed redirect hosts.
	 * @return string[]
	 */
	public function allow_frontend_redirect_host( array $hosts ): array {
		$host = wp_parse_url( $this->frontend_url(), PHP_URL_HOST );
		if ( is_string( $host ) && '' !== $host ) {
			$hosts[] = $host;
		}
		return $hosts;
	}

	/**
	 * Returns the base URL of the frontend that renders projects.
	 *
	 * Defaults to the WordPress home URL; override with the HWR_FRONTEND_URL
	 * constant or the hwr_frontend_url filter.
	 */
	private function frontend_url(): string {
		$default = defined( 'HWR_FRONTEND_URL' ) ? (string) HWR_FRONTEND_URL : home_url();

		/**
		 * Filters the frontend base URL used for project links.
		 *
		 * @param string $url Frontend base URL, without a trailing slash.
		 */
		return untrailingslashit( (string) apply_filters( 'hwr_frontend_url', $default ) );
	}
}
