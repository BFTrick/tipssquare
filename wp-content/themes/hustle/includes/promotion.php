<?php
/**
 * Homepage Features Panel
 */
 
	/**
 	* The Variables
 	*
 	* Setup default variables, overriding them if the "Theme Options" have been saved.
 	*/
	
	global $woo_options;
	
	$settings = array(
					'homepage_promotion_title' => '',
					'homepage_promotion_subtext' => '',
					'homepage_promotion_button_text' => '',
					'homepage_promotion_button_url' => ''
					);
					
	$settings = woo_get_dynamic_values( $settings );

?>

	<?php if ( $settings['homepage_promotion_title'] != '' || $settings['homepage_promotion_subtext'] != '' ): ?>

	<section id="promotion" class="home-section">
		
		<div class="left-section">

			<?php if ( $settings['homepage_promotion_title'] != '' ): ?>
				<h2><?php echo stripslashes( $settings['homepage_promotion_title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( $settings['homepage_promotion_subtext'] != '' ): ?>
				<p><?php echo stripslashes( $settings['homepage_promotion_subtext'] ); ?></p>
		<?php endif; ?>

		</div>

		<div class="right-section">
			<?php if ( $settings['homepage_promotion_button_text'] != '' && $settings['homepage_promotion_button_url'] != '' ): ?>
				<a class="button alt large" href="<?php echo esc_url($settings['homepage_promotion_button_url']); ?>"><?php echo stripslashes( $settings['homepage_promotion_button_text'] ); ?></a>
			<?php endif; ?>
		</div>

	</section>
	<?php endif; ?>	