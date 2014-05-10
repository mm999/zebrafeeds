<!DOCTYPE html>
<html>
<head>
<title>ZebraFeeds jobs</title>
</head>
<body>
<?php

require_once (__DIR__.'/../init.php');


if (strlen(ZF_URL) == 0) {
    echo "ZebraFeeds is not properly configured!<br/>";
    exit;
}

if ($_GET['key'] != md5(ZF_ADMINNAME.ZF_ADMINPASS)) {
    echo "No authorization<br/>";
    exit;
}


$subs = SubscriptionStorage::getInstance()->getActiveSubscriptions();
FeedCache::getInstance()->update($subs);



?>

</body>
</html>
