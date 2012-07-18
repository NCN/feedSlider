<?php
//ini_set('display_errors', 'On'); // Turning these on will make errors 'echo' back to AJAX causing bad formatted json
//error_reporting(E_ALL | E_STRICT); // Turning these on will make errors 'echo' back to AJAX causing bad formatted json

//PHP SCRIPT: imageFetch.php - get file names of images from present folder
Header("content-type: application/x-javascript");

date_default_timezone_set('America/New_York');
require_once('php/autoloader.php');
include('simplehtmldom_1_5/simple_html_dom.php');
set_time_limit(300); // 5 minutes timeout time

#$hashtag="ndwedding";

// Get hashtag variable passed from browser
$hashtag=$_GET['hashtag'];
error_log("Get images for #".$hashtag);

$origurl = "";

// Create Images/hashtag folder to save all images
if (!file_exists("Images/{$hashtag}")) {
    mkdir("Images/{$hashtag}");
}

// ********************************************************************************************************
// Class for Image object containing filename, description text, etc. Used for loading images into Object array which is
// then json-encoded and passed back to the AJAX
// ********************************************************************************************************
class Image
{
  // This is the CLASS DEFINITION (everything in the curly brackets).

  // $myVar is DECLARED, but it is not INITIALIZED (assigned a value).
  public $filename;
  public $text;
  public $date;
  public $epochtime;

  public function __construct($value = 'What?') {
    $this->setfilename($value); // $filename will now be INITIALIZED
  }

  public function setfilename($value) {
    if(!is_string($value)) {
      $value = (string)'Non-String type passed in argument';
    }

    $this->filename = $value;
  }

  public function settext($value) {
    if(!is_string($value)) {
      $value = (string)'Non-String type passed in argument';
    }

    $this->text = $value;
  }

  public function setdate($value) {
    if(!is_string($value)) {
      $value = (string)'Non-String type passed in argument';
    }

    $this->date = $value;
  }

  public function setepochtime($value) {
    if(!is_string($value)) {
      $value = (string)'Non-String type passed in argument';
    }

    $this->epochtime = $value;
  }
}

GLOBAL $finalurl;
 
class MakeItLink {
    protected static function _link_www( $matches ) {
        GLOBAL $finalurl;
        $url = $matches[2];
        $urlnew = MakeItLink::cleanURL($url);
        if( empty( $urlnew ) ) {
            return $matches[0];
        }

        $finalurl=$urlnew; // Couldn't figure out how to get this URL passed back to my calling area... just set the global here. This is what we really want.
        return "{$matches[1]}<a href='{$urlnew}'>{$urlnew}</a>";
    }

    public static function cleanURL( $url ) {
        if( $url == '' ) {
            return $url;
        }

        $url = preg_replace( "|[^a-z0-9-~+_.?#=!&;,/:%@$*'()x80-xff]|i", '', $url );
        $url = str_replace( array( "%0d", "%0a" ), '', $url );
        $url = str_replace( ";//", "://", $url );

        /* If the URL doesn't appear to contain a scheme, we
         * presume it needs http:// appended (unless a relative
         * link starting with / or a php file).
         */
        if(
            strpos( $url, ":" ) === false
            && substr( $url, 0, 1 ) != "/"
            && !preg_match( "|^[a-z0-9-]+?.php|i", $url )
        ) {
            $url = "http://{$url}";
        }

        // Replace ampersans and single quotes
        $url = preg_replace( "|&([^#])(?![a-z]{2,8};)|", "&#038;$1", $url );
        $url = str_replace( "'", "&#039;", $url );

        return $url;
    }

    public static function transform( $text ) {
        $text = " {$text}";

        $text = preg_replace_callback(
            '#(?<=[\s>])(\()?([\w]+?://(?:[\w\\x80-\\xff\#$%&~/\-=?@\[\](+]|[.,;:](?![\s<])|(?(1)\)(?![\s<])|\)))*)#is',
            array( 'MakeItLink', '_link_www' ),
            $text
        );
        //echo "text: ".$text."<br>";

        $text = preg_replace( '#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i', "$1$3</a>", $text );
        $text = trim( $text );
        //echo "text: ".$text."<br>";

        return $text;
    }
}


