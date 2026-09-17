<?php
/**
 * Tests for the draft-preview link rewriting.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio\Tests;

use Hwr\Portfolio\PostType\ProjectPostType;
use Hwr\Portfolio\Preview;
use WP_UnitTestCase;

final class PreviewTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		add_filter( 'hwr_preview_secret', static fn(): string => 'preview-secret' );
		add_filter( 'hwr_frontend_url', static fn(): string => 'https://frontend.test' );
	}

	public function test_rewrites_a_project_preview_to_the_frontend_with_a_signed_token(): void {
		$post = self::factory()->post->create_and_get(
			array(
				'post_type'   => ProjectPostType::POST_TYPE,
				'post_name'   => 'my-project',
				'post_status' => 'draft',
			)
		);

		$link = ( new Preview() )->filter_preview_link( 'http://wp.test/?p=1&preview=true', $post );

		$this->assertStringStartsWith( 'https://frontend.test/api/draft?', $link );

		parse_str( (string) wp_parse_url( $link, PHP_URL_QUERY ), $params );

		$this->assertSame( (string) $post->ID, $params['id'] );
		$this->assertSame( 'my-project', $params['slug'] );
		$this->assertArrayHasKey( 'exp', $params );

		// The token signs {id}.{slug}.{exp} with the preview secret.
		$expected = hash_hmac( 'sha256', $post->ID . '.my-project.' . $params['exp'], 'preview-secret' );
		$this->assertSame( $expected, $params['token'] );
	}

	public function test_leaves_non_project_posts_untouched(): void {
		$post     = self::factory()->post->create_and_get( array( 'post_type' => 'post' ) );
		$original = 'http://wp.test/?p=1&preview=true';

		$this->assertSame( $original, ( new Preview() )->filter_preview_link( $original, $post ) );
	}

	public function test_is_inert_without_a_secret(): void {
		add_filter( 'hwr_preview_secret', static fn(): string => '', 20 );

		$post     = self::factory()->post->create_and_get( array( 'post_type' => ProjectPostType::POST_TYPE ) );
		$original = 'http://wp.test/?p=1&preview=true';

		$this->assertSame( $original, ( new Preview() )->filter_preview_link( $original, $post ) );
	}
}
