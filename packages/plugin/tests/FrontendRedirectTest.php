<?php
/**
 * Tests for the front-end redirect allowlist handling.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio\Tests;

use Hwr\Portfolio\FrontendRedirect;
use WP_UnitTestCase;

final class FrontendRedirectTest extends WP_UnitTestCase {

	public function test_allow_frontend_redirect_host_adds_the_configured_host(): void {
		add_filter( 'hwr_frontend_url', static fn(): string => 'https://frontend.test' );

		$hosts = ( new FrontendRedirect() )->allow_frontend_redirect_host( array() );

		$this->assertContains( 'frontend.test', $hosts );
	}

	public function test_allow_frontend_redirect_host_preserves_existing_hosts(): void {
		add_filter( 'hwr_frontend_url', static fn(): string => 'https://frontend.test' );

		$hosts = ( new FrontendRedirect() )->allow_frontend_redirect_host( array( 'existing.test' ) );

		$this->assertContains( 'existing.test', $hosts );
		$this->assertContains( 'frontend.test', $hosts );
	}
}