function image_sort_by_time( $a, $b ) {
    return intval($a->epochtime) == intval($b->epochtime) ? 0 : ( intval($a->epochtime) < intval($b->epochtime) ) ? 1 : -1;
}

if ( !function_exists( 'esc_html' ) ) {
    function esc_html( $html, $char_set = 'UTF-8' ) {
        if ( empty( $html ) ) {
            return '';
        }
 
        $html = (string) $html;
        $html = htmlspecialchars( $html, ENT_QUOTES, $char_set );
 
        return $html;
    }
}

function parse_html($htmlurl) {
    //echo "url: ".$htmlurl."<br><br>";

    GLOBAL $origurl;

    // Read the URL page
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $htmlurl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    $result = curl_exec($ch);
    $info = curl_getinfo($ch); //Some information on the fetch
    curl_close($ch);
    $found_image=0;

    if (!empty($result)) { // If the curl returned a web page
        //echo "Not empty<br>";
        $html = str_get_html($result);
        //var_dump($html);
        
        
        // Look for Twitter (twitter.com) images: find all div tags with class tweet-media
        if(!(stristr($origurl, 'twitter') === FALSE)) {
//            echo "Twitter<br>";
            foreach($html->find('div.twimg') as $e) {
                //echo $e->innertext . '<br>';
                foreach($e->find('img') as $f) {
                    $image_url=$f->src; // Twitter image
                    //echo "Image: ".$image_url."<br>";
                    $found_image=1;
                }
            }
        }

        // Look for Instagram images: find all div tags with class media-photo
        if(!(stristr($origurl, 'instagram') === FALSE)) {
//            echo "Instagram<br>";
            foreach($html->find('div.media-photo') as $e) {
                //var_dump($e);
                //echo $e->innertext . '<br>';
                foreach($e->find('img') as $f) {
                    $image_url=$f->src; // IG image
                    //echo "Image: ".$image_url."<br>";
                    $found_image=1;
                }
            }
        }
        
        // Look for TwitPic photos: find all div tags with id media
        if(!(stristr($origurl, 'twitpic') === FALSE)) {
//            echo "Twitpic<br>";
            foreach($html->find('div#media-main') as $a) {
                foreach($a->find('div#media') as $e) {
                    //echo $e->innertext . '<br>';
                    foreach($e->find('img') as $f) {
                        $image_url=$f->src;
                        $found_image=1;
                    }
                    if ($found_image === 1) {
                        //echo "Image: ".$image_url."<br>";
                    }
                }
            }
        }

        // Look for YFrog photos: find all div tags with class the-image dont-hide
        if(!(stristr($origurl, 'yfrog') === FALSE)) {
//            echo "YFrog<br>";
            foreach($html->find('div.the-image') as $a) {
                //echo $a->innertext . '<br>';
                foreach($a->find('img') as $f) {
                    $image_url=$f->src;
                    $found_image=1;
                }
                if ($found_image === 1) {
                    //echo "Image: ".$image_url."<br>";
                }
            }
        }

        // Look for img.ly photos: find all div tags with id image-box
        if(!(stristr($origurl, 'img.ly') === FALSE)) {
        //    echo "YFrog<br>";
            foreach($html->find('div#image-box') as $a) {
                //echo $a->innertext . '<br>';
                foreach($a->find('img') as $f) {
                    $image_url=$f->src;
                    $found_image=1;
                }
                if ($found_image === 1) {
                    //echo "Image: ".$image_url."<br>";
                }
            }
        }
    }

    if ($found_image === 0) {
        return "";
    }
    else {
        return $image_url;
    }
}

//Get response location of a given URL
function expand_url($url){
    //Get response headers
    $response = get_headers($url, 1);
    //Get the location property of the response header. If failure, return original url
    if (array_key_exists('Location', $response)) {
        $location = $response["Location"];
        if (is_array($location)) {
            // t.co gives Location as an array
            return expand_url($location[count($location) - 1]);
        } else {
            return expand_url($location);
        }
    }
    return $url;
}


