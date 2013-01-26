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

	public function init() 
	{	
		// TODO
	}

}

new Tipssquare();