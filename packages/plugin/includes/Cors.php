<?php
/**
 * Restrictive CORS policy for the REST API.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio;

/**
 * Applies a restrictive CORS policy to the REST API.
 *
 * WordPress core reflects any request Origin and always sends
 * Access-Control-Allow-Credentials, allowing credentialed calls from any site.
 * This class sends CORS headers only for an allowlisted origin and omits the
 * credentials header unless it is explicitly enabled.
 */
final class Cors {

	/**
	 * Registers the hooks that install the policy.
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'replace_core_cors' ), 15 );
	}

	/**
	 * Replaces core's CORS handler with this one.
	 */
	public function replace_core_cors(): void {
		remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
		add_filter( 'rest_pre_serve_request', array( $this, 'send_cors_headers' ) );
	}

	/**
	 * Sends CORS headers when the request Origin is allowlisted.
	 *
	 * @param mixed $served Whether the request has been served.
	 * @return mixed
	 */
	public function send_cors_headers( $served ) {
		$origin = get_http_origin();

		if ( is_string( $origin ) && '' !== $origin && $this->is_allowed_origin( $origin ) ) {
			header( 'Access-Control-Allow-Origin: ' . esc_url_raw( untrailingslashit( $origin ) ) );
			header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
			header( 'Access-Control-Allow-Headers: Authorization, Content-Type' );
			header( 'Vary: Origin', false );

			/**
			 * Filters whether to advertise credentialed CORS responses.
			 *
			 * @param bool $allow Defaults to false.
			 */
			if ( apply_filters( 'hwr_cors_allow_credentials', false ) ) {
				header( 'Access-Control-Allow-Credentials: true' );
			}
		}

		return $served;
	}

	/**
	 * Returns whether an Origin is on the allowlist.
	 */
	public function is_allowed_origin( string $origin ): bool {
		return in_array( untrailingslashit( $origin ), $this->allowed_origins(), true );
	}

	/**
	 * Returns the CORS origin allowlist: the site and the configured frontend.
	 *
	 * @return string[]
	 */
	private function allowed_origins(): array {
		// The site itself and the configured frontend, resolved the same way every
		// other feature resolves it (constant or hwr_frontend_url filter).
		$origins = array( home_url(), Frontend::url() );

		/**
		 * Filters the CORS origin allowlist.
		 *
		 * @param string[] $origins
		 */
		$origins = (array) apply_filters( 'hwr_allowed_cors_origins', $origins );

		$origins = array_map( static fn( $o ): string => untrailingslashit( (string) $o ), $origins );

		return array_values( array_unique( array_filter( $origins ) ) );
	}
}
