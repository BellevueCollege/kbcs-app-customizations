<?php
	
global $wp_query;

if ( isset($wp_query->query_vars['episode_page_var']) && is_numeric($wp_query->query_vars['episode_page_var']) ) {
	$ep_id = (int)$wp_query->query_vars['episode_page_var'];
	//now do stuff
	
	//call the JSON API	
	$api_url = "http://kbcsweb.bellevuecollege.edu/play/api/shows/%d";

  	$content = file_get_contents(sprintf($api_url, $ep_id));
  	$json = json_decode($content, true);
	if ( $json ){
		$result = $json[0];
		$title = $result['title'].' - '.date_format(date_create($result['start']), "m/d/y");
		//var_dump($result);
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<title><?php echo $title?> - KBCS</title>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />

	<link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/css/bootstrap.css">
	<link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/css/bootstrap-responsive.css">
	<link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/css/font-awesome.css">
	<link href="<?php bloginfo('stylesheet_directory'); ?>/css/jplayer/blue.monday/jplayer.blue.monday.css" rel="stylesheet" type="text/css" />

	<script type='text/javascript' src='http://kbcs.fm/wp-includes/js/jquery/jquery.js?ver=1.10.2'></script>
	<script>jQueryWP = jQuery;</script>	
	<script type='text/javascript' src='http://s.bellevuecollege.edu/kbcs/themes/kbcs/js/moment.min.js?ver=3.7.8'></script>
	<script type='text/javascript' src='http://s.bellevuecollege.edu/kbcs/themes/kbcs/js/jquery.jplayer.min.js?ver=3.7.8'></script>
	<script type='text/javascript' src='http://s.bellevuecollege.edu/kbcs/themes/kbcs/js/jplayer.playlist.min.js?ver=3.7.8'></script>
	<script type="text/javascript">
	
		jQuery(document).ready(function(){
		      jQuery("#jquery_jplayer_1").jPlayer({
		        ready: function () {
		          jQuery(this).jPlayer("setMedia", {
		            title: "<?php echo $title;?>",
		            mp3: "<?php echo $result['audioUrl']; ?>"
		          });
		        },
		        cssSelectorAncestor: "#jp_container_1",
		        swfPath: "http://s.bellevuecollege.edu/kbcs/themes/kbcs/js/",
		        supplied: "mp3",
		        useStateClassSkin: true,
		        autoBlur: false,
		        smoothPlayBar: true,
		        keyEnabled: true,
		        remainingDuration: true,
		        toggleDuration: true
		      });
		});
		
		/*jQuery('#jplayer_1').jPlayer({
			ready: function (event) {
				jQuery(this).jPlayer("setMedia", {
						mp3: "<?php echo $result['audioUrl']; ?>"
				});
				},
				swfPath: "http://s.bellevuecollege.edu/kbcs/themes/kbcs/js/",
				supplied: "mp3",
				wmode: "window",
				cssSelectorAncestor: "#jp_container_1"
		});*/	
 		/*$(document).ready(function(){
  			$("#jquery_jplayer_1").jPlayer({
   			ready: function () {
    			$(this).jPlayer("setMedia", {
     				m4a: "/media/mysound.mp4",
     				oga: "/media/mysound.ogg"
    			});
   			},
   			swfPath: "/js",
   			supplied: "m4a, oga"
  			});
 		});*/
	</script>
</head>

<body>

	<div class="container wrapper"><!-- outer container -->
		<div class="container content"><!-- content container -->
			<div class="row">
				<div class="span12">
					<h2><?php echo $title; ?></h2>
					
					<!--<div id="jquery_jplayer_1" class="jp-jplayer"></div>
						<div id="jp_container_1" class="jp-audio" role="application" aria-label="media player">
						  <div class="jp-type-single">
						    <div class="jp-gui jp-interface">
						      <div class="jp-volume-controls">
						        <button class="jp-mute" role="button" tabindex="0">mute</button>
						        <button class="jp-volume-max" role="button" tabindex="0">max volume</button>
						        <div class="jp-volume-bar">
						          <div class="jp-volume-bar-value"></div>
						        </div>
						      </div>
						      <div class="jp-controls-holder">
						        <div class="jp-controls">
						          <button class="jp-play" role="button" tabindex="0">play</button>
						          <button class="jp-stop" role="button" tabindex="0">stop</button>
						        </div>
						        <div class="jp-progress">
						          <div class="jp-seek-bar">
						            <div class="jp-play-bar"></div>
						          </div>
						        </div>
						        <div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>
						        <div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>
						        <div class="jp-toggles">
						          <button class="jp-repeat" role="button" tabindex="0">repeat</button>
						        </div>
						      </div>
						    </div>
						</div>
					</div>-->
					
					
					<div id="jplayer-html">
        				<div class="jplayer-block">
							<div id="jquery_jplayer_1" class="jp-jplayer">
								<audio id="jp_audio" preload="metadata" src='<?php echo $result["audioUrl"]?>'></audio>
							</div>
							<div id="jp_container_1" class="jp-audio" role="application" aria-label="media player">
							    <div class="jp-type-single">
							        <div class="jp-gui jp-interface">
							            <ul class="jp-controls">
							                <li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
							                <li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
							                <li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
							                <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
							                <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
							                <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
							            </ul>
							            <div class="jp-progress">
							                <div class="jp-seek-bar">
							                    <div class="jp-play-bar"></div>
							                </div>
							            </div>
							            <div class="jp-volume-bar">
							                <div class="jp-volume-bar-value"></div>
							            </div>
							            <div class="jp-time-holder">
							                <div class="jp-current-time" role="timer" aria-label="time"></div>
							                <div class="jp-duration" role="timer" aria-label="duration"></div>
							
							                <ul class="jp-toggles">
							                    <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat" role="button">repeat</a></li>
							                    <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
							                </ul>
							            </div>
							        </div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div><!-- end row -->
		</div><!-- end content container -->
	</div><!-- end outer container -->

</body>
</html>
<?php
	}
}
