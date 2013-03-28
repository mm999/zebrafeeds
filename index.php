<?php

/* this code section is for first install only

NOT NEEDED IN A PRODUCTION ENVIRONMENT

*/

require_once('newsfeeds/init.php');
// config.php exists and is writable
	if(!is_writable('newsfeeds/config.php')) {
		header('Location:install.php');
	}
?>

<!DOCTYPE html>
<html>
<head>
<title>ZebraFeeds personal aggregator demo</title>
<?php require('newsfeeds/zebraheader.php'); ?>
</head>
<body>
<h1>ZebraFeeds Demo</h1>
This is a demo of ZebraFeeds as a personal news aggregator. <br/>
Check out the <a href="documentation/doc.html">documentation</a> to start customizing it for your website.
<p>
Other demos: <a href="demo_rssfeed.php">RSS feed</a> - <a href="demo_js.html">Javascript integration</a> - <a href="demo_manual.php">manual control of feeds rendering</a>
</p>
<hr/>
<div style="	vertical-align: top;
	margin-left: 15px;
	margin-right: 15px;">
	<?php require ('newsfeeds/zebrafeeds.php'); ?>
</div>

</body>
</html>
