<?php
/**
 * Points the WordPress preview link at the frontend's draft mode.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio;

use Hwr\Portfolio\PostType\ProjectPostType;
use WP_Post;

/**
 * Rewrites the "Preview" link for projects to the decoupled frontend.
 *
 * When an editor previews a project, WordPress would render the draft through
 * its own theme. Instead, this sends them to the Next.js app's draft entry
 * point ({frontend}/api/draft) with a short lived, HMAC signed token carrying
 * the post id, slug and expiry. The frontend verifies the token, enables Next
 * draft mode and renders the draft from an authenticated read. It is inert when
 * no preview secret or frontend URL is configured, so previews then fall back
 * to WordPress's own rendering.
 */
final class Preview {

	/**
	 * How long a preview link stays valid, in seconds.
	 */
	private const TTL = 300;

	/**
	 * Registers the preview link filter.
	 */
	public function register_hooks(): void {
		add_filter( 'preview_post_link', array( $this, 'filter_preview_link' ), 10, 2 );
	}

	/**
	 * Returns a signed frontend draft URL for a project preview.
	 *
	 * @param string       $link The default preview link.
	 * @param WP_Post|null $post The post being previewed.
	 */
	public function filter_preview_link( string $link, ?WP_Post $post ): string {
		if ( ! $post instanceof WP_Post || ProjectPostType::POST_TYPE !== $post->post_type ) {
			return $link;
		}

		$secret   = $this->secret();
		$frontend = Frontend::url();
		if ( '' === $secret || '' === $frontend ) {
			return $link;
		}

		$id   = (int) $post->ID;
		$slug = '' !== $post->post_name ? $post->post_name : sanitize_title( $post->post_title );
		// An untitled brand new draft has no name yet; fall back to the id so the
		// frontend URL still resolves (the page reads the draft by id anyway).
		if ( '' === $slug ) {
			$slug = (string) $id;
		}
		$exp = time() + self::TTL;

		return add_query_arg(
			array(
				'id'    => $id,
				'slug'  => rawurlencode( $slug ),
				'exp'   => $exp,
				'token' => hash_hmac( 'sha256', $id . '.' . $slug . '.' . $exp, $secret ),
			),
			$frontend . '/api/draft'
		);
	}

	/**
	 * Shared secret used to sign preview tokens (empty disables signed previews).
	 */
	private function secret(): string {
		$default = defined( 'HWR_PREVIEW_SECRET' ) ? (string) HWR_PREVIEW_SECRET : '';

		/**
		 * Filters the preview signing secret.
		 *
		 * @param string $secret
		 */
		return (string) apply_filters( 'hwr_preview_secret', $default );
	}
}
