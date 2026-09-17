<?php
/**
 * Internationalization.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio;

/**
 * Loads the plugin text domain so PHP strings can be translated.
 *
 * Hooked to init, the point at which WordPress expects translations to be
 * available. Loading earlier triggers the "translation loaded too early"
 * notice added in WordPress 6.7.
 */
final class I18n {

	/**
	 * Registers the hook that loads the text domain.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Loads translations for the plugin text domain.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'headless-portfolio',
			false,
			dirname( plugin_basename( HWR_PLUGIN_FILE ) ) . '/languages'
		);
	}
}
