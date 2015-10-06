<?php
header('Content-Type: text/xml; charset=utf-8', true); //set document header content type to be XML

$program_url = 'http://kbcsweb.bellevuecollege.edu/play/api/shows/?programId=%d&pageSize=%d';
$audio_url = 'http://kbcsweb.bellevuecollege.edu/playlist/audioarchive/%s-01.mp3';

//var_dump($wp_query);
while ( $wp_query->have_posts() ) {
  $wp_query->the_post();
  $program_id = get_post_meta(get_the_ID(), 'programid_mb', true);
  

  /**
  /////////// 
  NOTE ON COMMENTING OUT: Changes were made to ensure that program-specific feeds wouldn't include rebroadcast shows. 
  Since rebroadcast shows share program IDs, I did some show air time query gymnastics to filter non-original shows. But then 
  it got really fuzzy because not all program end points resolve anyway since they forward to the page of the original program. In any case, 
  Bruce and I eventually determined that all programs, even if rebroadcast, should have their own program IDs. Thus all of 
  this isn't needed anyway. Leaving for the time being, but will likely disappear into the ether...
  ///////// 
  //Get the count of WP programs that use the given program ID
  //If more than 1, we need to get more JSON results
  //Why the gymnastics? Because if it's a rebroadcast program, we have to skip some results, so 
  //we must get a number that ensures we have 'enough' of the correct broadcast time.
  global $wpdb;
  $prog_count = $wpdb->get_var(
                  $wpdb->prepare("SELECT count(DISTINCT pm.post_id)
                    FROM $wpdb->postmeta pm
                    JOIN $wpdb->posts p ON (p.ID = pm.post_id)
                    WHERE 
                      p.post_type = 'programs' 
                      AND pm.meta_key = %s
                      AND pm.meta_value = %s
                      AND p.post_status = 'publish'", "programid_mb", $program_id));
  $pull_count = $num;
  if ( is_numeric($prog_count) )
    $pull_count *= intval($prog_count);
  **/
    
  //gather start time and air date info from WP
  /** commenting out as may not use 
  $prog_air_time = get_post_meta(get_the_ID(), 'onair_starttime', true);
  $prog_air_days = array();
  $weekdays = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
  foreach ( $weekdays as $day ) {
    //check the onair_weekday meta value - if it's on, add to our array of air days
    $is_aired = get_post_meta(get_the_ID(), 'onair_'.strtolower($day), true);
    if ( isset($is_aired) && $is_aired == "on" ) $prog_air_days[] = $day;
  }
  $content = file_get_contents(sprintf($program_url,$program_id,$pull_count));
  **/
  
  //call the JSON API 
  $content = file_get_contents(sprintf($program_url,$program_id,$num));
  $json = json_decode($content, true);

  $xml = new DOMDocument("1.0", "UTF-8"); // Create new DOM document.
  
  //create "RSS" element
  $rss = $xml->createElement("rss"); 
  $rss_node = $xml->appendChild($rss); //add RSS element to XML node
  $rss_node->setAttribute("version","2.0"); //set RSS version
  
  //set attributes
  $rss_node->setAttribute("xmlns:dc","http://purl.org/dc/elements/1.1/"); //xmlns:dc (info http://j.mp/1mHIl8e )
  $rss_node->setAttribute("xmlns:content","http://purl.org/rss/1.0/modules/content/"); //xmlns:content (info http://j.mp/1og3n2W)
  $rss_node->setAttribute("xmlns:atom","http://www.w3.org/2005/Atom");//xmlns:atom (http://j.mp/1tErCYX )
  
  //Create RFC822 Date format to comply with RFC822
  $date_f = date("D, d M Y H:i:s T", time());
  $build_date = gmdate(DATE_RFC2822, strtotime($date_f));
  
  //create "channel" element under "RSS" element
  $channel = $xml->createElement("channel");  
  $channel_node = $rss_node->appendChild($channel);
   
  //a feed should contain an atom:link element
  $host = @parse_url(home_url());
  $self_link = esc_url( set_url_scheme( 'http://' . $host['host'] . wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) ;
  $channel_atom_link = $xml->createElement("atom:link");  
  $channel_atom_link->setAttribute("href", $self_link); //url of the feed
  $channel_atom_link->setAttribute("rel","self");
  $channel_atom_link->setAttribute("type","application/rss+xml");
  $channel_node->appendChild($channel_atom_link); 
  
  //add general elements under "channel" node
  $channel_node->appendChild($xml->createElement("title", get_bloginfo_rss('name') . get_wp_title_rss())); //title
  //$channel_node->appendChild($xml->createElement("description", bloginfo_rss('description') ));  //description
  $channel_node->appendChild($xml->createElement("link", get_bloginfo_rss('url') )); //website link 
  $channel_node->appendChild($xml->createElement("language", "en-us"));  //language
  $channel_node->appendChild($xml->createElement("lastBuildDate", $build_date));  //last build date
  $channel_node->appendChild($xml->createElement("generator", 'KBCS Custom Feeds Plugin')); //generator
  
  if($json){ //we have json data
    usort($json, 'reverse_sort_by_start');
  	foreach ( $json as $result ) 
      {	  

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
        
        //be smart about formatting the start time for the show
        $start_date = date_create($result['start']);
        $start_min = date_format($start_date, "i");
        $start_time = ($start_min == "00") ? date_format($start_date, "ga") : date_format($start_date, "g:ia");
        
        //check show air time and air days - skip if don't match that of the given program
        /** commenting out - as don't currently need to worry about rebroadcast shows
        $show_air_time = date_format($start_date, "H:i");
        $show_air_day = date_format($start_date, "l");
        //var_dump($show_air_time);
        if ( !($prog_air_time == $show_air_time && in_array($show_air_day, $prog_air_days)) ) {
          continue;
        }
        **/
        
        //set show title
        $title_format = '%s '.date_format($start_date, "n/j/y").', '.$start_time;
        $title = sprintf($title_format, $result['title']);
      
        if ( isset($result['wp_title']) ) {
          $title = sprintf($title_format, $result['wp_title']); //if WP program title exists, use that instead
        }
        
        $item_node = $channel_node->appendChild($xml->createElement("item")); //create a new node called "item"
        $title_node = $item_node->appendChild($xml->createElement("title"));
        $title_node->appendChild($xml->createTextNode($title)); //use createTextNode so title is properly encoded by default
        
        $episode_link = get_bloginfo_rss('url') . "/" . $this->episode_page_slug . "/" . $result['showId'];
        $link_node = $item_node->appendChild($xml->createElement("link", $episode_link)); //add link node under "item"
        
        $creator_node = $item_node->appendChild($xml->createElement("dc:creator"));
  	    $creator_contents = $xml->createCDATASection(htmlentities($result['host']));  
        $creator_node->appendChild($creator_contents);
  	  
        //Unique identifier for the item (GUID)
        $guid_link = $xml->createElement("guid", $episode_link); //use new episode specific page
        $guid_link->setAttribute("isPermaLink","true");
        $guid_node = $item_node->appendChild($guid_link); 
       
         //create "description" node under "item" to use for feature image
        if ( has_post_thumbnail() ) {
          
          $image_id = get_post_thumbnail_id(get_the_ID());
          $image_uri = wp_get_attachment_image_src($image_id, "full");

          if ( !empty($image_uri[0]) ){
            $img_uri = $image_uri[0];
            if ( substr($img_uri, 0, 2) == "//" ) {
              //is protocol-relative URI, change to protocol-specific URI per app developer request
              $img_uri = "http:" . $img_uri;
            }
            $description_node = $item_node->appendChild($xml->createElement("description"));  
            $description_contents = $xml->createCDATASection('<img src="'.$img_uri.'" />');  
            $description_node->appendChild($description_contents);
          }
        }

    	  //Audio URI
    	  $enclosure = sprintf($audio_url, date_format(date_create($result['start']), 'YmdHi'));
    	  $enc_node = $xml->createElement("enclosure");
    	  $enc_node->setAttribute("type", "audio/mpeg");
        //$clength = (!empty($result['content_length']) ) ? $result['content_length'] : $this->kcf_get_remote_filesize($enclosure);
        $enc_node->setAttribute("length", 0);
        $enc_node->setAttribute("url", $enclosure);
    	  $item_node->appendChild($enc_node);
  	  
        //Published date
        $date_rfc = gmdate(DATE_RFC2822, strtotime($result['start']));
        $pub_date = $xml->createElement("pubDate", $date_rfc);  
        $pub_date_node = $item_node->appendChild($pub_date); 

      }
  }
  echo $xml->saveXML();
}

function reverse_sort_by_start($a, $b) 
{
  return (strtotime($a['start']) > strtotime($b['start']) ? -1 : 1);
}