<?php
/**
 * Redirects the WordPress front-end to the decoupled frontend.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio;

use Hwr\Portfolio\PostType\ProjectPostType;

/**
 * Sends every WordPress front-end request to the headless frontend.
 *
 * In a headless setup the WordPress theme is never the presentation layer, so a
 * visitor who reaches a front-end URL directly (the site root, a page, an
 * archive) is forwarded to the Next.js app instead of seeing the default theme.
 * Single project requests keep their dedicated mapping in
 * Hwr\Portfolio\PostType\ProjectPostType, so this handler defers to that. Admin,
 * REST and GraphQL requests never reach template_redirect, so they are
 * unaffected. It stays inert until a distinct HWR_FRONTEND_URL is configured.
 */
final class FrontendRedirect {

	/**
	 * Registers the redirect hooks.
	 */
	public function register_hooks(): void {
		add_action( 'template_redirect', array( $this, 'redirect' ), 0 );
		add_filter( 'allowed_redirect_hosts', array( $this, 'allow_frontend_redirect_host' ) );
	}

	/**
	 * Forwards a front-end request to the frontend root.
	 */
	public function redirect(): void {
		// Editor previews and single project views are handled elsewhere.
		if ( is_preview() || is_singular( ProjectPostType::POST_TYPE ) ) {
			return;
		}

		// Skip a GraphQL request that reaches template_redirect on some setups.
		if ( function_exists( 'is_graphql_http_request' ) && is_graphql_http_request() ) {
			return;
		}

		// Leave machine-readable endpoints (feeds, robots.txt) to WordPress.
		if ( is_robots() || is_feed() ) {
			return;
		}

		// No distinct frontend configured; do not redirect to ourselves.
		if ( ! Frontend::is_distinct() ) {
			return;
		}

		// The frontend renders the home page and project pages only, so every
		// other WordPress front-end URL maps to the frontend root. 302 because the
		// frontend URL comes from configuration and can change.
		wp_safe_redirect( Frontend::url(), 302 );
		exit;
	}

	/**
	 * Adds the frontend host to the wp_safe_redirect() allowlist.
	 *
	 * @param string[] $hosts Allowed redirect hosts.
	 * @return string[]
	 */
	public function allow_frontend_redirect_host( array $hosts ): array {
		$host = wp_parse_url( Frontend::url(), PHP_URL_HOST );
		if ( is_string( $host ) && '' !== $host ) {
			$hosts[] = $host;
		}
		return $hosts;
	}
}
