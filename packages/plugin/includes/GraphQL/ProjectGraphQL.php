<?php
/**
 * WPGraphQL integration for Project meta.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio\GraphQL;

use Hwr\Portfolio\Meta\ProjectMeta;
use Hwr\Portfolio\PostType\ProjectPostType;
use WPGraphQL\Model\Post;

/**
 * Mirrors the Project meta fields into the WPGraphQL schema.
 *
 * The post type becomes a GraphQL type automatically via show_in_graphql. This
 * class adds the custom fields to that type, reading from the same
 * {@see ProjectMeta} definitions the REST surface uses. Registration is guarded
 * so the plugin still works when WPGraphQL is not active.
 */
final class ProjectGraphQL {

	public function __construct( private readonly ProjectMeta $meta ) {}

	public function register_hooks(): void {
		add_action( 'graphql_register_types', array( $this, 'register_fields' ) );
		add_filter( 'graphql_debug_enabled', array( $this, 'disable_debug_outside_dev' ) );
	}

	/**
	 * Keeps GraphQL debug output off outside local and development environments.
	 *
	 * @param bool $enabled Whether WPGraphQL debug is currently enabled.
	 */
	public function disable_debug_outside_dev( bool $enabled ): bool {
		return $enabled && in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
	}

	/**
	 * Registers the Project meta fields on the GraphQL type.
	 */
	public function register_fields(): void {
		if ( ! function_exists( 'register_graphql_field' ) ) {
			return;
		}

		$type = $this->graphql_type_name();

		register_graphql_field(
			$type,
			'role',
			array(
				'type'        => 'String',
				'description' => __( 'Role held on the project.', 'headless-portfolio' ),
				'resolve'     => fn( Post $post ): string => $this->meta->read( $post->ID )['role'],
			)
		);

		register_graphql_field(
			$type,
			'stack',
			array(
				'type'        => array( 'list_of' => 'String' ),
				'description' => __( 'Technologies used, as a list.', 'headless-portfolio' ),
				'resolve'     => fn( Post $post ): array => $this->meta->read( $post->ID )['stack'],
			)
		);

		register_graphql_field(
			$type,
			'repoUrl',
			array(
				'type'        => 'String',
				'description' => __( 'Public repository URL.', 'headless-portfolio' ),
				'resolve'     => fn( Post $post ): string => $this->meta->read( $post->ID )['repoUrl'],
			)
		);

		register_graphql_field(
			$type,
			'featured',
			array(
				'type'        => 'Boolean',
				'description' => __( 'Whether the project is featured.', 'headless-portfolio' ),
				'resolve'     => fn( Post $post ): bool => $this->meta->read( $post->ID )['featured'],
			)
		);
	}

	/**
	 * Returns the GraphQL type name for the project post type.
	 */
	private function graphql_type_name(): string {
		return ucfirst( ProjectPostType::GRAPHQL_SINGLE );
	}
}
