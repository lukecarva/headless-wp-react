<?php
/**
 * Plugin Name: HWR Demo Seed (local only)
 * Description: Seeds a few demo Projects on wp-env so the frontend has data. Local development only.
 *
 * Mapped into wp-content/mu-plugins by .wp-env.json. Runs once, guarded by an option.
 *
 * @package Hwr\Portfolio
 */

declare( strict_types=1 );

add_action(
	'init',
	static function (): void {
		// Only seed in local/dev, and only once.
		if ( 'local' !== wp_get_environment_type() ) {
			return;
		}
		if ( get_option( 'hwr_demo_seeded' ) ) {
			return;
		}
		if ( ! post_type_exists( 'project' ) ) {
			return; // Main plugin not active yet.
		}

		$samples = array(
			array(
				'title'    => 'Headless Commerce Platform',
				'role'     => 'Senior Full-stack (WP + React)',
				'stack'    => 'WordPress, WPGraphQL, Next.js, TypeScript',
				'repo_url' => 'https://github.com/example/headless-commerce',
				'featured' => '1',
				'excerpt'  => 'Decoupled storefront: WordPress as the catalog/CMS, Next.js frontend with ISR.',
			),
			array(
				'title'    => 'Editorial Design System',
				'role'     => 'Frontend Lead',
				'stack'    => 'React, Gutenberg, TypeScript, Storybook',
				'repo_url' => 'https://github.com/example/editorial-ds',
				'featured' => '1',
				'excerpt'  => 'Custom Gutenberg blocks and a shared React component library for editors.',
			),
			array(
				'title'    => 'Membership Portal',
				'role'     => 'Full-stack Developer',
				'stack'    => 'WordPress, REST API, React',
				'repo_url' => 'https://github.com/example/membership-portal',
				'featured' => '0',
				'excerpt'  => 'Gated content portal backed by the WordPress REST API.',
			),
		);

		foreach ( $samples as $sample ) {
			$post_id = wp_insert_post(
				array(
					'post_type'    => 'project',
					'post_status'  => 'publish',
					'post_title'   => $sample['title'],
					'post_excerpt' => $sample['excerpt'],
					'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $sample['excerpt'] ) . '</p><!-- /wp:paragraph -->',
				)
			);

			if ( is_int( $post_id ) && $post_id > 0 ) {
				update_post_meta( $post_id, 'hwr_role', $sample['role'] );
				update_post_meta( $post_id, 'hwr_stack', $sample['stack'] );
				update_post_meta( $post_id, 'hwr_repo_url', $sample['repo_url'] );
				update_post_meta( $post_id, 'hwr_featured', $sample['featured'] );
			}
		}

		update_option( 'hwr_demo_seeded', 1 );
	},
	20
);
