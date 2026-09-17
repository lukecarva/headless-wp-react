<?php
/**
 * Integration tests for the custom REST endpoint.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio\Tests;

use Hwr\Portfolio\Capabilities;
use Hwr\Portfolio\Meta\ProjectMeta;
use Hwr\Portfolio\PostType\ProjectPostType;
use WP_REST_Request;
use WP_UnitTestCase;

final class ProjectsControllerTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		// Ensure routes are registered for this request cycle.
		do_action( 'rest_api_init' );
	}

	public function test_route_is_registered(): void {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/hwr/v1/projects', $routes );
	}

	public function test_returns_published_projects_with_normalized_meta(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => ProjectPostType::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Headless Site',
			)
		);
		update_post_meta( $post_id, ProjectMeta::ROLE, 'Senior' );
		update_post_meta( $post_id, ProjectMeta::STACK, 'React, PHP' );

		$request  = new WP_REST_Request( 'GET', '/hwr/v1/projects' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertCount( 1, $data );
		$this->assertSame( 'Headless Site', $data[0]['title'] );
		$this->assertSame( 'Senior', $data[0]['meta']['role'] );
		$this->assertSame( array( 'React', 'PHP' ), $data[0]['meta']['stack'] );
		// The list shape is lean: full content is not fetched for a listing.
		$this->assertArrayNotHasKey( 'content', $data[0] );
	}

	public function test_featured_filter_excludes_non_featured(): void {
		$featured = self::factory()->post->create(
			array(
				'post_type'   => ProjectPostType::POST_TYPE,
				'post_status' => 'publish',
			)
		);
		update_post_meta( $featured, ProjectMeta::FEATURED, '1' );

		self::factory()->post->create(
			array(
				'post_type'   => ProjectPostType::POST_TYPE,
				'post_status' => 'publish',
			)
		);

		$request = new WP_REST_Request( 'GET', '/hwr/v1/projects' );
		$request->set_param( 'featured', true );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();
		$this->assertCount( 1, $data );
		$this->assertSame( $featured, $data[0]['id'] );
	}

	public function test_update_featured_route_is_registered(): void {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/hwr/v1/projects/(?P<id>\d+)/featured', $routes );
	}

	public function test_update_featured_denies_users_without_capability(): void {
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$project    = self::factory()->post->create(
			array(
				'post_type'   => ProjectPostType::POST_TYPE,
				'post_status' => 'publish',
			)
		);

		wp_set_current_user( $subscriber );

		$request = new WP_REST_Request( 'POST', '/hwr/v1/projects/' . $project . '/featured' );
		$request->set_param( 'featured', true );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertNotSame( '1', get_post_meta( $project, ProjectMeta::FEATURED, true ) );
	}

	public function test_update_featured_allows_editor_and_persists(): void {
		// The plugin grants project caps to editors on activation; do so here.
		Capabilities::add();
		$editor  = self::factory()->user->create( array( 'role' => 'editor' ) );
		$project = self::factory()->post->create(
			array(
				'post_type'   => ProjectPostType::POST_TYPE,
				'post_status' => 'publish',
			)
		);

		wp_set_current_user( $editor );

		$request = new WP_REST_Request( 'POST', '/hwr/v1/projects/' . $project . '/featured' );
		$request->set_param( 'featured', true );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '1', get_post_meta( $project, ProjectMeta::FEATURED, true ) );
	}
}
