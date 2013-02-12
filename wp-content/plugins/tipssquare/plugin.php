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

	// foursquare api variables
	private $fsVenuesApiBaseUri = "https://api.foursquare.com/v2/venues";
	private $fsTipsApiPage = "tips";
	private $fsTipsSortDirection = "recent";

	// foursquare app credentials
	private $fsClientId = "UQ1ZQLV4AHI34LGUBXNXFALIS1AY0NYXPIJE1GUFMV1PVRQG";
	private $fsClientSecret = "FW2FYYNFQZG1AQHFBOTIOUEQPEIDONVY5KZVUORK2QENKNC1";

	public function __construct() 
	{
		add_action( 'init', array( &$this, 'init' ) );
	}


	// initialize plugin
	public function init() 
	{	
		// create venue custom post type 
		$this->create_venue_post_type();

		// edit the ts_venue edit page column headers 
		add_filter ("manage_edit-ts_venue_columns", array( &$this, 'ts_venue_edit_columns' ) );

		// edit the ts_venue edit page column values
		add_action ("manage_posts_custom_column", array( &$this, 'ts_venue_custom_columns' ) );

		// hide extra publishing settings
		add_action('admin_head-post.php', array( &$this, 'ts_venue_hide_publishing_actions' ) );
		add_action('admin_head-post-new.php', array( &$this, 'ts_venue_hide_publishing_actions' ) );

		// change the default email preferences
		add_filter( 'wp_mail_from', array( &$this, 'just_use_my_email' ) );
		add_filter( 'wp_mail_from_name', array( &$this, 'just_use_my_email_name' ) );

		// run the main function of the program
		// TODO - fire off this method in WP Cron instead of on every page load
		$this->run();

		// add functionality for user permissions (capabilities) for the venue custom post type
		add_filter( 'map_meta_cap', array( &$this, 'my_map_meta_cap' ), 10, 4 );

		// add assets
		add_action('admin_head', array( &$this, 'load_assets' ) );

		// after this plugin is done loaded leave a hook for other plugins
		do_action('tipssquare_loaded');
	}


	// this functions creates a foursquare venue type
	public function create_venue_post_type() 
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
			'public' => false,
			'show_ui' => true, // UI in admin panel,
			'publicly_queryable' => false,
			'query_var' => false,
			'rewrite' => false,
			'capability_type' => 'venue',
			'capabilities' => array(
				'publish_posts' => 'publish_venues',
				'edit_posts' => 'edit_venues',
				'edit_others_posts' => 'edit_others_venues',
				'delete_posts' => 'delete_venues',
				'delete_others_posts' => 'delete_others_venues',
				'read_private_posts' => 'read_private_venues',
				'edit_post' => 'edit_venue',
				'delete_post' => 'delete_venue',
				'read_post' => 'read_venue',
			),
			'hierarchical' => false,
			'supports' => array(
				'title'
			),
			'menu_position' => 20,
			'register_meta_box_cb' => array( &$this, 'add_fsvenue_post_type_metabox' )
		);

		// register the foursquare venue custom post type
		register_post_type( 'ts_venue', $args );

		// save the foursquare venue custom fields in the custom metabox
		add_action( 'save_post', array( &$this, 'fsvenue_post_save_meta' ), 1, 2 ); 

		// create custom update messages for the foursquare venue custom post type
		add_filter( 'post_updated_messages', array( &$this, 'ts_venue_updated_messages' ) );
	}


	// This function runs the main plugin functionality
	// It...
	// * gets venues to query
	// * fetches tips
	// * queries for existing tips
	// * queries for venue observers
	// * sends email notifications
	public function run()
	{

		// get venues to monitor
		$venuesToMonitor = $this->query_venues_to_monitor();
		
		// if there are no venues to query then don't bother
		if(empty($venuesToMonitor))
		{
			return false;
		}
		
		// loop through each venue and process it
		foreach ($venuesToMonitor as $key => $venueId)
		{
			// get the tips from foursquare
			$tips = $this->fetch_tips($venueId);
			
			// get existing tips from the database
			$existingTipIds = $this->query_existing_tips();

			// get observers for this venue (the people who want to receive notifications)
			$observers = $this->query_venue_observers();

			// check to make sure there is at least one user monitoring this venue
			// if(!empty($observers))
			if(true)
			{
				$this->add_tips_to_db($tips, $existingTipIds, $observers);
			}
			else
			{
				// no post author for a venue? that is very weird...How did this post even get entered into the DB??
				// I should probably log some type of error
				// TODO
			}

		}

	}


	// get venues to monitor
	public function query_venues_to_monitor()
	{
		// initialize venuesToMonitor
		// for now we will programatically set these variables in the code
		// at a future date this will be pulled from the DB
		// $venuesToMonitor = array("4b524e1bf964a5201c7627e3");
		$venues = array();

		// get a list of venues
		$query = get_posts( array('posts_per_page' => 1000000, 'post_type' => 'ts_venue') ); 

		// for each venue get the post meta data and save it into an array
		foreach ($query as $key => $value) 
		{
			$venues[] = get_post_meta($value->ID, "fs_venue_id", true);
		}

		return $venues;
	}


	// This function fetches the tips for all venues
	public function fetch_tips($venueId)
	{
		// assemble the url for the foursquare api call
		// ex. https://api.foursquare.com/v2/venues/40a55d80f964a52020f31ee3/tips?sort=recent&v=yyyymmdd&client_id=aaaaabbbbbbcccc11113344kkd8did&client_secret=bbbbbcccccdddddeeeeeee858587guuguuuu999999
		$apiUrl = $this->fsVenuesApiBaseUri . "/" . $venueId . "/" . $this->fsTipsApiPage . "?sort=" . $this->fsTipsSortDirection . "&v=" . date("Ymd") . "&client_id=" . $this->fsClientId . "&client_secret=" . $this->fsClientSecret;

		// get the response from the foursquare API
		$ApiResultString = file_get_contents($apiUrl);

		// parse the response
		$ApiResultString = json_decode($ApiResultString);
		
		// if we don't get a 200 response (OK) then throw an error
		if ($ApiResultString->meta->code != 200)
		{
			// print error
			// TODO
			return false;
		}

		// if we get less than 1 response don't bother
		if ($ApiResultString->response->tips->count < 1)
		{
			return false;
		}
		
		// save the tips out of the api response
		$tips = $ApiResultString->response->tips->items;

		return $tips;
		
	}


	// get existing tips out of DB
	public function query_existing_tips()
	{
		// right now we have a limit of 1,000,000 tips, after that this program will start creating duplicate tips
		$existingTips = get_posts( array('posts_per_page' => 1000000, 'post_type' => 'foursquare_tip') );  
		
		// put the existing tip ids into a convenient array for future use
		$existingTipIds = array();
		if(!empty($existingTips))
		{
			foreach ($existingTips as $key => $value)
			{
				$existingTipIds[] = $value->id;
			}
		}

		return $existingTipIds;

	}


	// get the venue's observer's (the people that want to receive notifications about this venue)
	public function query_venue_observers()
	{
		global $wpdb;

		// Pseudo code for the query:
		// 	Find the email of the post author of a certain venue
		$observers = $wpdb->get_results( 
			"SELECT user_email, display_name
			FROM $wpdb->users 
			WHERE ID IN(
				SELECT post_author 
				FROM $wpdb->posts 
				WHERE ID IN(
					SELECT post_id
					FROM $wpdb->postmeta 
					WHERE (meta_key = 'fs_venue_id' AND meta_value = '".$venueId."')
				)
			)" 
		);

	}


	// add venue tips to the database
	public function add_tips_to_db($tips, $existingTipIds, $observers)
	{
		// loop through each tip and add it to DB if it isn't already there
		foreach ($tips as $tipKey => $tip)
		{
			// see if the tip already exists
			$tipAlreadyExists = in_array($tip->id, $existingTipIds);

			// if no post exists add it to the DB & email it!
			if(!$tipAlreadyExists)
			{
				$newPost = array(
					'post_content'		=> $tip->text,
					'post_date'			=> date('Y-m-d H:i:s', $tip->createdAt),
					'post_date_gmt'		=> date('Y-m-d H:i:s', $tip->createdAt),
					'post_status'		=> "publish",
					'post_title'		=> $tip->id,
					'post_type'			=> 'foursquare_tip',
				);
				$post_id = wp_insert_post($newPost, true);

				// add the meta data
				add_post_meta($post_id, "canonicalUrl", $tip->canonicalUrl);
				add_post_meta($post_id, "photourl", $tip->photourl);
				add_post_meta($post_id, "likes", $tip->likes);
				add_post_meta($post_id, "id", $tip->id);

				// send email if the tip was recent
				if(true)
				{
					$this->send_tip_notification_emails($observers);
				}
			}

		}
	}


	// prepare and send all of the tip notification emails
	public function send_tip_notification_emails($observers)
	{
		// write email content
		$content = $this->generate_tip_notification_email_content();

		// production email
		// wp_mail($observers, 'Foursquare Tip Notification', $content, $headers);

		// test email
		wp_mail("bftrick@gmail.com", 'Foursquare Tip Notification', $content);
	}


	// generate tip notification email content
	public function generate_tip_notification_email_content()
	{
		$message = "Hi there,";
		$message .= "\r\r";
		$message .= "We still need to write the content in the tip notification emails. Don't worry it's coming soon!";
		
		return $message;
	}


	// change the default From email address
	public function just_use_my_email()
	{
		return 'noreply@tipssquare.com';
	}


	// change the default email name
	public function just_use_my_email_name()
	{
		return 'TipsSquare';
	}

	


	// edit the columns for the ts_venue custom post type
	public function ts_venue_edit_columns($columns) 
	{
		$columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => "Title",
			"ts_venue_fsid" => "Foursquare ID",
			);
		return $columns;
	}


	// set values for the custom columns for the ts_venue custom post type
	// we're only modifying the foursquare id field since WP already handes the checkbox & title
	public function ts_venue_custom_columns($column)
	{
		global $post;
		$custom = get_post_custom();
		switch ($column){
			case "ts_venue_fsid":
				$fsVenueId = $custom['fs_venue_id'][0];
				?>
				<a href="https://foursquare.com/v/<?php echo $fsVenueId; ?>" target="_blank"><?php echo $fsVenueId; ?></a>
				<?php
			break;
		}
	}


	// hide the non essential publishing actions in the foursquare venue custom post type
	public function ts_venue_hide_publishing_actions()
	{
		$my_post_type = 'ts_venue';
		global $post;
		if($post->post_type == $my_post_type)
		{
			echo '
			<style type="text/css">
				#misc-publishing-actions, #minor-publishing-actions
				{
					display:none;
				}
			</style>
			';
		}
	}

	
	// add the meta box
	public function add_fsvenue_post_type_metabox() 
	{
		add_meta_box( 'fsvenue_metabox', 'Foursquare Venue ID', array( &$this, 'fsvenue_metabox' ), 'ts_venue', 'normal' );
	}


	// the content for the foursquare venue id metabox for the foursquare venue custom post type
	public function fsvenue_metabox() 
	{
		global $post;

		// Noncename needed to verify where the data originated
		echo '<input type="hidden" name="fsvenue_post_noncename" id="fsvenue_post_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

		// Get the data if its already been entered
		$fs_venue_id = get_post_meta($post->ID, 'fs_venue_id', true);

		// Echo out the field
		?>
		<div class="width_full p_box">
			<p>
				<label>Foursquare ID<br>
					<input type="text" name="fs_venue_id" class="widefat" value="<?php echo $fs_venue_id; ?>" placeholder="ex. 4bb7946eb35776b039d0c701">
				</label>
			</p>
		</div>
		<?php
	}


	// save the metabox data
	public function fsvenue_post_save_meta( $post_id, $post ) 
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
	 
	    $fsvenue_post_meta['fs_venue_id'] = $_POST['fs_venue_id'];
	 
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
	public function ts_venue_updated_messages( $messages ) {
		global $post, $post_ID;

		$messages['ts_venue'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __('Venue updated.', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
			6 => sprintf( __('Venue saved.', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
		);

		return $messages;
	}


	// Add functionality for user permissions (capabilities) for the venue custom post type
	// http://justintadlock.com/archives/2010/07/10/meta-capabilities-for-custom-post-types
	public function my_map_meta_cap( $caps, $cap, $user_id, $args ) {

		// If editing, deleting, or reading a movie, get the post and post type object. 
		if ( 'edit_venue' == $cap || 'delete_venue' == $cap || 'read_venue' == $cap ) 
		{
			$post = get_post( $args[0] );
			$post_type = get_post_type_object( $post->post_type );

			// Set an empty array for the caps. */
			$caps = array();
		}

		// If editing a venue, assign the required capability. 
		if ( 'edit_venue' == $cap ) 
		{
			if ( $user_id == $post->post_author )
			{
				$caps[] = $post_type->cap->edit_posts;
			}
			else
			{
				$caps[] = $post_type->cap->edit_others_posts;
			}
		}
		// If deleting a venue, assign the required capability. 
		elseif ( 'delete_venue' == $cap ) 
		{
			if ( $user_id == $post->post_author )
			{
				$caps[] = $post_type->cap->delete_posts;
			}
			else
			{
				$caps[] = $post_type->cap->delete_others_posts;
			}
		}
		// If reading a private venue, assign the required capability. */
		elseif ( 'read_venue' == $cap ) 
		{
			if ( 'private' != $post->post_status )
			{
				$caps[] = 'read';
			}
			elseif ( $user_id == $post->post_author )
			{
				$caps[] = 'read';
			}
			else
			{
				$caps[] = $post_type->cap->read_private_posts;
			}
		}

		// Return the capabilities required by the user. */
		return $caps;
	}

	public function load_assets( ) 
	{
		// check to make sure we're on the right page
		if ( strpos( $_SERVER[ 'REQUEST_URI' ], 'post_type=ts_venue' ) !== false ) 
		{
			// add some css
			wp_register_style( 'tipssquare_styles', plugins_url('assets/style.css', __FILE__) );
			wp_enqueue_style( 'tipssquare_styles' );
		}
	}
}


// initialize the plugin
new Tipssquare();

// that's all folks!