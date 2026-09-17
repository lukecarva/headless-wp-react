<?php
/**
 * Tests for the on-demand frontend revalidation ping.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio\Tests;

use Hwr\Portfolio\PostType\ProjectPostType;
use WP_UnitTestCase;

final class RevalidatorTest extends WP_UnitTestCase {

	public function test_project_save_pings_the_frontend_webhook(): void {
		add_filter( 'hwr_frontend_url', static fn(): string => 'https://frontend.test' );
		add_filter( 'hwr_revalidate_secret', static fn(): string => 'shhh' );

		$captured = array();
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( &$captured ) {
				$captured['url']    = $url;
				$captured['secret'] = $args['headers']['x-revalidate-secret'] ?? null;
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => '',
				);
			},
			10,
			3
		);

		self::factory()->post->create(
			array(
				'post_type'   => ProjectPostType::POST_TYPE,
				'post_status' => 'publish',
			)
		);

		$this->assertSame( 'https://frontend.test/api/revalidate', $captured['url'] ?? null );
		$this->assertSame( 'shhh', $captured['secret'] ?? null );
	}

	public function test_no_ping_when_secret_is_empty(): void {
		add_filter( 'hwr_frontend_url', static fn(): string => 'https://frontend.test' );
		add_filter( 'hwr_revalidate_secret', static fn(): string => '' );

		$pinged = false;
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( &$pinged ) {
				if ( is_string( $url ) && false !== strpos( $url, '/api/revalidate' ) ) {
					$pinged = true;
				}
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => '',
				);
			},
			10,
			3
		);

		self::factory()->post->create(
			array(
				'post_type'   => ProjectPostType::POST_TYPE,
				'post_status' => 'publish',
			)
		);

		$this->assertFalse( $pinged );
	}
}
