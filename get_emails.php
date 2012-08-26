<?php
    //ini_set('display_errors', 'On'); // Turning these on will make errors 'echo' back to AJAX causing bad formatted json
    //error_reporting(E_ALL | E_STRICT); // Turning these on will make errors 'echo' back to AJAX causing bad formatted json

    //PHP SCRIPT: imageFetch.php - get file names of images from present folder
    Header("content-type: application/x-javascript");

    date_default_timezone_set('America/New_York');
    require_once('php/autoloader.php');
    //set_time_limit(300); // 5 minutes timeout time


    // Get hashtag variable passed from browser
    $hashtag=$_SERVER['argv'][1]; //$_GET['hashtag'];
    $email=$_SERVER['argv'][2]; //$_GET['email'];
    $pass=$_SERVER['argv'][3]; //$_GET['password'];

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

    
    // Check if script is already running
    class pid {
        
        protected $filename;
        public $already_running = false;
        
        function __construct($directory) {
            
            $this->filename = $directory . '/' . basename($_SERVER['PHP_SELF']) . '.pid';
            
            if(is_writable($this->filename) || is_writable($directory)) {
                
                if(file_exists($this->filename)) {
                    $pid = (int)trim(file_get_contents($this->filename));
                    if(posix_kill($pid, 0)) {
                        $this->already_running = true;
                    }
                }
                
            }
            else {
                die("Cannot write to pid file '$this->filename'. Program execution halted.\n");
            }
            
            if(!$this->already_running) {
                $pid = getmypid();
                file_put_contents($this->filename, $pid);
            }
            
        }
        
        public function __destruct() {
            
            if(!$this->already_running && file_exists($this->filename) && is_writeable($this->filename)) {
                unlink($this->filename);
            }
            
        }
        
    }

    GLOBAL $finalurl;
     

    function image_sort_by_time( $a, $b ) {
        return intval($a->epochtime) == intval($b->epochtime) ? 0 : ( intval($a->epochtime) < intval($b->epochtime) ) ? 1 : -1;
    }


    function clean_filename($filename) {

        $bad = array_merge(
                array_map('chr', range(0,31)),
                array("<", ">", ":", '"', "/", "\\", "|", "?", "*"));
        $result = str_replace($bad, "", $filename);
        
        return $result;
    }



    function get_emails() {
        GLOBAL $email;
        GLOBAL $pass;
        GLOBAL $hashtag;
        
        //error_log("email: ".$email.", $pass: ".$pass);
        if (($email == null) || ($pass == null) || ($email == 'undefined') || ($pass == 'undefined')) {
            // No email address or password
            error_log("email or password was undefined - not getting emails");
            return;
        }

        /* connect to gmail */
        $hostname = '{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX';
        $username = $email; //'ndwedpics@gmail.com';
        $password = $pass; //'wedding1234';

        /* try to connect */
        //$connection = imap_open($hostname,$username,$password) or die('Cannot connect to Gmail: ' . imap_last_error());
        //error_log("  imap_open");
        if (!$connection = imap_open($hostname,$username,$password)) {
            // Error
            error_log("imap_open(".$hostname.",".$username.",".$password.") failed, with error: ".imap_last_error().", error: ".print_r(imap_errors()));
            return;
        }


        function decode_utf8($str) {
            preg_match_all("/=\?UTF-8\?B\?([^\?]+)\?=/i",$str, $arr);
            
            for ($i=0;$i<count($arr[1]);$i++){
                $str=ereg_replace(ereg_replace("\?","\?",
                                               $arr[0][$i]),base64_decode($arr[1][$i]),$str);
            }
            return $str;
        }

        // Check e-mails 60 times (once every 30 seconds for 30 minutes) with this imap connection
        $loop=1;
        while ($loop <= 60) { 
            error_log("get_emails() - Checking emails, loop=$loop.");

            /* Check the mailbox -- finds new emails */ 
            $check_result = imap_check($connection);
            
            /* count emails */
            $count = imap_num_msg($connection);

            //error_log("  message loop");
            for($message_number = 1; $message_number <= $count; $message_number++) {
                error_log("  Message $message_number ... call imap_headerinfo");
                $header = imap_headerinfo($connection, $message_number);
                
                // Look at unread emails only
                //if($header->Unseen == 'U') {
                    //echo "Message ".$message_number." is unread<br>";

                    //$raw_body = imap_body($connection, $message_number);
                    $structure = imap_fetchstructure($connection, $message_number);
                    
                    // Get sender
                    if (isset($header->from[0]->personal)) { 
                        $sender = $header->from[0]->personal; 
                    } else { 
                        $sender = $header->from[0]->mailbox; 
                    } 
                    //echo "<br>".$sender."<br>";

                    // Get subject
                    $subject = decode_utf8($header->subject);
                    if ($subject == null) {
                        $subject = "";
                    }
                    //echo $subject."<br>";
                    
                    // Get date time
                    $date = date('Y-m-d H:i:s', $header->udate); 
                    //$date = $header->udate; 
                    $epoch = strtotime($date); // Convert date to epoch
                    $timeadjusted=intval($epoch);// - 4*60*60; // Somehow corrects it to EST... I think Instagrams date times are messed up
                    $date=date("Y-m-d H:i:s",$timeadjusted); // Build human-readable date in EST
                    //echo $date."<br>";

                    //error_log("   sender=$sender, subject=$subject, date=$date");
                    
                    // Search for attachments
                    $attachments = array();
                    if(isset($structure->parts) && count($structure->parts)) {
                        
                        for($i = 0; $i < count($structure->parts); $i++) {

                            $attachments[$i] = array(
                                'is_attachment' => false,
                                'filename' => '',
                                'name' => '',
                                'attachment' => ''
                            );
                            
                            if($structure->parts[$i]->ifdparameters) {
                                foreach($structure->parts[$i]->dparameters as $object) {
                                    if(strtolower($object->attribute) == 'filename') {
                                        $attachments[$i]['is_attachment'] = true;
                                        $attachments[$i]['filename'] = $object->value;
                                    }
                                }
                            }
                            
                            if($structure->parts[$i]->ifparameters) {
                                foreach($structure->parts[$i]->parameters as $object) {
                                    if(strtolower($object->attribute) == 'name') {
                                        $attachments[$i]['is_attachment'] = true;
                                        $attachments[$i]['name'] = $object->value;
                                    }
                                }
                            }
                            
                            if($attachments[$i]['is_attachment']) {

                                // Get attachment name
                                $filename=$attachments[$i]['filename'];
                                //echo $filename."<br>";
                                //error_log("    filename=$filename");
                                
                                // Get extension
                                $extension = substr(strrchr($filename, '.'), 1);
                                //echo $extension."<br>";
                                
                                // Check is extension is JPG JPEG PNG GIF
                                if(
                                   (!(stristr($extension, 'png') === FALSE)) ||
                                   (!(stristr($extension, 'jpg') === FALSE)) ||
                                   (!(stristr($extension, 'jpeg') === FALSE)) ||
                                   (!(stristr($extension, 'gif') === FALSE))
                                   )
                                {
                                    //echo "File was an image"."<br>";
                                    
                                    // Build the local file name we will save to
                                    $local_filename = $sender."_".$subject."_".$date."_".$i; // Form name
                                    $local_filename = clean_filename($local_filename); // Remove bad characters
                                    $local_filename = "Images/".$hashtag."/".$local_filename; // Add path
                                    $local_filename = str_replace(' ', '_', $local_filename);
                                    
                                    $img_local=$local_filename.".".$extension;
                                    //echo $img_local."<br>";
                                    //error_log("    img_local=$img_local");
                                    
                                    // Save the image file if it doesnt exist
                                    if (!file_exists($img_local)) { 
                                        //echo 'file needs to be written'."<br>";
                                        //echo imap_base64($attachments[$i]['attachment']);

                                        error_log("      $img_local DOES NOT exist yet - get image start");
                                        $attachments[$i]['attachment'] = imap_fetchbody($connection, $message_number, $i+1);
                                        
                                        if($structure->parts[$i]->encoding == 3) { // 3 = BASE64
                                            $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                                        }
                                        elseif($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                                            $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                                        }

                                        error_log("      get image done, now writing");
                                        
                                        $fp=fopen($img_local,"w");
                                        fputs($fp, $attachments[$i]['attachment']);
                                        fclose($fp);

                                        error_log("      done writing");
                                    }
                                    else {
                                        //echo 'Image file does not need to be written'."<br>";
                                        //error_log("      img_local exists");
                                    }
                                    
                                    // Save the Metadata if it doesn't exist
                                    $text=$sender . " - " . $subject . " (" . $date . ")"; 
                                    
                                    $mf = new Image($img_local);
                                    $mf->settext($text);
                                    $mf->setdate($date);
                                    $mf->setepochtime(strval($timeadjusted));
                                    
                                    $meta_data_local=$local_filename.".txt"; 
                                    //echo "meta_data_local: ".$meta_data_local."<br><br>";
                                    if (!file_exists($meta_data_local)) {
                                        // Write meta data file
                                        $fp = fopen($meta_data_local, 'w'); // Open meta-data file for writing
                                        fwrite($fp, json_encode($mf)); // Write json encoded meta data
                                        fclose($fp);
                                    }
                                    else {
                                        //echo 'Metadata file does not need to be written'."<br>";
                                    }
                                }
                            }
                        }
                    }
                //}
                //else {
                    //echo "Message ".$message_number." was read<br>";
                //}
            }

            $loop++;
            sleep(30); // Sleep 30 seconds before checking messages again
        }
        
        /* close the connection */
        error_log("get_emails() - Closing connection, done for now.");
        imap_close($connection);
    }


    $pid = new pid('/Users/nathannantais/Sites/feedSlider');
    if($pid->already_running) {
        //echo "Already running.\n";
        error_log("get_emails() - Already running.");
        exit;
    }
    else {
        //echo "Running...\n";
        error_log("get_emails() - Running now");
        error_log("Get images for #".$hashtag.", email ".$email.", password ".$pass);
        sleep(8); // Sleep so e-mail checking will be offset, to be done after local file searching is already done
        get_emails();
    }
    

?>