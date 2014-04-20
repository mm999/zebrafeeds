<!DOCTYPE html>
<html>
<head>
<title>ZebraFeeds jobs</title>
</head>
<body>
<?php

require_once __DIR__.'/../init.php');

/* refresh an array of feeds */
function zf_refreshFeeds($subscriptions) {
        foreach($subscriptions as $sub) {
            if ($sub->isActive) {

                echo '<li>refreshing <em>'.htmlentities($sub->title) .'</em>: ';
                $result = '';
                FeedCache::getInstance()->update(array($sub->id => $sub));
                echo '</li>';

                // if we implemented parallel fetch, we'll have to use this url
                //readfile(ZF_URL."/pub/refresh.php?key=".$_GET['key']."&xmlurl=".urlencode($feed['xmlurl'])."&refreshtime=".$feed['refreshtime']);


                flush();
                ob_flush();

            }
        }
    } else {
        echo 'no feeds<br/>';
    }
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
    $subs = SubscriptionStorage::getInstance()->getActiveSubscriptions();
    zf_refreshFeeds($subs);
} else {
    // ZF_USEOPML set to no: output will be configured by whoever is integrating the script on their site
    echo "Refresh function not available";
}



?>

</body>
</html>
