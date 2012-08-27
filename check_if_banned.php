<?php
    //ini_set('display_errors', 'On'); // Turning these on will make errors 'echo' back to AJAX causing bad formatted json
    //error_reporting(E_ALL | E_STRICT); // Turning these on will make errors 'echo' back to AJAX causing bad formatted json

    //PHP SCRIPT: imageFetch.php - get file names of images from present folder
    Header("content-type: application/x-javascript");

    //set_time_limit(300); // 5 minutes timeout time

    error_log("check_if_banned() - return list of banned files");

    
    
    class Image
    {
        // This is the CLASS DEFINITION (everything in the curly brackets).
        
        // $myVar is DECLARED, but it is not INITIALIZED (assigned a value).
        public $filename;
        
        public function __construct($value = 'What?') {
            $this->setfilename($value); // $filename will now be INITIALIZED
        }
        
        public function setfilename($value) {
            if(!is_string($value)) {
                $value = (string)'Non-String type passed in argument';
            }
            
            $this->filename = $value;
        }        
    }

    
    
    
    $myFile = "banned_images.txt";

    $file = new SplFileObject($myFile);

    $banned_files = array();
        
    $match = false;
    $i = 0;
    foreach($file as $line) {
        if (strlen($line) > 0) {
            $mf = new Image($line);
            $banned_files[] = $mf;
            $i++;
        }
    }
        
    error_log("check_if_banned() - $i banned files");
    echo json_encode($banned_files);

?>