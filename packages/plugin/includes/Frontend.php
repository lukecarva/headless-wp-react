<?php
/**
 * Resolves the decoupled frontend's location.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio;

/**
 * Single source for the frontend base URL.
 *
 * Every feature that needs to know where the frontend lives (project links, the
 * headless redirect, preview, revalidation and CORS) resolves it here, so they
 * cannot drift apart: the HWR_FRONTEND_URL constant or the hwr_frontend_url
 * filter, defaulting to this WordPress site when neither is set.
 */
final class Frontend {

	/**
	 * Base URL of the frontend, without a trailing slash.
	 */
	public static function url(): string {
		$default = defined( 'HWR_FRONTEND_URL' ) ? (string) HWR_FRONTEND_URL : home_url();

		/**
		 * Filters the frontend base URL.
		 *
		 * @param string $url Frontend base URL, without a trailing slash.
		 */
		return untrailingslashit( (string) apply_filters( 'hwr_frontend_url', $default ) );
	}

	/**
	 * Whether a frontend distinct from this WordPress site is configured.
	 *
	 * Features that would otherwise target WordPress itself (the headless
	 * redirect, the revalidation ping) stay inert until this is true.
	 */
	public static function is_distinct(): bool {
		return self::url() !== untrailingslashit( home_url() );
	}
}
