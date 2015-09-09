<?php
require_once('kbcs-config.php');	//include config

if(!class_exists('KBCS_Custom_Feeds')) {
	
	class KBCS_Custom_Feeds { 
	
		protected $feed_slug;	//feed endpoint
		protected $episode_page_slug;	//path for episode-specific page
		protected $orig_feed_num;	//default num items per feed
		protected $cron_job_name;	//cron job name
		protected $cron_interval;	//cron interval in minutes
		protected $cron_interval_name;	//custom cron interval name
		protected $aggregate_program_types;	//program types we know to pregenerate a cache for
		
		function __construct() {			
			
			//set variables to use in class
			$this->feed_slug = KBCS_Config::get_feed_slug();
			$this->episode_page_slug = KBCS_Config::get_episode_page_slug();
			$this->orig_feed_num = get_option('posts_per_rss');
			$this->cron_job_name = KBCS_Config::get_cron_job_name();
			$this->cron_interval = KBCS_Config::get_cron_interval();
			$this->cron_interval_name = KBCS_Config::get_cron_interval_name();
			$this->aggregate_program_types = KBCS_Config::get_aggregate_program_types();
			
			//add action and filter needed
			add_filter( 'pre_option_posts_per_rss', array( $this, 'kcf_posts_per_rss') );
			add_action( 'init', array($this, 'kcf_add_feed'));
			add_filter( 'cron_schedules', array($this, 'kcf_add_cron_interval') );	//set cron job interval
			add_action( $this->cron_job_name, array($this, 'kcf_generate_all_feed_objects'));
		}
	
		/**
		* Activation and deactivation hooks should probably be abstracted out rather than being in just one of the classes, 
		* but that also adds redundancy. And the other class doesn't currently have activation/deactivation task.
		* Basically, don't judge me, future developer.
		**/
		//do activation related events
		function kcf_activation(){
			if ( ! wp_next_scheduled( $this->cron_job_name ) ) {
				wp_schedule_event(time(), $this->cron_interval_name, $this->cron_job_name);
			}
			//set option on install that can be used later to test whether to flush rewrite rules
			add_option(KBCS_Config::get_option_install_name(), true);
		}
		
		//do deactivation events
		function kcf_deactivation(){
			wp_clear_scheduled_hook($this->cron_job_name);
			delete_option(KBCS_Config::get_option_install_name());
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
			if ( !empty($_GET["itemCount"]) ) {
  				$num = intval($_GET["itemCount"]);
			}
			
			$post_type = "programs";
			$taxonomy = "";
			$term = "";
			$query_program_type = get_query_var('program_type');
			$query_post_type = get_query_var('post_type');
			
			//include appropriate feed template file
			if(!empty($query_program_type)){
				include dirname( __FILE__ ) . '/templates/programtype-xml.php';
			} else if ( isset($query_post_type) && $query_post_type == "programs") {
				include dirname( __FILE__ ) . '/templates/programs-xml.php';
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
				//Oh, hey, look at this weird override value set at an arbitrarily high value! 
				//This overrides any other supplied value in the case that this is an aggregate/program type feed call.
				//This needs to be set at a high enough value to ensure we get all the programs of a specific category.
				return 100;
			}
    		return $option_name;
		}
		
		//create custom interval for cron job
		function kcf_add_cron_interval($interval) {
    		$interval[$this->cron_interval_name] = array('interval' => $this->cron_interval*60, 'display' => 'KBCS App Customizations plugin interval');
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
			);
			
			//do custom query
			$cust_query = new WP_Query($args);

			$program_url = 'http://kbcsweb.bellevuecollege.edu/play/api/shows/?programID=%d&pageSize=%d'; //Playlist Center API URL
			$audio_url = 'http://kbcsweb.bellevuecollege.edu/playlist/audioarchive/%s-01.mp3'; //template for archive audio filename
			
			//loop through all programs of this program type, get episodes from Playlist Center, and add to episode array
			$episode_array = array();
			$done_array = array();
			while ( $cust_query->have_posts() ) {
  				$cust_query->the_post();
				$wp_post_id = get_the_ID();
  				$program_id = get_post_meta($wp_post_id, 'programid_mb', true);
				
				//only do API call if program id exists and is unique (i.e. hasn't been called already)
				if ( !empty($program_id) && !in_array($program_id, $done_array) )
				{
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
					
					$done_array[] = $program_id;	//add program id to processed array
				}
			}
			
			//reset original post data
			wp_reset_postdata();

			//Do key-ed sort (since start is used as key) by start date reverse
			krsort($episode_array);
			
			//cut down to only 200 items before getting content length (in bytes)
			//Re-looping is super unideal, but better to get the length here and have it cached than 
			//doing when feed is requested
			$episode_slice = array_slice($episode_array, 0, 200);
			$slice_array = array();
			foreach ( $episode_slice as $result ) {
				$clength = $this->kcf_get_remote_filesize(sprintf($audio_url, date_format(date_create($result['start']), 'YmdHi')));
				$result['content_length'] = $clength;
				$slice_array[$result['start']] = $result;
			}
			
			//add episode object for this program type to Wordpress long-term cache
			set_transient("kcf_object_".$prog_type, $slice_array, 3605);
		}
		
		/**
		* Get content length in bytes of show audio
		* Does a headers-only request to remote file then reads
		* content-length header of response.
		**/
		function kcf_get_remote_filesize($url)
		{
    		$head = array_change_key_case(get_headers($url, 1));
    		// content-length of download (in bytes), read from Content-Length: field
    		$clen = isset($head['content-length']) ? $head['content-length'] : 0;
			return $clen;
		}
	}
}