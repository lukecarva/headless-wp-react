<?php
/**
 * On-demand frontend revalidation.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio;

use Hwr\Portfolio\PostType\ProjectPostType;

/**
 * Tells the frontend to revalidate cached project data when a project changes.
 *
 * Closes the on-demand loop with the Next webhook: on a project save it POSTs to
 * {frontend}/api/revalidate with the shared secret, so published changes appear
 * immediately instead of waiting out the ISR interval. It is a no-op when the
 * secret is not configured (HWR_REVALIDATE_SECRET / the hwr_revalidate_secret
 * filter), so it stays inert until the pairing is set up.
 */
final class Revalidator {

	/**
	 * Registers the save hook.
	 */
	public function register_hooks(): void {
		add_action( 'save_post_' . ProjectPostType::POST_TYPE, array( $this, 'on_change' ) );
	}

	/**
	 * Pings the frontend after a project is created or updated.
	 *
	 * @param int $post_id Saved post ID.
	 */
	public function on_change( int $post_id ): void {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$secret   = $this->secret();
		$frontend = $this->frontend_url();
		if ( '' === $secret || '' === $frontend ) {
			return;
		}

		// Non-blocking so the editor save is never slowed by the frontend.
		wp_remote_post(
			$frontend . '/api/revalidate',
			array(
				'timeout'  => 5,
				'blocking' => false,
				'headers'  => array( 'x-revalidate-secret' => $secret ),
			)
		);
	}

	/**
	 * Shared secret expected by the Next webhook (empty disables the ping).
	 */
	private function secret(): string {
		$default = defined( 'HWR_REVALIDATE_SECRET' ) ? (string) HWR_REVALIDATE_SECRET : '';

		/**
		 * Filters the revalidation shared secret.
		 *
		 * @param string $secret
		 */
		return (string) apply_filters( 'hwr_revalidate_secret', $default );
	}

	/**
	 * Base URL of the frontend, mirroring ProjectPostType's resolution.
	 */
	private function frontend_url(): string {
		$default = defined( 'HWR_FRONTEND_URL' ) ? (string) HWR_FRONTEND_URL : home_url();

		return untrailingslashit( (string) apply_filters( 'hwr_frontend_url', $default ) );
	}
}
