<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>ZebraFeeds jobs</title>
</head>
<body>
<?php 
    
require_once('../init.php');
require_once($zf_path . 'includes/fetch.php');

/* refresh an array of feeds */
function zf_refreshFeeds(&$channels) {
    if (count($channels)>0) {
        foreach($channels as $channel) {
            if ($channel['issubscribed'] == "yes") {
                if (isset($channel['xmlurl']) && trim($channel['xmlurl']) != '' && $channel['showeditems'] > 0) {

                    echo '<li>refreshing <em>'.htmlentities($channel['title']) .'</em>: ';
                    $result = '';
                    $rss = zf_fetch_rss($channel, $channel['refreshtime'], $result);
                    if (!$rss) {
                        echo $result;
                    } else {
                        echo 'OK';
                    }
                    echo '</li>';

                    // if we implemented parallel fetch, we'll have to use this url 
                    //readfile(ZF_URL."/pub/refresh.php?key=".$_GET['key']."&xmlurl=".urlencode($feed['xmlurl'])."&refreshtime=".$feed['refreshtime']);


                    flush();
                    ob_flush();
                }
            }
        }
    } else {
        echo 'no feeds<br/>';
    }
}

/* refresh all channels from a list*/
function zf_refreshList($name) {
    echo 'processing list: <strong>'.htmlentities($name).'</strong><ul>';
    $list = new opml($name);
    if ($list->load()) {
        zf_refreshFeeds($list->channels);
    }
    echo "</ul>";
}


if (strlen(ZF_URL) == 0) {
    echo "ZebraFeeds is not properly configured!<br/>";
    exit;
} 

if ($_GET['key'] != md5(ZF_ADMINNAME.ZF_ADMINPASS)) {
    echo "No authorization<br/>";
    exit;
} 


if (ZF_USEOPML == 'yes') {
    /* simple case: we refreshing a single list. 
	* might be used in the future */
    if (isset($_GET['list'])) {
        $list = $_GET['list'];
        zf_refreshList($list);
        exit;
    } else {

        $listsNames = zf_getListNames();

        if (ZF_DEBUG) {
            print_r($listsNames);
        }

        if (count($listsNames) > 0 ) {
            foreach($listsNames as $name) {
                zf_refreshList($name);
            }
            echo "<h3>Refresh complete</h3>";

        } else {
            echo "No lists available";
        }
    }
} else {
    // ZF_USEOPML set to no: output will be configured by whoever is integrating the script on their site
    echo "Refresh function not available";
}



?>

</body>
</html>
