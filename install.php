<!DOCTYPE html>
	<html>
	<head>
	<title>ZebraFeeds</title>
	<link rel="stylesheet" type="text/css" href="res/css/admin.css"/>
	<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
	<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0"/>
	</head>
	<body>


	<header class="top">
		<a href="http://cazalet.org/zebrafeeds"><img src="res/img/logo-new.png" alt="ZebraFeeds"></a>
	</header>
<?php

require_once(__DIR__.'/init.php');
require_once('includes/adminfuncs.php');

function displayProceedGotoButton($nextStep) {
	echo '<div>
		<form name="proceed" action="'.$_SERVER['PHP_SELF'].'" method="post">
		<input type="hidden" name="step" value="'.$nextStep.'"/>
		<input type="submit" name="go" value="Proceed to step '.$nextStep.'">
		</form></div>';
}




if (!isset($_POST['step'])) {
	echo '<div id="core">';
	echo '<h2>Welcome to the ZebraFeeds installer</h2>';

	echo '<p>This installer will guide you through the installation/upgrade of <br/>ZebraFeeds in 2 simple steps.</p>';
	displayProceedGotoButton(1);
	echo '</div>';

} elseif ($_POST['step'] == 1) {
	echo '<div id="core">';
	echo '<h2>Step 1: Permissions</h2>';

	echo '<p>Attempting to set correct file and directory permissions...';
	@touch(ZF_CONFIGFILE);
	@chmod(ZF_CONFIGFILE,0666);
	@mkdir(ZF_DATADIR,0777);
	// do not use constant as it can cause problems in case of upgrade (???)
	@mkdir(ZF_CACHEDIR,0777);

	$ok = true;
	// config.php exists and is writable
	if(!is_writable(ZF_CONFIGFILE)) {
		echo '<br/>config.php is not writable (you cannot save changes)!<br/>';
		$ok = false;
	}

	// cache/data exists
	if(!is_writable(ZF_DATADIR)) {
		echo '<br/>'.ZF_DATADIR.' is not writable!';
		$ok = false;
	}
   if(!is_writable(ZF_CACHEDIR)) {
		echo '<br/>'.ZF_CACHEDIR.' is not writable!';
		$ok = false;
	}

	// zebrafeeds.opml exists and is writable
	if(!is_writable(ZF_OPMLFILE)) {
		echo '<br/>zebrafeeds.opml is not writable (you cannot save subscriptions)!<br/>';
		$ok = false;
	}

	if ($ok) {
		echo ' Done</p>';
		displayProceedGotoButton(2);
	} else {
		echo '<br/>Please correct this and restart the installer.</p>';

	}

	echo '</div>';

} elseif ($_POST['step'] == 2) {
	echo '<div id="core">';
	echo '<h2>Step 2: Basic configuration</h2>';


// set login method
// set admin name
// set admin pass
// set url of zebrafeeds
//

//TODO: display just for confirmation. Extract last part and save as zebrafeeds folder name. use it in init.php
$defaultUrl = dirname(getZfUrl());

?>

<form name="configform" action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">

<div class="twocols">
	<div class="col1">
			<label for="zfurl">URL to ZebraFeeds folder: </label>
	</div>
	<div class="col2">
			<input name="zfurl" type="text" id="zfurl"	value="<?php echo $defaultUrl;?>" />
	</div>
	<div class="col1">
			<label for="adminname">Admin username: </label>
	</div>
	<div class="col2">
			<input name="adminname" type="text" id="adminname"	value="<?php echo defined("ZF_ADMINNAME")?ZF_ADMINNAME:'admin';?>" />
	</div>
	<div class="col1">
			<label for="newpassword">Admin password: </label>
	</div>
	<div class="col2">
			<input type="password" name="newpassword" id="newpassword" />
				<?php
				// show the message only if pass already defined
	if (defined("ZF_ADMINPASS")) {
	   echo '<span class="smallprint">leave empty if you don\'t want to change pass</span>';
	}
?>
	</div>
	<div class="col1">
			<label for="confirmpassword">Admin password confirm: </label>
	</div>
	<div class="col2">
			<input type="password" name="confirmpassword" id="confirmpassword"/>
	</div>
	<div class="col1">
			<label for="zflogintype">Admin panel login mechanism:</label>
	</div>
	<div class="col2">
			<select name="zflogintype" id="zflogintype" >
			  <option value="server" <?php if(ZF_LOGINTYPE=='server') echo 'selected="selected"';?>>server</option>
			  <option value="session" <?php if(ZF_LOGINTYPE=='session') echo 'selected="selected"';?>>session</option>
			</select>
			<span class="smallprint">session: uses PHP session. server: requires .htaccess and .htpasswd on server</span>
	</div>
	</div>
	<input type="submit" name="dosave" value="Complete your installation"/>
	<input type="hidden" name="step" value="3"/>

  </form>
</div>
<?php

} elseif ($_POST['step'] == 3) {
	$config = $_POST;
	echo '<div id="core">';

	$ok = true;
	if($_POST['newpassword']==$_POST['confirmpassword'] && $_POST['newpassword']!='') {
		$config['adminpassword'] = crypt($_POST['newpassword']);
	} else {
		if (defined("ZF_ADMINPASS")) {
			$config['adminpassword'] = ZF_ADMINPASS;
		} else {
			echo "No password defined. ";
			$ok = false;
		}
	}


	// default values for the other parameters saved in config.php
	$config["zfurl"] = $_POST['zfurl'];

	$config["subtag"] = ZF_HOMETAG;
	$config["refreshmode"] =  ZF_REFRESHMODE;
	$config["template"] = ZF_TEMPLATE;
	$config["displayerror"] = ZF_DISPLAYERROR;
	$config["encoding"] = ZF_ENCODING;
	$config["locale"] = ZF_LOCALE;
	$config["pubdateformat"] = ZF_PUBDATEFORMAT;
	$config["dateformat"] = ZF_DATEFORMAT;



	if ($ok && saveConfig($config)) {
		displayStatus('Basic configuration saved.');
		echo '<br/>Please go to the <a href="index.php?zfaction=config">configuration page</a> to complete the installation<br/><br/>';
		echo 'For security reasons, make sure to delete the file <code>install.php</code><br/><br/>';
		echo 'Have a look at the <a href="embed-demo.php">Embed demo page</a> to embed feeds on your site.';

	} else {
		echo "Cannot continue the installation";
		displayStatus('Configuration NOT saved.');
	}
	echo '</div>';
}

//phpinfo();
?>
</body>
</html>