function get_tweets() {

    GLOBAL $hashtag;
    GLOBAL $finalurl;
    GLOBAL $origurl;
    // $rss contains XML string of RSS feed fetched from Twitter API.
    //$feed = file_get_contents('http://search.twitter.com/search.rss?q=%23'.$hashtag);
    //$feed = file_get_contents('http://search.twitter.com/search.rss?q='.$hashtag);
    $feed = file_get_contents('http://search.twitter.com/search.rss?&result_type=recent&rpp=50&q='.$hashtag); // rpp max 100
    if (!empty($feed)) {
        $rss = new SimpleXmlElement($feed);

        foreach($rss->channel->item as $entry) {
            // each item is contained in $item
            // for example: $item['author'] contains the author data
            // inside item's <author> tag.
            //var_dump($entry);
            
            $author = $entry->author; // newdles143@twitter.com (Alexis Jaworski)
            preg_match_all('/([A-Za-z0-9_]+@)/', $author, $usernames); // newdles143@
            $author='@'.str_replace("@","",$usernames[0][0]); // Put @ in front and remove @ from end
            
            $tweet_content = $entry->title;
            $date = $entry->pubDate;
            $epoch = strtotime($date); // Convert date to epoch
            $timeadjusted=intval($epoch);// - 4*60*60; // Somehow corrects it to EST... I think Instagrams date times are messed up
            $date=date("Y-m-d H:i:s",$timeadjusted); // Build human-readable date in EST
            
            //echo "author: ".$author."<br><br>";
            //echo "tweet_content: ".$tweet_content."<br><br>";
            //echo "date: ".$date."<br><br>";
            //echo "epoch: ".$epoch."<br><br>";

            $finalurl = "";
            $url = MakeItLink::transform( $tweet_content );
            
            if(!(stristr(strval($tweet_content), strval($hashtag)) === FALSE)) { // Check that our hashtag is really in the title

                // **************************************************
                // DOWNLOAD IMAGE FILE AND SAVE LOCALLY IF IT IS NEW
                // **************************************************
                //$url = 'http://example.com/image.php';
                
                $local_filename="Images/".$hashtag."/".$author."-".$epoch;
                
                $img_local_pic = "Images/".$hashtag."/".$author."-".$epoch.'.jpg'; // @ncn-1234567.jpg
                $meta_data_local_pic = "Images/".$hashtag."/".$author."-".$epoch.".txt"; // @ncn-1234567.txt

                $img_local_no_pic = "Images/".$hashtag."/no_pic_".$author."-".$epoch.'.jpg'; // @ncn-1234567.jpg
                $meta_data_local_no_pic = "Images/".$hashtag."/no_pic_".$author."-".$epoch.".txt"; // @ncn-1234567.txt

                if ((!file_exists($img_local_pic)) && (!file_exists($img_local_no_pic))) { // Only do any of this stuff if we havent already saved this Tweet

                    $image_to_download="";
                    if(!empty($finalurl))  // If there was a URL in the tweet
                    {
                        //echo "url from tweet: ".$finalurl."<br><br>";
                        //$url = "http://tiny.cc/cjymcw";

                        $origurl = expand_url($finalurl);
                        
                        if(
                        (!(stristr($origurl, 'twitter') === FALSE)) ||
                        (!(stristr($origurl, 'instagram') === FALSE)) ||
                        (!(stristr($origurl, 'twitpic') === FALSE)) ||
                        (!(stristr($origurl, 'yfrog') === FALSE)) ||
                        (!(stristr($origurl, 'img.ly') === FALSE))
                        ) 
                        {                        
                            // Only call if the original URL is from one of the sites we understand
                            $image_to_download=parse_html($finalurl); // parse url to find image
                        }
                    }
                    
                    if (empty($image_to_download)) {
                        //echo "Tweet had no picture <br>";
                        // Copy standard tweet image over it
                        copy('Images_perm/tweet_bg.jpg', $img_local_no_pic);
                    }
                    else {
                        //echo "Tweet had a picture <br>";
                        // Download image file
                        file_put_contents($img_local_pic, file_get_contents($image_to_download));
                    }
                }
                

                // *******************************************
                // WRITE JSON METADATA TO LOCAL FILE
                // *******************************************

                $text=$author . " - " . $tweet_content . " (" . $date . ")"; // Caption: @n_c_n - #detroit what! (2012-07-15 9:25)

                if (file_exists($img_local_pic)) {
                    $mf = new Image($img_local_pic);
                    $mf->settext($text);
                    $mf->setdate($date);
                    $mf->setepochtime(strval($timeadjusted));
                    
                    if (!file_exists($meta_data_local_pic)) {
                        // Write meta data file
                        $fp = fopen($meta_data_local_pic, 'w'); // Open meta-data file for writing
                        fwrite($fp, json_encode($mf)); // Write json encoded meta data
                        fclose($fp);
                    }
                }
                else {
                    $mf = new Image($img_local_no_pic);
                    $mf->settext($text);
                    $mf->setdate($date);
                    $mf->setepochtime(strval($timeadjusted));
                    
                    if (!file_exists($meta_data_local_no_pic)) {
                        // Write meta data file
                        $fp = fopen($meta_data_local_no_pic, 'w'); // Open meta-data file for writing
                        fwrite($fp, json_encode($mf)); // Write json encoded meta data
                        fclose($fp);
                    }
                }
            }
            else {
                //error_log($tweet_content." does not contain ".$hashtag);
            }
        }
    }
}


