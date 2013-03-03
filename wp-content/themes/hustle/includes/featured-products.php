<?php
/**
 * Homepage Shop Panel
 */
 	
	/**
 	* The Variables
 	*
 	* Setup default variables, overriding them if the "Theme Options" have been saved.
 	*/
	
	global $woocommerce, $post;
	
	$settings = array(
					'homepage_number_of_products' => 4,
					'homepage_shop_area_title' => 'Product Showcase'
					);
					
	$settings = woo_get_dynamic_values( $settings );
	
?>
<section id="home-shop" class="home-section woocommerce-columns-4 fix">
				
	<header>
		<?php if ( '' != $settings['homepage_shop_area_title'] ) { ?>
			<h1><?php echo esc_html( $settings['homepage_shop_area_title'] ); ?></h1>
		<?php } ?>
	</header>

	<ul class="products">
<?php
		$number_of_products = $settings['homepage_number_of_products'];
		$args = array( 
			'post_type' => 'product', 
			'posts_per_page' => intval( $number_of_products ), 
			'meta_query' => array(
								'relation' => 'AND', 
								array(
									'key' => '_visibility',
									'value' => array( 'catalog', 'visible' ),
									'compare' => 'IN'
								), 
								array(
									'key' => '_featured',
									'value' => array( 'yes' )
								)
							) 
		);

		$first_or_last = 'first';
		$loop = new WP_Query( $args );
		$query_count = $loop->post_count;
		$count = 0;

		

		while ( $loop->have_posts() ) : $loop->the_post(); $count++;

		if ( function_exists( 'get_product' ) ) {
			$_product = get_product( $loop->post->ID );
		} else { 
			$_product = new WC_Product( $loop->post->ID );
		}
?>
		
				<li class="product<?php if ( $count == 1 ) { echo " first"; } if ( $count == 4 ) { echo " last"; $count = 0; } ?>">
				
					<div class="image-wrap">

						<?php woocommerce_show_product_sale_flash( $post, $_product ); ?>
					
						<?php if (has_post_thumbnail( $loop->post->ID )) { ?>
							<a href="<?php echo esc_url( get_permalink( $loop->post->ID ) ); ?>" title="<?php echo esc_attr($loop->post->post_title ? $loop->post->post_title : $loop->post->ID); ?>">
								<?php echo get_the_post_thumbnail( $loop->post->ID, 'shop_catalog' ); ?>
							</a>
						<?php } 
							else {
								echo '<img src="'.woocommerce_placeholder_img_src().'" alt="Placeholder" />';
							}
						?>
						
						<ul class="product-details">
								<li class="details"><a class="button details" href="<?php echo esc_url( get_permalink( $loop->post->ID ) ); ?>" title="<?php echo esc_attr($loop->post->post_title ? $loop->post->post_title : $loop->post->ID); ?>"><?php _e('View Details' ,'woothemes'); ?></a></li>
								<li class="price-wrap"><a href="<?php echo esc_url( get_permalink( $loop->post->ID ) ); ?>" title="<?php echo esc_attr($loop->post->post_title ? $loop->post->post_title : $loop->post->ID); ?>"><span class="price"><strong><?php echo $_product->get_price_html(); ?></strong></span></a></li>
								<li class="cart"><?php woocommerce_template_loop_add_to_cart( $loop->post, $_product ); ?></li>
						</ul>
						
					</div>
			
					<h3><a href="<?php echo esc_url( get_permalink( $loop->post->ID ) ); ?>" title="<?php echo esc_attr($loop->post->post_title ? $loop->post->post_title : $loop->post->ID); ?>"><?php echo get_the_title(); ?></a></h3>

				</li>

		<?php endwhile; ?>
	</ul><!--/ul.recent-->		    			    		
</section>

<?php wp_reset_postdata(); ?>