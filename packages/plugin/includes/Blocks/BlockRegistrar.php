<?php
/**
 * Registers Gutenberg blocks built by @wordpress/scripts.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio\Blocks;

/**
 * Registers the Gutenberg blocks compiled under build/blocks.
 *
 * Blocks are authored in TypeScript in src/blocks and compiled by
 * @wordpress/scripts, which emits each block.json and asset file. Registering
 * from block.json keeps the PHP and JS metadata in a single source.
 */
final class BlockRegistrar {

	/**
	 * Registers the hook that registers the blocks.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Registers each built block from its block.json.
	 */
	public function register(): void {
		$blocks_dir = HWR_PLUGIN_DIR . 'build/blocks';

		if ( ! is_dir( $blocks_dir ) ) {
			// Blocks not built yet (fresh clone before `pnpm build`); skip quietly.
			return;
		}

		foreach ( glob( $blocks_dir . '/*', GLOB_ONLYDIR ) as $block_path ) {
			if ( is_readable( $block_path . '/block.json' ) ) {
				register_block_type( $block_path );
			}
		}
	}
}
