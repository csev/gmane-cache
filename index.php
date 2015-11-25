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

$deflate = function_exists('gzdeflate') && function_exists('gzinflate');

header("Cache-Control: max-age=604800, public");

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

$n = count($pieces);
if ( $n < 3 ) {
?>
This is a caching server in order to allow the retreival of
messages from a gmane.org archive without overwhelming the
actual gmane server.

This server expects a URL of the form:

<?= isset($_SERVER['SCRIPT_URI']) ? $_SERVER['SCRIPT_URI'] : '..' ?>/<?= $ALLOWED[0] ?>/4/5

And returns messages in an mbox format.   No more than 10 
messages can be requested at one time.

There is a copy of this hosted at:

http://gmane.dr-chuck.net/

That is served through CloudFlare and so it should provide
fast access anywhere in the world.
<?php
    exit();
}

$first = $n - 3;

$list_id = array_search($pieces[$first],$ALLOWED);
if ( $list_id === false ) {
    die("Mailing list ".htmlentities($pieces[$first])." not found.");
}

$start = 0+$pieces[$first+1];
$end = 0+$pieces[$first+2];

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
	$expire = $CFG->expire - rand(0,$CFG->expire/10);
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

        // Inflate if necessary
        if ( $deflate ) {
            $row['message'] = gzinflate($row['message']);
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

    // Inflate if necessary
    $insert_text = $text;
    if ( $deflate ) {
        $insert_text = gzdeflate($text);
    }

    $stmt = $pdo->prepare('INSERT INTO messages
        (message_id, status, message, list_id, created_at, updated_at) VALUES
        (:mid, :stat, :mess, :lid, NOW(), NOW())
        ON DUPLICATE KEY UPDATE updated_at=NOW(), 
        message = :mess, status = :stat');
    $stmt->execute( array( 
        ':mid' => $message, 
        ':mess' => $insert_text, 
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