function get_instagram_photos() {
    /*************
     * Settings
     **************/
    GLOBAL $hashtag;
    
    //$files = array();
    //$fileobjects = array();

    $hashtagmod=(empty($_GET['hashtag']))?$hashtag:strtolower(trim($_GET['hashtag']));
    $feedurl=(get_magic_quotes_gpc())?stripslashes("http://instagr.am/tags/".$hashtagmod."/feed/recent.rss"):"http://instagr.am/tags/".$hashtagmod."/feed/recent.rss";
    $feed=new SimplePie();
    $feed->set_feed_url($feedurl);
    $feed->set_item_class();  
    $feed->enable_cache(true);  
    $feed->set_cache_duration(10);  
    $feed->set_cache_location('cache');  
    $feed->init();
    $feed->handle_content_type();

    if($feed->data) {
        $items=$feed->get_items();

        foreach($items as $item){
        
            // *******************************************
            // PARSE ITEM FROM RSS
            // *******************************************
            $permalink=$item->get_permalink();
            $title=$item->get_title();
            $nodoubletitle=str_replace("\"","'",$title);

            $dateformat="r";
            $date=$item->get_date($dateformat); // Will be in ?, example: Sun, 15 Jul 2012 13:35:52 -0400)

            $dateformat="U";
            $epoch=$item->get_date($dateformat); // gets epoch time of photo

            $timeadjusted=intval($epoch)-7*60*60; // Somehow corrects it to EST... I think Instagrams date times are messed up
            
            $date=date("Y-m-d H:i:s",$timeadjusted); // Build human-readable date in EST
                        
            //$estdate = new DateTime($date);

            $id=$item->get_id();
            $hashtags="";
            $thumbnail="";
            $photographer="";
            if($enclosure = $item->get_enclosure(0)){
                foreach ((array) $enclosure->get_keywords() as $keyword)
                {
                    $hashtags.="<a href=\"?hashtag=".$keyword."\" title=\"View recent photos on Instagram tagged with ".$keyword."\">#".$keyword."</a>, ";
                }
                $hashtags=substr($hashtags,0,-2);
                $thumbnail.=$enclosure->get_thumbnail();
                foreach ((array) $enclosure->get_credits() as $credit)
                {
                    $photographer=$credit->get_name();
                }
            }

            if(!(stristr(strval($title), strval($hashtag)) === FALSE)) { // Check that our hashtag is really in the title
                // **************************************************
                // DOWNLOAD IMAGE FILE AND SAVE LOCALLY IF IT IS NEW
                // **************************************************
                //$url = 'http://example.com/image.php';
                $img_local = 'Images/'.$hashtag.'/'.basename($id);
                if (!file_exists($img_local)) {
                    // Download image file
                    file_put_contents($img_local, file_get_contents($id));
                }
                
                // *******************************************
                // WRITE JSON METADATA TO LOCAL FILE
                // *******************************************

                $text="@".$photographer . " - " . $title . " (" . $date . ")"; // Caption: @n_c_n - #detroit what! (2012-07-15 9:25)

                $mf = new Image($img_local);
                $mf->settext($text);
                $mf->setdate($date);
                $mf->setepochtime(strval($timeadjusted));

                //$fileobjects[] = $mf; // Insert this object into the array of objects
                
                $info = pathinfo($id);
                $file_name_without_extension =  'Images/'.$hashtag.'/'.basename($id,'.'.$info['extension']);
                $meta_data_local=$file_name_without_extension.".txt";
                if (!file_exists($meta_data_local)) {
                    // Download image file
    //                file_put_contents($meta_data_local, $mf);
                    $fp = fopen($meta_data_local, 'w'); // Open meta-data file for writing
                    fwrite($fp, json_encode($mf)); // Write json encoded meta data
                    fclose($fp);
                }

                //echo $file_name; // outputs 'image'
            }
            else {
                //error_log($title." does not contain ".$hashtag);
            }
        }
        
        //usort($fileobjects, 'image_sort_by_time');

        return; //($fileobjects);
    }
    else {
        //$output.="  <h2>".$hashtag." returned zero results.</h2>"."\n";
    }
}




