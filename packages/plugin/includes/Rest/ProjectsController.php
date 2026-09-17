<?php
/**
 * Custom REST surface for projects.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio\Rest;

use Hwr\Portfolio\Meta\ProjectMeta;
use Hwr\Portfolio\PostType\ProjectPostType;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Custom REST endpoints under the `hwr/v1` namespace.
 *
 * The core post type is already exposed at /wp/v2/projects. This route returns
 * a lean, presentation-ready shape (normalized stack, resolved featured image)
 * so non-GraphQL consumers get the same read model without walking _embed
 * chains.
 */
final class ProjectsController {

	public const NAMESPACE = 'hwr/v1';
	public const REST_BASE = 'projects';

	public function __construct( private readonly ProjectMeta $meta ) {}

	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers the REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/' . self::REST_BASE,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => '__return_true', // Published projects are public.
					'args'                => array(
						'featured' => array(
							'type'        => 'boolean',
							'required'    => false,
							'description' => __( 'Return only featured projects.', 'headless-portfolio' ),
						),
						'per_page' => array(
							'type'              => 'integer',
							'default'           => 10,
							'minimum'           => 1,
							'maximum'           => 100,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// Draft preview read. Returns a single project (any status) for the
		// decoupled frontend's draft mode, guarded by the same per-post capability
		// as the write route, so only a user who may edit the project can read its
		// unpublished content. Authenticated over a WordPress Application Password.
		register_rest_route(
			self::NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>\d+)/preview',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_preview_item' ),
					'permission_callback' => array( $this, 'can_edit_project' ),
					'args'                => array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// Write route for toggling a project's "featured" flag. Guarded by an
		// explicit per-post capability check (edit_post maps to the project
		// capabilities); the API never trusts the caller.
		register_rest_route(
			self::NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>\d+)/featured',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_featured' ),
					'permission_callback' => array( $this, 'can_edit_project' ),
					'args'                => array(
						'id'       => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'featured' => array(
							'type'     => 'boolean',
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Authorizes the write route.
	 *
	 * The current user must be able to edit the specific project. Returns a
	 * WP_Error so unauthenticated and forbidden callers get the correct status.
	 *
	 * @return true|WP_Error
	 */
	public function can_edit_project( WP_REST_Request $request ) {
		$id = (int) $request['id'];

		if ( current_user_can( 'edit_post', $id ) ) {
			return true;
		}

		return new WP_Error(
			'hwr_forbidden',
			__( 'You are not allowed to edit this project.', 'headless-portfolio' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Sets a project's featured flag.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_featured( WP_REST_Request $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );

		if ( ! $post instanceof WP_Post || ProjectPostType::POST_TYPE !== $post->post_type ) {
			return new WP_Error(
				'hwr_not_found',
				__( 'Project not found.', 'headless-portfolio' ),
				array( 'status' => 404 )
			);
		}

		$featured = (bool) $request['featured'];
		update_post_meta( $id, ProjectMeta::FEATURED, $featured ? '1' : '0' );

		return new WP_REST_Response( $this->prepare_item( $post ) );
	}

	/**
	 * Returns a single project (any status) for frontend draft preview.
	 *
	 * Shaped to match the frontend's project detail read (title, excerpt, role,
	 * stack, repoUrl, content), so the page renders a draft the same way as a
	 * published project.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_preview_item( WP_REST_Request $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );

		if ( ! $post instanceof WP_Post || ProjectPostType::POST_TYPE !== $post->post_type ) {
			return new WP_Error(
				'hwr_not_found',
				__( 'Project not found.', 'headless-portfolio' ),
				array( 'status' => 404 )
			);
		}

		$meta = $this->meta->read( $post->ID );

		return new WP_REST_Response(
			array(
				'title'   => get_the_title( $post ),
				'excerpt' => wp_strip_all_tags( get_the_excerpt( $post ) ),
				'role'    => $meta['role'],
				'stack'   => $meta['stack'],
				'repoUrl' => $meta['repoUrl'],
				'content' => apply_filters( 'the_content', $post->post_content ),
			)
		);
	}

	/**
	 * Returns the list of published projects.
	 */
	public function get_items( WP_REST_Request $request ): WP_REST_Response {
		$args = array(
			'post_type'      => ProjectPostType::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => (int) $request->get_param( 'per_page' ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$featured = $request->get_param( 'featured' );
		if ( null !== $featured ) {
			if ( $featured ) {
				$args['meta_query'] = array(
					array(
						'key'   => ProjectMeta::FEATURED,
						'value' => '1',
					),
				);
			} else {
				// "Not featured" is stored inconsistently across write paths (an
				// empty string by core meta, "0" by this controller and ACF) and may
				// be absent entirely, so treat anything that is not exactly "1" as
				// not featured.
				$args['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key'     => ProjectMeta::FEATURED,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => ProjectMeta::FEATURED,
						'value'   => '1',
						'compare' => '!=',
					),
				);
			}
		}

		$query = new \WP_Query( $args );
		$items = array_map( array( $this, 'prepare_item' ), $query->posts );

		$response = new WP_REST_Response( $items );
		$response->header( 'X-WP-Total', (string) $query->found_posts );

		return $response;
	}

	/**
	 * Shapes a single Project post for the list and write responses.
	 *
	 * Full content is not included here: the list only needs the excerpt, and the
	 * draft-preview read builds its own shape in get_preview_item().
	 *
	 * @return array<string, mixed>
	 */
	public function prepare_item( WP_Post $post ): array {
		$meta      = $this->meta->read( $post->ID );
		$thumbnail = get_the_post_thumbnail_url( $post, 'large' );

		return array(
			'id'            => $post->ID,
			'slug'          => $post->post_name,
			'title'         => get_the_title( $post ),
			'excerpt'       => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'featuredImage' => is_string( $thumbnail ) ? $thumbnail : null,
			'meta'          => $meta,
		);
	}
}
