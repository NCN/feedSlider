<?php
    //ini_set('display_errors', 'On'); // Turning these on will make errors 'echo' back to AJAX causing bad formatted json
    //error_reporting(E_ALL | E_STRICT); // Turning these on will make errors 'echo' back to AJAX causing bad formatted json

    //PHP SCRIPT: imageFetch.php - get file names of images from present folder
    Header("content-type: application/x-javascript");

    //set_time_limit(300); // 5 minutes timeout time

    if( $_REQUEST["filename"] )
    {
        $filename = $_REQUEST['filename'];
        
        $myFile = "banned_images.txt";
        $fh = fopen($myFile, 'a') or die("can't open file");
        $stringData = "$filename\n";
        fwrite($fh, $stringData);
        fclose($fh);

        error_log("ban_file() - banning filename: $filename");
        echo "ban_file() - banning filename: $filename";
    }
    else {
        error_log("ban_file() - no filename specified");
        echo "ban_file() - no filename specified";
    }


?>