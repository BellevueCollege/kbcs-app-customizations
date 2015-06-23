<?php
/*
Plugin Name: KBCS Custom Feeds
Plugin URI: https://github.com/BellevueCollege/kbcs-custom-feeds
Description: Provides additional feed functionality utilizing the Playlist Center API
Author: Bellevue College Integration Team
Version: 0.0.0.1
Author URI: http://www.bellevuecollege.edu
*/

defined( 'ABSPATH' ) OR exit;

if(!class_exists('KBCS_Custom_Feeds')) {
	
	class KBCS_Custom_Feeds { 
	
		protected $feed_slug = 'shows';
		protected $orig_feed_num; 
		
		function __construct() {
			//add action and filter needed
			$this->orig_feed_num = get_option('posts_per_rss');
			add_filter( 'pre_option_posts_per_rss', array( $this, 'kcf_posts_per_rss') );
			add_action( 'init', array($this, 'kcf_add_feed'));
		}
	
		//add custom feed
		function kcf_add_feed() {
			add_feed($this->feed_slug, array($this, 'kcf_render'));
		}
		
		//render feed
		function kcf_render() {

			global $wp_query;
			
			//Set alternate item count, if provided
			$num = $this->orig_feed_num;
			if ( !empty($_REQUEST["itemcount"]) ) {
  				$num = intval($_REQUEST["itemcount"]);
			}
			
			$post_type = "programs";
			$taxonomy = "";
			$term = "";
			$query_program_type = get_query_var('program_type');
			$query_post_type = get_query_var('post_type');
			
			//include appropriate feed template file
			if(!empty($query_program_type)){
				include dirname( __FILE__ ) . '/templates/kbcs-programtype-xml.php';
			} else if ( isset($query_post_type) && $query_post_type == "programs") {
				include dirname( __FILE__ ) . '/templates/kbcs-programs-xml.php';
			}

			exit();
		}
		
		/**
		* Change posts_per_rss value if a program_type feed query
		* This is a work around necessary because of its aggregate nature - i.e. we need to get _all_ 
		* programs in the main query, so that we get all relevant shows/episodes from the Playlist Center.
		*/
		function kcf_posts_per_rss( $option_name ) {
			global $wp_query;
			$query_program_type = $wp_query->get('program_type');
			if ( $wp_query->is_main_query() && $wp_query->is_feed( $this->feed_slug ) && !empty($query_program_type) ) {
				return 100;
			}
    		return $option_name;
		}
		
	}
}
if ( class_exists('KBCS_Custom_Feeds') ) {
	//instantiate class
	$kbcs_custom_feeds = new KBCS_Custom_Feeds();
}