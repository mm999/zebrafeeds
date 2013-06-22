<?php

/* this code section is for first install only

NOT NEEDED IN A PRODUCTION ENVIRONMENT

*/

ini_set('display_errors', 1);

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
	<div id="wrapper">
	    <div id="zfOverlay"></div>
		<nav id="zebrabar">
			<?php #require ('newsfeeds/zebrabar.php'); ?>
		</nav>
		<section id="content">
		    <header class="menuBar">
    			<div class="titlebar">
    			    <a href="#" class="btn-menu btn">
    			        <i class="icon-reorder"></i>
    			    </a>
    			    <h1>Zebrafeeds</h1>
    			</div>
    		</header>
			<div class="main"></div>
		</section>
        <section id="zffixednews">
            <header>
                <div class="titlebar">
                    <a href="#" class="btn-close btn">
                        <i class="icon-angle-left"></i>
                    </a>
                    <h1></h1>
                </div>
            </header>
            <div id="newsContent"></div>
        </section>
	</div>
</body>
</html>
