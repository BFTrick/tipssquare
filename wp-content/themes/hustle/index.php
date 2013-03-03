<?php
// File Security Check
if ( ! function_exists( 'wp' ) && ! empty( $_SERVER['SCRIPT_FILENAME'] ) && basename( __FILE__ ) == basename( $_SERVER['SCRIPT_FILENAME'] ) ) {
    die ( 'You do not have sufficient permissions to access this page!' );
}
?><?php
/**
 * Index Template
 *
 * Here we setup all logic and XHTML that is required for the index template, used as both the homepage
 * and as a fallback template, if a more appropriate template file doesn't exist for a specific context.
 *
 * @package WooFramework
 * @subpackage Template
 */
	get_header();
	global $woo_options;
	
	$settings = array(
				'homepage_enable_intro_message' => 'true', 
				'homepage_enable_blog_posts' => 'true', 
				'homepage_enable_promotion' => 'true', 
				'homepage_enable_featured_products' => 'true',
				'homepage_enable_features' => 'true',
				'homepage_enable_testimonials' => 'true',
				'homepage_number_of_features' => 3, 
				'homepage_number_of_testimonials' => 3, 
				'homepage_features_area_title' => '', 
				'homepage_testimonials_area_title' => ''
				);
					
	$settings = woo_get_dynamic_values( $settings );
	
?>

    <div id="content" class="col-full">
    
    	<?php woo_main_before(); ?>
    
		<section id="main" class="col-full fullwidth">      
		<?php if ( is_home() && ! dynamic_sidebar( 'homepage' ) ) {
			if ( 'true' == $settings['homepage_enable_intro_message'] ) {
				get_template_part( 'includes/intro-message' );
			}
			
			if ( is_woocommerce_activated() && 'true' == $settings['homepage_enable_featured_products'] ) {
				get_template_part( 'includes/featured-products' );
			}

			if ( 'true' == $settings['homepage_enable_features'] ) {
				$args = array( 'title' => $settings['homepage_features_area_title'], 'size' => 235, 'per_row' => 3, 'limit' => $settings['homepage_number_of_features'] );
				$args['before'] = '<section id="features" class="home-section widget_woothemes_features">';
				$args['after'] = '</section>';
				$args['before_title'] = '<header class="block"><h1>';
				$args['after_title'] = '</h1></header>';
				
				do_action( 'woothemes_features', $args );
			}		

			// Featured Slider.
			woo_featured_slider_loader();
			
			if ( 'true' == $settings['homepage_enable_testimonials'] ) {
				$args = array( 'title' => $settings['homepage_testimonials_area_title'], 'size' => 80, 'per_row' => 3, 'limit' => $settings['homepage_number_of_testimonials'] );
				$args['before'] = '<section id="testimonials" class="home-section widget_woothemes_testimonials">';
				$args['after'] = '</section>';
				$args['before_title'] = '<header class="block"><h1>';
				$args['after_title'] = '</h1></header>';
				
				do_action( 'woothemes_testimonials', $args );
			}

			if ( 'true' == $settings['homepage_enable_blog_posts'] ) {
				get_template_part( 'includes/blog-posts' );
			}

			if ( 'true' == $settings['homepage_enable_promotion'] ) {
				get_template_part( 'includes/promotion' );
			}

    	?>
		<?php } ?>    
		</section><!-- /#main -->
		
		<?php woo_main_after(); ?>

    </div><!-- /#content -->
		
<?php get_footer(); ?>