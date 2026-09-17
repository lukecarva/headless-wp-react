<?php
/**
 * Server-side render for the `hwr/project-showcase` block.
 *
 * @package Hwr\Portfolio
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content (unused; block has no inner blocks).
 * @var WP_Block $block      Block instance.
 */

declare( strict_types=1 );

use Hwr\Portfolio\Meta\ProjectMeta;
use Hwr\Portfolio\PostType\ProjectPostType;

$featured_only = ! empty( $attributes['featuredOnly'] );
$count         = isset( $attributes['count'] ) ? absint( $attributes['count'] ) : 3;
// Clamp to the editor UI's range (RangeControl 1 to 12) so crafted block
// markup cannot turn this into an unbounded query.
$count   = max( 1, min( 12, $count ) );
$heading = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';

$query_args = array(
	'post_type'      => ProjectPostType::POST_TYPE,
	'post_status'    => 'publish',
	'posts_per_page' => $count,
);

if ( $featured_only ) {
	$query_args['meta_key']   = ProjectMeta::FEATURED;
	$query_args['meta_value'] = '1';
}

$projects = new WP_Query( $query_args );
$meta     = new ProjectMeta();

if ( ! $projects->have_posts() ) {
	printf(
		'<p %s>%s</p>',
		get_block_wrapper_attributes(),
		esc_html__( 'No projects found.', 'headless-portfolio' )
	);
	return;
}
?>
<section <?php echo get_block_wrapper_attributes(); ?>>
	<?php if ( '' !== $heading ) : ?>
		<h2 class="hwr-showcase__heading"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<ul class="hwr-showcase__grid">
		<?php
		while ( $projects->have_posts() ) :
			$projects->the_post();
			$fields = $meta->read( get_the_ID() );
			?>
			<li class="hwr-showcase__item">
				<a class="hwr-showcase__link" href="<?php the_permalink(); ?>">
					<h3 class="hwr-showcase__title"><?php the_title(); ?></h3>
				</a>
				<?php if ( '' !== $fields['role'] ) : ?>
					<p class="hwr-showcase__role"><?php echo esc_html( $fields['role'] ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $fields['stack'] ) ) : ?>
					<ul class="hwr-showcase__stack">
						<?php foreach ( $fields['stack'] as $tech ) : ?>
							<li class="hwr-showcase__tech"><?php echo esc_html( $tech ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</li>
			<?php
		endwhile;
		wp_reset_postdata();
		?>
	</ul>
</section>
