<?php
header('Content-Type: text/plain; charset=utf-8');

if ( ! file_exists("config.php") ) { 
    echo("Please see configuration instructions at\n\n");
    echo("https://github.com/csev/gmane-cache\n\n");
    die("Not configured.");
}

require_once "config.php";
require_once "pdo.php";
require_once "tsugi_util.php";

// header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
// header("Cache-Control: post-check=0, pre-check=0", false);
// header("Pragma: no-cache");


$local_path = route_get_local_path(__DIR__);
$pos = strpos($local_path,'?');
$query = false;
$vars = array();
if ( $pos > 0 ) {
    $query = substr($local_path,$pos+1);
    parse_str($query,$vars);
    $local_path = substr($local_path,0,$pos);
}
$pieces = explode('/',$local_path);

if ( count($pieces) != 3 ) {
    die("Expecting URL of form /gmane.comp.cms.sakai.devel/12/13");
}

$list_id = array_search($pieces[0],$ALLOWED);
if ( $list_id === false ) {
    die("Mailing list ".htmlentities($pieces[0])." not found.");
}

$start = 0+$pieces[1];
$end = 0+$pieces[2];

if ( $start < 1 || $end < 1 ) {
    die("Message numbers must be numeric and > 0");
} else if ( $end <= $start ) {
    die("End message number must be > starting message number");
} else if ( $end > $start+10 ) {
    die("No more than 10 messages can be requested at the same time");
}

$baseurl = "http://download.gmane.org/gmane.comp.cms.sakai.devel/";

$message = $start;
$debug = array();
$output = "";
while ( $message < $end ) {
    $stmt = $pdo->prepare('SELECT status, message AS message, 
        created_at, updated_at, NOW() as now FROM messages
        WHERE message_id = :mi AND list_id = :lid');
    $stmt->execute(array( ':mi' => $message, ':lid' => $list_id ) );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check expiration
    $datediff = -1;
    if ( $row !== false ) {
        $now_str = $row['now'];
        $now = strtotime($now_str);
        $updated_at = $row['updated_at'];
        $updated_time = strtotime($updated_at);
        $datediff = $now - $updated_time;
        if ( $datediff >= $CFG->expire ) {
            $debug[] = "$message expired diff=$datediff updated_at=$updated_at";
            $row = false;
        }
    }

    // We have an unexpired row.
    if ( $row !== false ) {
        // Check data quality
        if ( $row['status'] != 200 || strlen($row['message']) < 1 ) {
            $debug[] = "$message status=$status, length=".strlen($row['message']);
            $message ++;
            continue;
        }

        $debug[] = "$message from cache diff=$datediff";
        $output .= $row['message'] . "\n";
        $message ++;
        continue;
    }

    // Need some new data - lets call gmane
    $url = $baseurl . $message . '/' . ($message+1);
    $debug[] = $url;

    // global $last_http_response;
    // global $LastHeadersSent;
    // global $LastHeadersReceived;
    $text = getCurl($url, $header=false);

    // TODO: What about attachments...
    $debug[] = "$message retrieved status=$last_http_response, length=".strlen($text);
    if ( strlen($text) > $CFG->maxtext ) {
        $text = substr($text,0,$CFG->maxtext)."\n";  // Sanity
        $debug[] = "$message truncated to ".$CFG->maxtext;
    }

    $stmt = $pdo->prepare('INSERT INTO messages
        (message_id, status, message, list_id, created_at, updated_at) VALUES
        (:mid, :stat, :mess, :lid, NOW(), NOW())
        ON DUPLICATE KEY UPDATE updated_at=NOW(), 
        message = :mess, status = :stat');
    $stmt->execute( array( 
        ':mid' => $message, 
        ':mess' => $text, 
        ':stat' => $last_http_response,  
        ':lid' => $list_id) 
    );

    if ( $last_http_response != 200 || strlen($text) < 1 ) {
        error_log("status=$last_http_response length=".strlen($text)." ".$url);
        $message++;
        continue;
    }

    $output .= $text . "\n";

    $message ++;
}

// Sweet debug output
$dbg = "";
foreach ( $debug as $line ) {
    if (strlen($dbg) > 0 ) $dbg .= ', ';
    $dbg .= $line;
    header('X-Gmane-Debug: '.$dbg);
}

echo($output);