//This function gets the file names of all images in the current directory
//and ouputs them as a JavaScript array - NOT USED
function get_local_photos_without_metadata($dirname="Images") {
    $pattern="(.png|.jpg|.jpeg|.gif)"; //valid image extensions
    //$files = array();
    $fileobjects = array();
    
    if($handle = opendir($dirname)) 
    {
        while(false !== ($file = readdir($handle))){
            if(preg_match($pattern, $file))
            { //if this file is a valid image
                //Output it as a JavaScript array element
                $mf = new Image("Images/".$hashtag."/".$file); // Append folder name to image name
                $mf->settext('@n_c_n: This is the best wedding - totally dancing my butt of! Hurrah! Not looking forward to the hangover though');
                $mf->setdate('Jul 14, 2012 - 16:25');
                $fileobjects[] = $mf;
            }
        }

        closedir($handle);
    }
    
    return($fileobjects);
}


//This function gets the file names of all images in the current directory
//and ouputs them as a JavaScript array
function get_local_photos() {
    $pattern="(.png|.jpg|.jpeg|.gif)"; //valid image extensions
    //$files = array();
    $fileobjects = array();
    GLOBAL $hashtag;
    $dirname = 'Images/'.$hashtag;
    
    if($handle = opendir($dirname)) 
    {
        while(false !== ($file = readdir($handle))){
            if(preg_match($pattern, $file))
            { //if this file is a valid image
                //Output it as a JavaScript array element
                $img_local = $dirname.'/'.$file;
                $info = pathinfo($file);
                $file_name_without_extension =  $dirname.'/'.basename($file,'.'.$info['extension']);
                $meta_data_local=$file_name_without_extension.".txt";
                
                // Can only load the image if the metadata file exists
                if (file_exists($meta_data_local)) {
                    // Read in metadata file
                    $data = json_decode(file_get_contents($meta_data_local));
                    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                        error_log("incorrect data");
                    }
                    else {
                        $mf = new Image($img_local); // Image name
                        $mf->settext($data->text); // Text from read in metadata '@n_c_n: This is the best wedding - totally dancing my butt of! Hurrah! Not looking forward to the hangover though');
                        $mf->setdate($data->date);
                        $mf->setepochtime($data->epochtime);
                        $fileobjects[] = $mf;
                    }
                }
            }
        }

        closedir($handle);
    }
    
    usort($fileobjects, 'image_sort_by_time');

    return($fileobjects);
}


//echo 'var galleryarray=new Array();'; //Define array in JavaScript

//$images = get_local_photos_without_metadata(); //Output the array elements containing the image file names
get_instagram_photos();
get_tweets();
$images = get_local_photos(); //Output the array elements containing the image file names

//echo json_encode($images);
//echo "HELLLLLLLOOOOOO";

die(json_encode($images));

//    foreach($images as $img)
//    {
//      echo '<img src="images/' . $img . '" />';
//    }

?>