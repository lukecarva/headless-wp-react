<?php
/**
 * Enqueues the native React editing panel for projects.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio\Editor;

use Hwr\Portfolio\PostType\ProjectPostType;

/**
 * Loads the project document sidebar panel in the block editor.
 *
 * The panel is authored in React under src/editor and compiled by
 * @wordpress/scripts to build/editor. It is the default editing UI for the
 * project meta. When ACF is active it manages the same fields instead, so this
 * panel stands aside to avoid presenting two editors for one set of values.
 */
final class ProjectEditorPanel {

	/**
	 * Registers the editor asset hook.
	 */
	public function register_hooks(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueues the compiled panel script on the project editor screen.
	 */
	public function enqueue(): void {
		// ACF, when present, provides the editing UI for the same meta keys.
		if ( function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( null === $screen || ProjectPostType::POST_TYPE !== $screen->post_type ) {
			return;
		}

		$asset_path = HWR_PLUGIN_DIR . 'build/editor/index.asset.php';
		if ( ! is_readable( $asset_path ) ) {
			// Not built yet (fresh clone before `pnpm build`); skip quietly.
			return;
		}

		$asset = require $asset_path;

		wp_enqueue_script(
			'hwr-project-editor-panel',
			plugins_url( 'build/editor/index.js', HWR_PLUGIN_FILE ),
			isset( $asset['dependencies'] ) ? (array) $asset['dependencies'] : array(),
			isset( $asset['version'] ) ? (string) $asset['version'] : HWR_PLUGIN_VERSION,
			true
		);
	}
}
