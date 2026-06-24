<?php
/**
 * Server-side render for the Table of Contents block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 *
 * @package TOCflow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();
if ( ! $post_id ) {
	return;
}

// Which heading levels did the author choose to show?
$levels = array();
if ( ! empty( $attributes['showH2'] ) ) {
	$levels[] = 2;
}
if ( ! empty( $attributes['showH3'] ) ) {
	$levels[] = 3;
}
if ( ! empty( $attributes['showH4'] ) ) {
	$levels[] = 4;
}
if ( empty( $levels ) ) {
	$levels = array( 2 );
}

// Pull the full heading map, then keep only the selected levels.
$all      = tocflow_get_all_headings( $post_id );
$filtered = array_values(
	array_filter(
		$all,
		function ( $heading ) use ( $levels ) {
			return in_array( $heading['level'], $levels, true );
		}
	)
);

if ( empty( $filtered ) ) {
	return; // No matching headings: render nothing.
}

// Normalize the real heading levels (e.g. 2, 3) down to sequential depths
// (1, 2) so nesting looks clean even when a level is skipped.
$present = array_values( array_unique( wp_list_pluck( $filtered, 'level' ) ) );
sort( $present );
$depth_map = array_flip( $present );

foreach ( $filtered as &$heading ) {
	$heading['level'] = $depth_map[ $heading['level'] ] + 1;
}
unset( $heading );

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'tocflow' ) );
$list_tag           = ! empty( $attributes['ordered'] ) ? 'ol' : 'ul';
$title              = isset( $attributes['title'] ) ? $attributes['title'] : '';
$label              = '' !== $title ? $title : __( 'Table of Contents', 'tocflow' );
?>
<?php // get_block_wrapper_attributes() escapes its own values, so it is output directly. ?>
<nav <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( $label ); ?>">
	<?php if ( '' !== $title ) : ?>
		<p class="tocflow__title"><?php echo esc_html( $title ); ?></p>
	<?php endif; ?>
	<?php
	// Markup is assembled with escaped values inside tocflow_render_list().
	echo tocflow_render_list( $filtered, $list_tag ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</nav>
