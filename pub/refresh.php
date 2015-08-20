<!DOCTYPE html>
<html>
<head>
<title>ZebraFeeds jobs</title>
</head>
<body>
<?php

require_once (__DIR__.'/../init.php');


if ($_GET['key'] != md5(ZF_ADMINNAME.ZF_ADMINPASS)) {
    echo "No authorization<br/>";
    exit;
}


$subs = SubscriptionStorage::getInstance()->getActiveSubscriptions();
FeedCache::getInstance()->update($subs, 'auto');
echo 'complete';


?>

</body>
</html>
