<?php
/** 
* This template outputs an aggregate feed of segment episodes based on a provided program type.
* Example URL: http://kbcs.local/program_type/music/episodes?itemcount=15
**/
//header('Content-Type: text/xml; charset=utf-8', true); //set document header content type to be XML

$program_url = 'http://kbcsweb.bellevuecollege.edu/play/api/shows/?programID=%d&pageSize=%d'; //Playlist Center API URL
$audio_url = 'http://kbcsweb.bellevuecollege.edu/playlist/audioarchive/%s-01.mp3'; //template for archive audio filename

//loop through all programs of this program type, get episodes from Playlist Center, and add to episode array
$episode_array = array();

while ( $wp_query->have_posts() ) {
  $wp_query->the_post();
  $program_id = get_post_meta(get_the_ID(), 'programid_mb', true);
  
  $content = file_get_contents(sprintf($program_url,$program_id,$num));
  $json = json_decode($content, true);
  
  if( $json ){
    foreach( $json as $result ) {
      $episode_array[$result['start']] = $result; //add to episode array with 'start' as key so it can be sorted on later
    }
  }
}

//Do key-ed sort (since start is used as key) by start date reverse
krsort($episode_array);

//cut array to specified size
$episode_slice = array_slice($episode_array, 0, $num);

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
 
//a feed should contain an atom:link element (info http://j.mp/1nuzqeC)
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

if($episode_slice) { //we have program info
	foreach ( $episode_slice as $result ) {	  

	    $title = $result['title'].' '.date_format(date_create($result['start']), "m/d/y");
	  
      $item_node = $channel_node->appendChild($xml->createElement("item")); //create a new node called "item"
      $title_node = $item_node->appendChild($xml->createElement("title", htmlentities($title))); //Add title under "item"
      //$link_node = $item_node->appendChild($xml->createElement("link", "http://kbcs.fm/path/to/episode/")); //add link node under "item"
      $creator_node = $item_node->appendChild($xml->createElement("dc:creator"));
	    $creator_contents = $xml->createCDATASection(htmlentities($result['host']));  
      $creator_node->appendChild($creator_contents);
	  
      //Unique identifier for the item (GUID)
      $guid_link = $xml->createElement("guid", get_the_guid() . "/" . $result['showId']);  
      $guid_link->setAttribute("isPermaLink","false");
      $guid_node = $item_node->appendChild($guid_link); 
     
      //create "description" node under "item"
      //$description_node = $item_node->appendChild($xml->createElement("description"));  
      
      //fill description node with CDATA content
      //$description_contents = $xml->createCDATASection(htmlentities("Change this text to something from Wordpress?"));  
      //$description_node->appendChild($description_contents);
	  
  	  //audio URI
  	  $enclosure = sprintf($audio_url, date_format(date_create($result['start']), 'YmdHi'));
  	  $enc_node = $xml->createElement("enclosure");
  	  $enc_node->setAttribute("type", "audio/mpeg");
      $enc_node->setAttribute("url", $enclosure);
  	  $item_node->appendChild($enc_node);
	  
      //Published date
      $date_rfc = gmdate(DATE_RFC2822, strtotime($result['start']));
      $pub_date = $xml->createElement("pubDate", $date_rfc);  
      $pub_date_node = $item_node->appendChild($pub_date); 

    }
}
echo $xml->saveXML();