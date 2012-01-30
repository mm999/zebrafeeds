<!DOCTYPE html>
<html>
<head>
<title>ZebraFeeds personal aggregator demo</title>
<?php require('newsfeeds/zebraheader.php'); ?> 
</head>
<body>
<h1>ZebraFeeds Demo</h1>
This page shows a demo of a possible personal aggregator.
<hr/>
<div style="
	font-size: 80%;
">
	<?php require ('newsfeeds/zebrabar.php'); ?>
</div>
<div style="	vertical-align: top;
	margin-left: 15px;
	margin-right: 15px;">
	<?php require ('newsfeeds/zebrafeeds.php'); ?>
</div>

</body>
</html>
