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
	
		protected $feed_slug = 'episodes';
		protected $orig_feed_num;
		protected $cron_job_name = 'kcf_generate_aggregate_feed_objects';
		protected $cron_interval = 1;	//cron interval in minutes
		protected $cron_interval_name = 'kcf_bihourly';
		protected $aggregate_program_types = array('music','news-ideas');	//program types we know to pregenerate a cache for
		
		function __construct() {			
			//add action and filter needed
			$this->orig_feed_num = get_option('posts_per_rss');
			add_filter( 'pre_option_posts_per_rss', array( $this, 'kcf_posts_per_rss') );
			add_action( 'init', array($this, 'kcf_add_feed'));
			add_filter( 'cron_schedules', array($this, 'kcf_add_cron_interval') );	//set cron job interval
			add_action( $this->cron_job_name, array($this, 'kcf_generate_all_feed_objects'));
		}
	
		//do activation related events
		function kcf_activation(){
			if ( ! wp_next_scheduled( $this->cron_job_name ) ) {
				wp_schedule_event(time(), $this->cron_interval_name, $this->cron_job_name);
			}
		}
		
		//do deactivation events
		function kcf_deactivation(){
			wp_clear_scheduled_hook($this->cron_job_name);
			flush_rewrite_rules();
		}
		
		//add custom feed
		function kcf_add_feed() {
			add_feed($this->feed_slug, array($this, 'kcf_render'));
			
			//flush rewrite rules if new feed slug is not yet registered //
			$registered = false;
			$rules = get_option( 'rewrite_rules' );
    		$feed_matches = array_keys( $rules, 'index.php?&feed=$matches[1]' );

    		foreach ( $feed_matches as $feed_slug )
    		{
        		if ( false !== strpos( $feed_slug, $this->feed_slug ) ) {
            		$registered = true;
					break;
				}
    		}

    		// only flush rewrite rules if feed is not yet registered
    		if ( ! $registered ) {
        		flush_rewrite_rules( false );
			}
			//end flush code //
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
		
		//create custom interval for cron job
		function kcf_add_cron_interval($interval) {
    		$interval[$this->cron_interval_name] = array('interval' => $this->cron_interval*60, 'display' => 'Every half hour');
    		return $interval;
		}
		
		//loop through all known used program types to generate cache objects for aggregate feed
		function kcf_generate_all_feed_objects(){
			
			//Generate a cache object for each known aggregate feed type
			foreach ( $this->aggregate_program_types as $type ) {
				$this->kcf_generate_feed_object($type);
			}
		}
		
		/**
		* Generate Wordpress cache object for a given program type.
		* Called by cron job to aggregate and cache episode information.
		* May also be called by feed generation template in the case that a 
		* non-"presaved" category is requested.
		**/
		function kcf_generate_feed_object( $prog_type ) {
			
			//define arguments for custom query
			$args = array(
				'program_type' => $prog_type,
				'taxonomy' => 'program_type',
				'term' => $prog_type,
				'posts_per_page' => 100
				/*'tax_query' => array(
					array(
						'taxonomy' => 'program_type',
						'field'    => 'slug',
						'terms'    => array($prog_type),
					),
				),*/
			);
			
			//do custom query
			$cust_query = new WP_Query($args);

			$program_url = 'http://kbcsweb.bellevuecollege.edu/play/api/shows/?programID=%d&pageSize=%d'; //Playlist Center API URL

			//loop through all programs of this program type, get episodes from Playlist Center, and add to episode array
			$episode_array = array();
			while ( $cust_query->have_posts() ) {
  				$cust_query->the_post();
				$wp_post_id = get_the_ID();
  				$program_id = get_post_meta($wp_post_id, 'programid_mb', true);
  				$content = file_get_contents(sprintf($program_url,$program_id,20));
  				$json = json_decode($content, true);
  
  				if( $json ){
				    foreach( $json as $result ) {
				        //Set correct timezone - is there a way to get this from the server?
				        $timezone = new DateTimeZone("America/Los_Angeles");
				        
				        //Create now date/time object
				        $now = new DateTime();
				        $now->setTimezone($timezone);
				        
				        //Create show date/time object
				        $show_date = new DateTime($result['start'], $timezone);
				
				        //Get timestamps for comparison, skip this result if show happens in future
				        $now_ts = $now->getTimestamp();
				        $show_date_ts = $show_date->getTimestamp();
				
				        if ( ($now_ts - $show_date_ts) < 0 ) {
				          //show happens in the future so skip
				          continue;
				        }
				        else {
				          //add to results
						  $result['wp_post_id'] = $wp_post_id;	//save WP post id so we don't have to query it again later
				          $episode_array[$result['start']] = $result; //add to episode array with 'start' as key so it can be sorted on later
				        }
				    }
  				}
			}
			
			//reset original post data
			wp_reset_postdata();

			//Do key-ed sort (since start is used as key) by start date reverse
			krsort($episode_array);
			
			//add episode object for this program type to Wordpress long-term cache
			set_transient("kcf_object_".$prog_type, $episode_array, 3605);
		}	
	}
}
if ( class_exists('KBCS_Custom_Feeds') ) {
	//instantiate class
	$kbcs_custom_feeds = new KBCS_Custom_Feeds();
	
	//register activation and deactivation hooks
	register_activation_hook(__FILE__, array($kbcs_custom_feeds, 'kcf_activation'));
	register_deactivation_hook(__FILE__, array($kbcs_custom_feeds, 'kcf_deactivation'));
}