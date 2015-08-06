<?php
require_once('kbcs-config.php');	//include config values

if(!class_exists('KBCS_Episode_Page')) {
	
	class KBCS_Episode_Page { 

		protected $page_slug;	//episode page slug
		
		function __construct() {			
			//add action and filters needed
			$this->page_slug = KBCS_Config::get_episode_page_slug();
			add_action('admin_init', array( $this, 'kep_add_rewrite_rule'));
			add_filter('query_vars', array( $this, 'kep_handle_episodepage_query_vars'));
			add_filter('template_include', array( $this, 'kep_render_page'), 1, 1);
		}
	
		//do activation related events
		function kep_activation(){

		}
		
		//do deactivation events
		function kep_deactivation(){
			flush_rewrite_rules();
		}
		
		//add episode page rewrite rule
		function kep_add_rewrite_rule(){
			add_rewrite_rule($this->page_slug .'/([^/]+)/?', 'index.php?episode_page_var=$matches[1]', 'top');
			//flush rewrite rules only once
			if ( get_option(KBCS_Config::get_option_install_name()) == true ) {
        		flush_rewrite_rules(false);
        		update_option(KBCS_Config::get_option_install_name(), false);
    		}
		}
		
		//handle episode number variable passed to page
		function kep_handle_episodepage_query_vars($query_vars)
		{
    		$query_vars[] = 'episode_page_var';
    		return $query_vars;
		}
		
		//render episode page
		function kep_render_page($template)
		{
    		global $wp_query;
 
    		if (isset($wp_query->query_vars['episode_page_var'])) {
				$show_id = $wp_query->query_vars['episode_page_var'];
        		return dirname(__FILE__) . '/templates/episode-page.php';
    		}
    		return $template;
		}
	}
}