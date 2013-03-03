<?php
/**
 * The template for displaying product content within loops.
 *
 * Override this template by copying it to yourtheme/woocommerce/content-product.php
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

global $product, $woocommerce_loop;

// Store loop count we're currently on
if ( empty( $woocommerce_loop['loop'] ) )
	$woocommerce_loop['loop'] = 0;

// Store column count for displaying the grid
if ( empty( $woocommerce_loop['columns'] ) )
	$woocommerce_loop['columns'] = apply_filters( 'loop_shop_columns', 4 );

// Ensure visibilty
if ( ! $product->is_visible() )
	return;

// Increase loop count
$woocommerce_loop['loop']++;
?>
<li class="product <?php
	if ( $woocommerce_loop['loop'] % $woocommerce_loop['columns'] == 0 )
		echo 'last';
	elseif ( ( $woocommerce_loop['loop'] - 1 ) % $woocommerce_loop['columns'] == 0 )
		echo 'first';
	?>">

	<?php do_action( 'woocommerce_before_shop_loop_item' ); ?>

	<div class="image-wrap">

	    <?php woocommerce_show_product_sale_flash( $post, $_product ); ?>
	
	    <?php if (has_post_thumbnail( $loop->post->ID )) { ?>
	    	<a href="<?php echo esc_url( get_permalink( $loop->post->ID ) ); ?>" title="<?php echo esc_attr($loop->post->post_title ? $loop->post->post_title : $loop->post->ID); ?>">
	    		<?php echo get_the_post_thumbnail( $loop->post->ID, 'shop_catalog' ); ?>
	    	</a>
	    <?php } 
	    	else {
	    		echo '<img src="' . esc_url( woocommerce_placeholder_img_src() ) . '" alt="Placeholder" />';
	    	}
	    ?>
	    
	    <ul class="product-details">
	    		<li class="details"><a class="button details" href="<?php echo esc_url( get_permalink( $loop->post->ID ) ); ?>" title="<?php echo esc_attr($loop->post->post_title ? $loop->post->post_title : $loop->post->ID); ?>"><?php _e('View Details' ,'woothemes'); ?></a></li>
	    		<li class="price-wrap"><a href="<?php echo esc_url( get_permalink( $loop->post->ID ) ); ?>" title="<?php echo esc_attr($loop->post->post_title ? $loop->post->post_title : $loop->post->ID); ?>"><span class="price"><strong><?php echo $product->get_price_html(); ?></strong></span></a></li>
	    		<li class="cart"><?php woocommerce_template_loop_add_to_cart( $loop->post, $product ); ?></li>
	    </ul>
	    
	</div>
	
<?php
	/**
	 * woocommerce_before_shop_loop_item_title hook
	 *
	 * @hooked woocommerce_show_product_loop_sale_flash - 10
	 * @hooked woocommerce_template_loop_product_thumbnail - 10
	 */
	do_action( 'woocommerce_before_shop_loop_item_title' );
?>

	<h3><a href="<?php echo esc_url( get_permalink( $loop->post->ID ) ); ?>" title="<?php echo esc_attr($loop->post->post_title ? $loop->post->post_title : $loop->post->ID); ?>"><?php echo get_the_title(); ?></a></h3>
	
<?php
	/**
	 * woocommerce_after_shop_loop_item_title hook
	 *
	 * @hooked woocommerce_template_loop_price - 10
	 */
	do_action( 'woocommerce_after_shop_loop_item_title' );
?>
<?php do_action( 'woocommerce_after_shop_loop_item' ); ?>
</li>