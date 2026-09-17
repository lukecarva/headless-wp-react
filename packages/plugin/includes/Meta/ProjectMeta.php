<?php
/**
 * Single source of truth for Project meta fields.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio\Meta;

use Hwr\Portfolio\PostType\ProjectPostType;

/**
 * Single source of truth for the Project meta fields.
 *
 * Defines every custom field once and registers it with WordPress. REST and
 * GraphQL both read from {@see self::definitions()}, so the content model stays
 * consistent across both API surfaces.
 */
final class ProjectMeta {

	public const ROLE     = 'hwr_role';
	public const STACK    = 'hwr_stack';
	public const REPO_URL = 'hwr_repo_url';
	public const FEATURED = 'hwr_featured';

	/**
	 * Request-scoped cache of read meta, keyed by post ID.
	 *
	 * Lets repeated reads of the same post within one request reuse a single
	 * built array. It does not outlive the request.
	 *
	 * @var array<int, array{role:string, stack:string[], repoUrl:string, featured:bool}>
	 */
	private array $memo = array();

	/**
	 * Field definitions. The single source of truth for REST and GraphQL.
	 *
	 * `graphql_name` is the camelCase field exposed by WPGraphQL and `graphql_type`
	 * its GraphQL type (both consumed by Hwr\Portfolio\GraphQL\ProjectGraphQL).
	 *
	 * @return array<string, array{type:string, graphql_name:string, graphql_type:string|array<string,string>, sanitize:callable, description:string}>
	 */
	public function definitions(): array {
		return array(
			self::ROLE     => array(
				'type'         => 'string',
				'graphql_name' => 'role',
				'graphql_type' => 'String',
				'sanitize'     => 'sanitize_text_field',
				'description'  => __( 'Role held on the project.', 'headless-portfolio' ),
			),
			self::STACK    => array(
				'type'         => 'string',
				'graphql_name' => 'stack',
				'graphql_type' => array( 'list_of' => 'String' ),
				'sanitize'     => 'sanitize_text_field',
				'description'  => __( 'Technologies used, as a list.', 'headless-portfolio' ),
			),
			self::REPO_URL => array(
				'type'         => 'string',
				'graphql_name' => 'repoUrl',
				'graphql_type' => 'String',
				'sanitize'     => 'esc_url_raw',
				'description'  => __( 'Public repository URL.', 'headless-portfolio' ),
			),
			self::FEATURED => array(
				'type'         => 'boolean',
				'graphql_name' => 'featured',
				'graphql_type' => 'Boolean',
				'sanitize'     => array( self::class, 'sanitize_bool' ),
				'description'  => __( 'Whether the project is featured.', 'headless-portfolio' ),
			),
		);
	}

	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register each field as post meta, exposed to REST and the block editor.
	 */
	public function register(): void {
		foreach ( $this->definitions() as $key => $def ) {
			register_post_meta(
				ProjectPostType::POST_TYPE,
				$key,
				array(
					'type'              => $def['type'],
					'description'       => $def['description'],
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => $def['sanitize'],
					'auth_callback'     => fn( bool $allowed, string $meta_key, int $object_id ): bool => $this->can_edit_meta( $object_id ),
				)
			);
		}
	}

	/**
	 * Authorizes writing a Project's meta.
	 *
	 * Checks the capability for the specific post (edit_post), so a user can only
	 * change meta on a project they may edit. Falls back to the general
	 * capability when there is no post yet.
	 */
	public function can_edit_meta( int $object_id ): bool {
		return $object_id > 0
			? current_user_can( 'edit_post', $object_id )
			: current_user_can( 'edit_posts' );
	}

	/**
	 * Read all Project meta for a post, normalized to typed values.
	 *
	 * `stack` is normalized from its stored comma string into a string[] so both
	 * REST and GraphQL return an array rather than making each consumer split it.
	 *
	 * @return array{role:string, stack:string[], repoUrl:string, featured:bool}
	 */
	public function read( int $post_id ): array {
		if ( isset( $this->memo[ $post_id ] ) ) {
			return $this->memo[ $post_id ];
		}

		$stack_raw = (string) get_post_meta( $post_id, self::STACK, true );

		$this->memo[ $post_id ] = array(
			'role'     => (string) get_post_meta( $post_id, self::ROLE, true ),
			'stack'    => $this->split_stack( $stack_raw ),
			'repoUrl'  => (string) get_post_meta( $post_id, self::REPO_URL, true ),
			'featured' => (bool) get_post_meta( $post_id, self::FEATURED, true ),
		);

		return $this->memo[ $post_id ];
	}

	/**
	 * Turn a comma-separated stack string into a clean list.
	 *
	 * @return string[]
	 */
	public function split_stack( string $stack ): array {
		if ( '' === trim( $stack ) ) {
			return array();
		}
		$parts = array_map( 'trim', explode( ',', $stack ) );
		$parts = array_filter( $parts, static fn( string $p ): bool => '' !== $p );
		return array_values( array_unique( $parts ) );
	}

	/**
	 * Coerce mixed truthy input to a strict boolean for the `featured` flag.
	 *
	 * @param mixed $value Raw input.
	 */
	public static function sanitize_bool( $value ): bool {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}
}
