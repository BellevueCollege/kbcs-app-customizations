<?php
if(!class_exists('KBCS_Episode_Page')) {
	
	class KBCS_Episode_Page { 

		function __construct() {			
			//add action and filters needed
			add_action('admin_init', array( $this, 'kep_add_rewrite_rule'));
			add_filter('query_vars', array( $this, 'kep_handle_episodepage_query_vars'));
			add_filter('template_include', array( $this, 'kep_render_page'), 1, 1);
		}
	
		//do activation related events
		function kep_activation(){
			//stuff
		}
		
		//do deactivation events
		function kep_deactivation(){
			flush_rewrite_rules();
		}
		
		//add episode page rewrite rule
		function kep_add_rewrite_rule(){
			add_rewrite_rule('episode-page/([^/]+)/?', 'index.php?episode_page_var=$matches[1]', 'top');
			//flush rewrite rules only once
			if ( get_option('plugin_settings_have_changed') == true ) {
        		flush_rewrite_rules(false);
        		update_option('plugin_settings_have_changed', false);
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
        		return dirname(__FILE__) . '/templates/episode-page.php'; //='.$show_id;
    		}
    		return $template;
		}
	}
}