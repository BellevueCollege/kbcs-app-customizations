<?php
/*
Plugin Name: KBCS Custom Feeds
Plugin URI: https://github.com/BellevueCollege/kbcs-custom-feeds
Description: Provides additional feed functionality utilizing the Playlist Center API
Author: Bellevue College Integration Team
Version: 0.1
Author URI: http://www.bellevuecollege.edu
*/

defined( 'ABSPATH' ) OR exit;

if(!class_exists('KBCS_Custom_Feeds')) {
	
	class KBCS_Custom_Feeds { 
	
		protected $feed_slug = 'episodes';
		
		function __construct() {
			//add action and filter needed
			add_action( 'init', array($this, 'kcf_add_feed'));
			add_filter( 'pre_get_posts', array( $this, 'kcf_pre_get_posts' ) );
		}
	
		//add custom feed
		function kcf_add_feed() {
			add_feed($this->feed_slug, array($this, 'kcf_render'));
		}
		
		//render feed
		function kcf_render() {

			global $wp_query;
			//var_dump($wp_query);
			//exit();
			
			//Set alternate item count, if provided
			$num = 10;
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
				//$taxonomy = "program_type";
				//$term = $query_program_type;
				
				/*$query = new WP_Query(
				    array(
				       'post_type'=> $post_type, 
				       'taxonomy'=> $taxonomy, 
				       'term' => $term, 
				       'status' => 'published',
				       'order' => 'desc')
			    );*/
				include dirname( __FILE__ ) . '/templates/kbcs-programtype-xml.php';
			} else if ( isset($query_post_type) && $query_post_type == "programs") {
				include dirname( __FILE__ ) . '/templates/kbcs-programs-xml.php';
			}

			exit();
		}
		
		//change query vars before WP gets posts
		function kcf_pre_get_posts( $query ) {
	
			if ( $query->is_main_query() && $query->is_feed( $this->feed_slug ) ) {
			
				$query->set( 'posts_per_page', -1);
				$query->set( 'posts_per_rss', -1);
				/*$query->set( 'post_status', 'publish');
				if ( get_query_var('taxonomy') == 'program_type' )
					$query->set( 'posts_per_page', -1);*/
				$query->set( 'nopaging', 1 );
			
			}
		}
	}
}
if ( class_exists('KBCS_Custom_Feeds') ) {
	//instantiate class
	$kbcs_custom_feeds = new KBCS_Custom_Feeds();
}