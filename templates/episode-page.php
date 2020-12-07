<?php
	
global $wp_query;

if ( isset($wp_query->query_vars['episode_page_var']) && is_numeric($wp_query->query_vars['episode_page_var']) ) {
	//get the episode ID
	$ep_id = (int)$wp_query->query_vars['episode_page_var'];

	//call the JSON API	to get episode information
	$api_url = "http://kbcsweb.bellevuecollege.edu/play/api/shows/%d";

  	$content = file_get_contents(sprintf($api_url, $ep_id));
  	$json = json_decode($content, true);

	if ( $json ){
		$result = $json[0];
		
		//be smart about formatting the start time for the show
      	$start_date = date_create($result['start']);
      	$start_min = date_format($start_date, "i");
      	$start_time = ($start_min == "00") ? date_format($start_date, "ga") : date_format($start_date, "g:ia");
		$start_hour = date_format($start_date, "H:i");
		$start_day = date_format($start_date, "l");
      
      	//set show title
		$title_format = '%s - '.date_format($start_date, "n/j/y").', '.$start_time;
		$title = sprintf($title_format, $result['title']);
		
		//set program information
		$program_id = $result['programId'];
		$program_img_uri = "";
		
		//define arguments for custom query
		$meta_array = array();
		$meta_array[] = array( 'key' => 'programid_mb', 'value' => $program_id);	//query by program id
		$meta_array[] = array( 'key' => 'onair_starttime', 'value' => $start_hour ); // and program start time
		$meta_array[] = array( 'key' => 'onair_'.strtolower($start_day), 'value' => 'on'); // and program air day to distinguish rebroadcasts

		$args = array(
			'post_type' => 'programs',
			'post_status' => 'publish',
			'meta_query' => $meta_array
		);
			
		//do custom query to get associated program information
		$cust_query = new WP_Query($args);
		if ( $cust_query->have_posts() ) {
  			$cust_query->the_post();
			$wp_post_id = get_the_ID();
			$image_id = get_post_thumbnail_id($wp_post_id);
          	$program_img_uri = wp_get_attachment_image_src($image_id, array(9999,90));
			$title = sprintf($title_format, get_the_title($wp_post_id)); //use WP program title if available
		}
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<title><?php echo $title?> - 91.3 KBCS</title>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/css/font-awesome.css">
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
	<style>
		.playlist-item { background-color: #eee; }
		.comment-block { color: #999; }
		.media { margin-bottom: .8em; }
		.img-thumbnail { height: 90px; }
	</style>
</head>

<body>

	<div class="container"><!-- outer container -->
		<div class="container-fluid content"><!-- content container -->
			<div class="row"><!-- content row -->
				<div></div>
				<div class="media">
					<!-- Display title and image -->
					<div class="media-body">
						<h3 class="media-heading"><?php echo $title; ?></h2>
						<p><?php echo $result['host']; ?></p>
					</div>
					<?php if ( !empty($program_img_uri) ) { ?>
						<div class="media-right">
							<img class="media-object img-thumbnail" src="<?php echo $program_img_uri[0]; ?>" alt="" />
						</div>
					<?php } ?>
				</div>
					
				<?php if( $result['playlist'] ) {  ?>
					<!-- Display playlist information -->
					<ul class="list-group">
					<?php foreach ( $result['playlist'] as $item ) {?>
						<li class="list-group-item playlist-item">
							<div class="row">
								<div class="col-xs-3">
									<span class="hour"><?php echo date_format(date_create($item['played']), "g:i a"); ?></span>
								</div>
								<div class="col-xs-9">
									<h5 class="list-group-item-heading"><?php echo $item['artist']; ?></h5>
									<?php
										$item_info = ""; 
										if ( !empty($item['title'] ) ) {
											$item_info .= $item['title'];
										}
										if ( !empty($item['album']) ) {
											$item_info .= ", <i>".$item['album']."</i>";
										}
										if ( !empty($item_info) ) {
									?>
										<p class="list-group-item-text"><?php echo $item_info; ?></p>
									<?php } ?>
								</div>
							</div>
							<?php if ( isset($item['comment']) && !empty($item['comment']) ) { ?>
								<div class="row">
									<div class="col-xs-9 col-xs-offset-3 comment-block">
										<span class="glyphicon glyphicon-bullhorn" aria-hidden="true"></span>
										<strong>Host said:</strong> <?php echo $item['comment']; ?>
									</div>
								</div>
							<?php } ?>
						</li>
					<?php } ?>
					</ul>
				<?php } ?>
			</div><!-- end row -->
		</div><!-- end content container -->
	</div><!-- end outer container -->

</body>
</html>
<?php
	}
}

//reset original post data
wp_reset_postdata();