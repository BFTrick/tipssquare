<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the id=main div and all content after
 *
 * @package tipssquare
 * @since tipssquare 1.0
 */
?>

	</div><!-- #main .site-main -->

	<footer id="colophon" class="site-footer" role="contentinfo">
		<div class="site-info">
			<?php do_action( 'tipssquare_credits' ); ?>
			<p>TipsSquare was built with all the love in the world by <a href="http://www.patrickrauland.com/" target="_blank">Patrick Rauland</a> at <a href="http://www.kavarna.com/" target="_blank">Kavarna</a> &amp; <a href="http://www.lunacafe.com/" target="_blank">Luna Cafe</a> in sleepy Green Bay, WI.</p>
			<p>TipsSquare is built on the incredible <a href="http://wordpress.org/" title="<?php esc_attr_e( 'A Semantic Personal Publishing Platform', 'tipssquare' ); ?>" rel="generator">WordPress</a> platform</p>
		</div><!-- .site-info -->
	</footer><!-- #colophon .site-footer -->
</div><!-- #page .hfeed .site -->

<?php wp_footer(); ?>

</body>
</html>