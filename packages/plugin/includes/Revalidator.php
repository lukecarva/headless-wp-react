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
 * Closes the on-demand loop with the Next webhook: on a project save or a
 * permanent deletion it POSTs to {frontend}/api/revalidate with the shared
 * secret, so published changes and removals appear immediately instead of
 * waiting out the ISR interval. It is inert when the secret is not configured
 * (HWR_REVALIDATE_SECRET / the hwr_revalidate_secret filter) or when no distinct
 * frontend URL is set, so it never pings this WordPress site.
 */
final class Revalidator {

	/**
	 * Registers the save and delete hooks.
	 */
	public function register_hooks(): void {
		add_action( 'save_post_' . ProjectPostType::POST_TYPE, array( $this, 'on_change' ) );
		add_action( 'before_delete_post', array( $this, 'on_delete' ) );
	}

	/**
	 * Pings the frontend after a project is created, updated, trashed or restored.
	 *
	 * WordPress routes trashing and restoring through wp_update_post, so this one
	 * save hook already covers those transitions; only a permanent deletion needs
	 * a hook of its own.
	 *
	 * @param int $post_id Saved post ID.
	 */
	public function on_change( int $post_id ): void {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$this->ping();
	}

	/**
	 * Pings the frontend when a project is permanently deleted.
	 *
	 * A permanent deletion does not fire save_post, so it is handled here.
	 * `before_delete_post` fires for every post type and before the row is gone,
	 * so this checks the type while get_post_type() still resolves; without it a
	 * deleted project would linger on the frontend until the ISR interval elapsed.
	 *
	 * @param int $post_id Post being deleted.
	 */
	public function on_delete( int $post_id ): void {
		if ( ProjectPostType::POST_TYPE === get_post_type( $post_id ) ) {
			$this->ping();
		}
	}

	/**
	 * Sends the non-blocking revalidation ping, unless it is not configured.
	 */
	private function ping(): void {
		$secret   = $this->secret();
		$frontend = $this->frontend_url();

		// Inert without a secret, and never ping ourselves: without a distinct
		// HWR_FRONTEND_URL the frontend resolves to this WordPress site.
		if ( '' === $secret || '' === $frontend || untrailingslashit( home_url() ) === $frontend ) {
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
