<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Featured Slider Template
 *
 * Here we setup all HTML pertaining to the featured slider.
 *
 * @package WooFramework
 * @subpackage Template
 */

/* Retrieve the settings and setup query arguments. */
$settings = array(
				'featured_entries' => '3',
				'featured_order' => 'DESC', 
				'featured_slide_group' => '0', 
				'featured_videotitle' => 'true', 
				'homepage_slider_area_title' => ''
				);
				
$settings = woo_get_dynamic_values( $settings );

$query_args = array(
				'limit' => $settings['featured_entries'], 
				'order' => $settings['featured_order'], 
				'term' => $settings['featured_slide_group']
				);

/* Retrieve the slides, based on the query arguments. */
$slides = woo_featured_slider_get_slides( $query_args );

/* Media settings */
$media_settings = array( 'width' => '320', 'height' => '200' );

if ( 'true' != $settings['featured_videotitle'] ) {
	$media_settings['width'] = '1063';
	$media_settings['height'] = '620'; 
}

/* Begin HTML output. */
if ( false != $slides ) {
	$count = 0;

	$container_css_class = 'flexslider';

	if ( 'true' == $settings['featured_videotitle'] ) {
		$container_css_class .= ' default-width-slide';
	} else {
		$container_css_class .= ' full-width-slide';
	}
?>
<div class="home-section fix">
<?php
if ( '' != $settings['homepage_slider_area_title'] ) {
?>
	<header>
		<h1><?php echo esc_html( $settings['homepage_slider_area_title'] ); ?></h1>
	</header>
<?php
}
?>		
	<div id="featured-slider" class="home-section <?php echo esc_attr( $container_css_class ); ?>">
	
		<ul class="slides">
	<?php
		foreach ( $slides as $k => $post ) {
			setup_postdata( $post );
			$count++;
	
			$url = get_post_meta( get_the_ID(), 'url', true );
			$use_slide_background = get_post_meta( get_the_ID(), '_use_slide_background', true );
			$title = get_the_title();
			if ( $url != '' ) {
				$title = '<a href="' . esc_url( $url ) . '" title="' . esc_attr( $title ) . '">' . $title . '</a>';
			}
	
			$css_class = 'slide-number-' . esc_attr( $count );
	
			$slide_media = '';
			$embed = woo_embed( 'width=' . intval( $media_settings['width'] ) . '&height=' . intval( $media_settings['height'] ) . '&class=slide-video' );
			if ( '' != $embed ) {
				$css_class .= ' has-video';
				$slide_media = $embed;
			} else {
				if ( 'true' == $use_slide_background ) {
				    $css_class .= ' has-slide-background';
				} else {
				    $image = woo_image( 'width=768&noheight=true&class=slide-image&link=img&return=true' );
				    if ( '' != $image ) {
				    	$css_class .= ' has-image no-video';
				    	$slide_media = $image;
				    } else {
				    	$css_class .= ' no-image no-video';
				    }
				}
			}
			
			$slide_background = '';
			if ( 'true' == $use_slide_background ) {
			    $slide_background = woo_image( 'width=1100&noheight=true&class=slide-background&link=img&return=true' );
			}
	?>
			<li class="slide <?php echo esc_attr( $css_class ); ?>">
				<?php
					echo $slide_background;
					if ( '' != $slide_media ) {
						echo '<div class="slide-media">' . $slide_media . '</div><!--/.slide-media-->' . "\n";
					}
				?>
				<?php if ( '' == $embed || ( '' != $embed && 'true' == $settings['featured_videotitle'] ) ) { ?>
				<div class="slide-content">
					<header><h1><?php echo $title; ?></h1></header>
					<div class="entry"><?php the_content(); ?></div><!--/.entry-->
				</div><!--/.slide-content-->
				<?php } ?>
			</li>
	<?php } wp_reset_postdata(); ?>
		</ul>
		
	</div><!--/#featured-slider-->
	<?php
	} else {
		echo do_shortcode( '[box type="info"]' . __( 'Please add some slides in the WordPress admin to show in the Featured Slider.', 'woothemes' ) . '[/box]' );
	}
?>
</div>