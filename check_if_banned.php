<?php
    //ini_set('display_errors', 'On'); // Turning these on will make errors 'echo' back to AJAX causing bad formatted json
    //error_reporting(E_ALL | E_STRICT); // Turning these on will make errors 'echo' back to AJAX causing bad formatted json

    //PHP SCRIPT: imageFetch.php - get file names of images from present folder
    Header("content-type: application/x-javascript");

    //set_time_limit(300); // 5 minutes timeout time

    if( $_REQUEST["filename"] )
    {
        $filename = $_REQUEST['filename'];
        error_log("check_if_banned() - filename: $filename");
        
        $myFile = "banned_images.txt";

        $stringData = "$filename\n";
        
        $file = new SplFileObject($myFile);
        //$file->setFlags(SplFileObject::DROP_NEW_LINES);
        
        $match = false;
        foreach($file as $line){
            if( false !== stripos( $line, $stringData ) ){
                $match = true;
                break;
            }
        }
        
        if( true === $match ){
            //we found a match
            error_log("check_if_banned() - $filename IS BANNED");
            echo json_encode("BANNED");
        } else {
            //No Match
            //error_log("check_if_banned() - $filename is not banned");
            echo json_encode("OK");
        }
    }
    else {
        error_log("check_if_banned() - no filename specified");
        echo "check_if_banned() - no filename specified";
    }


?>