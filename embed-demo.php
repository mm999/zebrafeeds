<?php

/* this code section is for this demo file/first install only
NOT NEEDED IN A PRODUCTION ENVIRONMENT
*/
require_once('init.php');
// config.php exists and is writable
	if(!is_writable('config.php')) {
		header('Location:install.php');
	}
?>

<!DOCTYPE html>
<html>
<head>
<title>ZebraFeeds personal aggregator demo</title>
<?php require('embed/header.php'); ?>
</head>
<body>
<h1>ZebraFeeds Demo</h1>
This is a demo of ZebraFeeds as a personal news aggregator. <br/>
Check out the <a href="documentation/doc.html">documentation</a> to start customizing it for your website.
<hr/>
<div style="	vertical-align: top;
	margin-left: 15px;
	margin-right: 15px;">
	<?php require ('embed/feeds.php'); ?>
</div>

</body>
</html>
