<?php
/**
 * On-demand frontend revalidation.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio;

use Hwr\Portfolio\PostType\ProjectPostType;
use WP_Post;

/**
 * Tells the frontend to revalidate cached project data when a project changes.
 *
 * Closes the on-demand loop with the Next webhook: when a published project
 * changes or is deleted it POSTs to {frontend}/api/revalidate with the shared
 * secret, so published changes and removals appear immediately instead of
 * waiting out the ISR interval. It is inert when the secret is not configured
 * (HWR_REVALIDATE_SECRET / the hwr_revalidate_secret filter) or when no distinct
 * frontend URL is set, so it never pings this WordPress site.
 */
final class Revalidator {

	/**
	 * Registers the status-change and delete hooks.
	 */
	public function register_hooks(): void {
		add_action( 'transition_post_status', array( $this, 'on_transition' ), 10, 3 );
		add_action( 'before_delete_post', array( $this, 'on_delete' ) );
	}

	/**
	 * Pings the frontend when a project enters, leaves or changes while published.
	 *
	 * Only published projects reach the frontend, so a change matters only when
	 * the project is or was published: publishing, editing a published project,
	 * unpublishing, trashing and restoring all pass through here, while autosaves,
	 * revisions and draft-only saves do not bust the cache. Trashing and restoring
	 * route through wp_update_post, so this one hook covers them; only a permanent
	 * deletion needs a hook of its own.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Previous post status.
	 * @param WP_Post $post       Post being transitioned.
	 */
	public function on_transition( string $new_status, string $old_status, WP_Post $post ): void {
		if ( ProjectPostType::POST_TYPE !== $post->post_type ) {
			return;
		}

		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
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
		$secret = $this->secret();

		// Inert without a secret, and never ping ourselves: without a distinct
		// frontend the URL resolves to this WordPress site.
		if ( '' === $secret || ! Frontend::is_distinct() ) {
			return;
		}

		// Non-blocking so the editor save is never slowed by the frontend.
		wp_remote_post(
			Frontend::url() . '/api/revalidate',
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
}
