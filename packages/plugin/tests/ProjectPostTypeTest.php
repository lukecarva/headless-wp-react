<?php
/**
 * Tests for the Project post type registration and permalink rewriting.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio\Tests;

use Hwr\Portfolio\PostType\ProjectPostType;
use WP_UnitTestCase;

final class ProjectPostTypeTest extends WP_UnitTestCase {

	public function test_post_type_is_registered_and_public_for_the_apis(): void {
		( new ProjectPostType() )->register();

		$object = get_post_type_object( ProjectPostType::POST_TYPE );

		$this->assertNotNull( $object );
		// Public so REST and WPGraphQL expose it to the frontend.
		$this->assertTrue( $object->public );
		$this->assertTrue( $object->publicly_queryable );
		$this->assertTrue( $object->show_in_rest );
		// Kept out of the WordPress site's own search.
		$this->assertTrue( $object->exclude_from_search );
	}

	public function test_filter_permalink_rewrites_project_to_frontend(): void {
		add_filter( 'hwr_frontend_url', static fn(): string => 'https://frontend.test' );

		$post = self::factory()->post->create_and_get(
			array(
				'post_type' => ProjectPostType::POST_TYPE,
				'post_name' => 'my-project',
			)
		);

		$result = ( new ProjectPostType() )->filter_permalink( 'http://wp.test/?p=1', $post );

		$this->assertSame( 'https://frontend.test/projects/my-project', $result );
	}

	public function test_filter_permalink_trims_trailing_slash_from_base(): void {
		add_filter( 'hwr_frontend_url', static fn(): string => 'https://frontend.test/' );

		$post = self::factory()->post->create_and_get(
			array(
				'post_type' => ProjectPostType::POST_TYPE,
				'post_name' => 'another',
			)
		);

		$result = ( new ProjectPostType() )->filter_permalink( 'http://wp.test/?p=2', $post );

		$this->assertSame( 'https://frontend.test/projects/another', $result );
	}

	public function test_filter_permalink_leaves_other_post_types_untouched(): void {
		$post     = self::factory()->post->create_and_get( array( 'post_type' => 'post' ) );
		$original = 'http://wp.test/hello-world';

		$result = ( new ProjectPostType() )->filter_permalink( $original, $post );

		$this->assertSame( $original, $result );
	}
}
