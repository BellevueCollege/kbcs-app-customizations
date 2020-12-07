<?php
	/*
	Plugin Name: KBCS App Customizations
	Plugin URI: https://github.com/BellevueCollege/kbcs-app-customizations
	Description: Provides additional feed functionality and episode-specific page utilizing the Playlist Center API
	Author: Bellevue College Integration Team
	Version: 1.0.1
	Author URI: http://www.bellevuecollege.edu
	GitHub Plugin URI: bellevuecollege/kbcs-app-customizations
	*/

	defined ( 'ABSPATH' ) OR exit;

	require_once ( 'kbcs-custom-feeds.php' );
	require_once ( 'kbcs-episode-page.php' );

	if ( class_exists ( 'KBCS_Custom_Feeds' ) ) {
		//instantiate class
		$kbcs_custom_feeds = new KBCS_Custom_Feeds();

		//register activation and deactivation hooks
		register_activation_hook ( __FILE__, array( $kbcs_custom_feeds, 'kcf_activation' ) );
		register_deactivation_hook ( __FILE__, array( $kbcs_custom_feeds, 'kcf_deactivation' ) );
	}

	if ( class_exists ( 'KBCS_Episode_Page' ) ) {
		//instantiate class
		$kbcs_episode_page = new KBCS_Episode_Page();
	}