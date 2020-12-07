<?php
/** 
* This class holds the config values for the plugin. If you want to change a setting, this is probably where it is.
* Change with caution as the KBCS app depends on URLs generated from paths/slugs defined here.
**/
if(!class_exists('KBCS_Config')) {
	
	class KBCS_Config { 
		
		protected static $cron_job_name = 'kac_generate_aggregate_feed_objects'; //cron job name
		protected static $cron_interval = 15;	//cron interval in minutes
		protected static $cron_interval_name = 'kac_interval';	//name of custom cron interval
		
		protected static $feed_slug = 'episodes';	//feed endpoint that will be added
		protected static $episode_page_slug = 'episode-page';	//path for episode specific page
		
		protected static $aggregate_program_types = array('music','news-ideas');	//program types to generate WP cache objects for
		
		protected static $option_install_name = 'kbcs_plugin_installed';	//option used to know when installed (for flushing rewrite rules)
		
		protected static $static_content_server_domain = 's.bellevuecollege.edu';	//domain of static content server
		protected static $rss_radiobookmark_namespace = 'http://www.radiobookmark.com/dtds/?-1.0.dtd';	//namespace for radiobookmark element
		
		public static function get_cron_job_name() {
			return self::$cron_job_name;
		}		
		
		public static function get_cron_interval() {
			return self::$cron_interval;
		}
		
		public static function get_cron_interval_name() {
			return self::$cron_interval_name;
		}
		
		public static function get_feed_slug() {
			return self::$feed_slug;
		}
		
		public static function get_episode_page_slug() {
			return self::$episode_page_slug;
		}
		
		public static function get_aggregate_program_types() {
			return self::$aggregate_program_types;
		}
		
		public static function get_option_install_name() {
			return self::$option_install_name;
		}
		
		public static function get_static_content_server_domain() {
			return self::$static_content_server_domain;
		}	
		
		public static function get_rss_radiobookmark_namespace() {
			return self::$rss_radiobookmark_namespace;
		}
	}
}