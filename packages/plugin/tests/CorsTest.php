<?php
/**
 * Tests for the restrictive CORS policy.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio\Tests;

use Hwr\Portfolio\Cors;
use WP_UnitTestCase;

final class CorsTest extends WP_UnitTestCase {

	private Cors $cors;

	public function set_up(): void {
		parent::set_up();
		$this->cors = new Cors();
	}

	public function test_site_origin_is_allowed(): void {
		$this->assertTrue( $this->cors->is_allowed_origin( home_url() ) );
	}

	public function test_unknown_external_origin_is_denied(): void {
		$this->assertFalse( $this->cors->is_allowed_origin( 'https://evil.example' ) );
	}

	public function test_allowlist_is_filterable_and_slash_insensitive(): void {
		add_filter(
			'hwr_allowed_cors_origins',
			static function ( array $origins ): array {
				$origins[] = 'https://app.trusted.test';
				return $origins;
			}
		);

		// Trailing slash on the incoming origin must not matter.
		$this->assertTrue( $this->cors->is_allowed_origin( 'https://app.trusted.test/' ) );
		$this->assertFalse( $this->cors->is_allowed_origin( 'https://other.test' ) );
	}

	public function test_credentials_are_off_by_default(): void {
		$this->assertFalse( apply_filters( 'hwr_cors_allow_credentials', false ) );
	}
}
