<?php
/*
Plugin Name: tipssquare
Plugin URI: http://www.tipssquare.com
Description: Checks foursquare for tips on specified venues
Version: 1.0
Author: Patrick Rauland
Author URI: http://www.patrickrauland.com
*/

class Tipssquare {
	public function __construct() 
	{
		add_action( 'init', array( &$this, 'init' ) );
	}


	// initialize plugin
	public function init() 
	{	
		// TODO

		// create Foursquare venue custom post type 
		$this->create_fsvenue_post_type();

		// edit the fsvenue edit page column headers 
		add_filter ("manage_edit-fsvenue_columns", "fsvenue_edit_columns");

		// edit the fsvenue edit page column values
		add_action ("manage_posts_custom_column", "fsvenue_custom_columns");

		// after this plugin is done loaded leave a hook for other plugins
		do_action('tipssquare_loaded');

	}


	// this functions creates a foursquare venue type
	public function create_fsvenue_post_type() 
	{
		$labels = array(
			'name' => __( 'Venues' ),
			'singular_name' => __( 'Venue' ),
			'add_new' => __( 'Add venue' ),
			'all_items' => __( 'All venues' ),
			'add_new_item' => __( 'Add venue' ),
			'edit_item' => __( 'Edit venue' ),
			'new_item' => __( 'New venue' ),
			'view_item' => __( 'View venue' ),
			'search_items' => __( 'Search venues' ),
			'not_found' => __( 'No venues found' ),
			'not_found_in_trash' => __( 'No venues found in trash' ),
			'parent_item_colon' => __( 'Parent venue' )
		);
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => false,
			'query_var' => false,
			'rewrite' => false,
			'capability_type' => 'post',
			'hierarchical' => false,
			'supports' => array(
				'title'
			),
			'menu_position' => 20,
			'register_meta_box_cb' => 'add_fsvenue_post_type_metabox'
		);

		// register the foursquare venue custom post type
		register_post_type( 'fsvenue', $args );

		// save the foursquare venue custom fields in the custom metabox
		add_action( 'save_post', 'fsvenue_post_save_meta', 1, 2 ); 

		// create custom update messages for the foursquare venue custom post type
		add_filter( 'post_updated_messages', 'fsvenue_updated_messages' );
	}

}


// initialize the plugin
new Tipssquare();


// add the meta box
function add_fsvenue_post_type_metabox() 
{ 
	add_meta_box( 'fsvenue_metabox', 'Venue ID', 'fsvenue_metabox', 'fsvenue', 'normal' );
}


// the content for the foursquare venue id metabox for the foursquare venue custom post type
function fsvenue_metabox() 
{
	global $post;
	// Noncename needed to verify where the data originated
	echo '<input type="hidden" name="fsvenue_post_noncename" id="fsvenue_post_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

	// Get the data if its already been entered
	$fsvenue_post_name = get_post_meta($post->ID, '_fsvenue_post_name', true);

	// Echo out the field
	?>
	<div class="width_full p_box">
		<p>
			<label>ID<br>
				<input type="text" name="fsvenue_post_name" class="widefat" value="<?php echo $fsvenue_post_name; ?>" placeholder="ex. 4bb7946eb35776b039d0c701">
			</label>
		</p>
	</div>
	<?php
}


// save the metabox data
function fsvenue_post_save_meta( $post_id, $post ) 
{
    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if ( !wp_verify_nonce( $_POST['fsvenue_post_noncename'], plugin_basename(__FILE__) ) ) {
        return $post->ID;
    }
 
    // Is the user allowed to edit the post or page?
    if ( !current_user_can( 'edit_post', $post->ID )){
        return $post->ID;
    }
    // OK, we're authenticated: we need to find and save the data
    // We'll put it into an array to make it easier to loop though.
 
    $fsvenue_post_meta['_fsvenue_post_name'] = $_POST['fsvenue_post_name'];
 
    // Add values as custom fields
    // Cycle through the $fsvenue_post_meta array!
    foreach( $fsvenue_post_meta as $key => $value ) 
    {
		$value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
		if( get_post_meta( $post->ID, $key, FALSE ) ) // If the custom field already has a value
		{ 
			update_post_meta($post->ID, $key, $value);
		} 
		else // If the custom field doesn't have a value 
		{ 
			add_post_meta( $post->ID, $key, $value );
		}
		if( !$value ) // Delete if blank
		{ 
			delete_post_meta( $post->ID, $key );
		}
    }
}


// add filter to ensure the text "Venue", or "venue", is displayed when user updates a venue 
function fsvenue_updated_messages( $messages ) {
	global $post, $post_ID;

	$messages['fsvenue'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __('Venue updated.', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
		6 => sprintf( __('Venue saved.', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
	);

	return $messages;
}


// edit the columns for the foursquare venue custom post type
function fsvenue_edit_columns($columns) 
{
	$columns = array(
		"cb" => "<input type=\"checkbox\" />",
		"title" => "Title",
		"fsvenue_fsid" => "Foursquare ID",
		);
	return $columns;
}


// set values for the custom columns for the foursquare venue custom post type
// we're only modifying the foursquare id field since WP already handes the checkbox & title
function fsvenue_custom_columns($column)
{
	global $post;
	$custom = get_post_custom();
	switch ($column){
		case "fsvenue_fsid":
			$fsVenueId = $custom['_fsvenue_post_name'][0];
			?>
			<a href="https://foursquare.com/v/<?php echo $fsVenueId; ?>" target="_blank"><?php echo $fsVenueId; ?></a>
			<?php
		break;
	}
}