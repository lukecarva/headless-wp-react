<?php
/**
 * Plugin bootstrapper.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

namespace Hwr\Portfolio;

use Hwr\Portfolio\Acf\ProjectFieldGroup;
use Hwr\Portfolio\Blocks\BlockRegistrar;
use Hwr\Portfolio\Editor\ProjectEditorPanel;
use Hwr\Portfolio\GraphQL\ProjectGraphQL;
use Hwr\Portfolio\Meta\ProjectMeta;
use Hwr\Portfolio\PostType\ProjectPostType;
use Hwr\Portfolio\Rest\ProjectsController;

/**
 * Wires the plugin's features to WordPress hooks.
 *
 * Constructs each feature object and lets it register its own hooks, keeping
 * responsibilities isolated and independently testable.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	private function __construct() {}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers every feature's hooks. Intended to run once, on plugins_loaded.
	 */
	public function boot(): void {
		$meta = new ProjectMeta();

		( new I18n() )->register_hooks();
		( new Cors() )->register_hooks();
		( new Revalidator() )->register_hooks();
		( new Preview() )->register_hooks();
		( new FrontendRedirect() )->register_hooks();
		( new ProjectPostType() )->register_hooks();
		$meta->register_hooks();
		( new ProjectFieldGroup() )->register_hooks();
		( new ProjectEditorPanel() )->register_hooks();
		( new ProjectsController( $meta ) )->register_hooks();
		( new ProjectGraphQL( $meta ) )->register_hooks();
		( new BlockRegistrar() )->register_hooks();
	}
}
