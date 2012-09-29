<!DOCTYPE html>
<html>
<head>
<title>ZebraFeeds installation</title>
 <style type="text/css">
  <!--
body {
	color: #000;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 80%;
	margin : 0;
	padding : 0;
}

#header{
background-color: #dbca95;
padding: 10px;
}

#core {

padding: 10px;

}
.twocols {
	margin-left: 10%;
	margin-bottom: 40px;
	position : static;
}
.twocols h2 {
	border: 2px solid #000;
	color: #0084ff;
}
.twocols .col1 {
	width : 49%;
	margin-left : 1%;
	float : left;
	text-align: left;
}
.twocols .col1, .col2:first-child {
	width : 30%;
	margin-left : 0;

}

.col1 p {
	height: 5em;
	padding: 0 5em 0 5em;
}
.smallprint {
	color: #ff9000;
	font-size: 80%;
	font-family: sans-serif;
}
  -->
  </style>
</head>
<body>
<div id="header">
<a href="http://cazalet.org/zebrafeeds"><img src="newsfeeds/images/zflogo.png" border="0" alt="ZebraFeeds logo"/></a> ZebraFeeds installation
</div>
<?php

require_once('newsfeeds/init.php');
require_once($zf_path.'admin/adminfuncs.php');

function displayProceedGotoButton($nextStep) {
	echo '<div>
		<form name="proceed" action="'.$_SERVER['PHP_SELF'].'" method="post">
		<input type="hidden" name="step" value="'.$nextStep.'"/>
		<input type="submit" name="go" value="Proceed to step '.$nextStep.'">
		</form></div>';
	//<a href="javascript:document.goto'.md5($list).'.submit();">Go to '.$list.' subscription list</a>
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
	@touch($zf_path.'config.php');
	@chmod($zf_path.'config.php',0666);
	@mkdir(ZF_DATADIR,0777);
	// do not use constant as it can cause problems in case of upgrade (???)
	@mkdir(ZF_CACHEDIR,0777);
	@chmod(ZF_OPMLDIR,0777);

	$ok = true;
	// config.php exists and is writable
	if(!is_writable($zf_path.'config.php')) {
		echo '<br/>'.$zf_path.'config.php is not writable (you cannot save changes)!<br/>';
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

	// cache/data exists
	if(!is_writable(ZF_OPMLDIR)) {
		echo '<br/>'.ZF_OPMLDIR.' is not writable!';
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

?>

<form name="configform" action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">

<div class="twocols">
	<div class="col1">
			<label for="zfurl">ZebraFeeds script URL : </label>
	</div>
	<div class="col2">
			<input name="zfurl" id="zfurl" type="text" size="50" value="<?php if(defined("ZF_URL") && ZF_URL!='') { echo ZF_URL; } else {if(zfurl()!=false) {echo zfurl();}} ?>" />
	</div>
	<div class="col1">
			<label for="adminname">Admin username : </label>
	</div>
	<div class="col2">
			<input name="adminname" type="text" id="adminname"	value="<?php echo defined("ZF_ADMINNAME")?ZF_ADMINNAME:'admin';?>" />
	</div>
	<div class="col1">
			<label for="newpassword">Admin password : </label>
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
			<label for="confirmpassword">Admin password confirm : </label>
	</div>
	<div class="col2">
			<input type="password" name="confirmpassword" id="confirmpassword"/>
	</div>
	<div class="col1">
			<label for="zflogintype">Admin panel login mechanism : </label>
	</div>
	<div class="col2">
			<select name="zflogintype" id="zflogintype" >
			  <option value="server" <?php if(ZF_LOGINTYPE=='server') echo 'selected="selected"';?>>server</option>
			  <option value="session" <?php if(ZF_LOGINTYPE=='session') echo 'selected="selected"';?>>session</option>
			</select>
			<span class="smallprint">session: will use cookies. server: requires .htaccess and .htpasswd on server</span>
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
		$config['adminpassword'] = md5($_POST['newpassword']);
	} else {
		if (defined("ZF_ADMINPASS")) {
			$config['adminpassword'] = ZF_ADMINPASS;
		} else {
			echo "No password defined. ";
			$ok = false;
		}
	}


	if ($_POST[zfurl] == "") {
		echo "ZebraFeeds URL not defined. ";
		$ok = false;
	}

	// default values for the other parameters saved in config.php
	$config["zfhomeurl"] = ZF_HOMEURL;
	$config["usesubs"] = ZF_USEOPML;
	$config["subfilename"] = ZF_HOMELIST;
	$config["refreshmode"] =  ZF_REFRESHMODE;
	$config["template"] = ZF_TEMPLATE;
	$config["displayerror"] = ZF_DISPLAYERROR;
	$config["encoding"] = ZF_ENCODING;
	$config["locale"] = ZF_LOCALE;
	$config["pubdateformat"] = ZF_PUBDATEFORMAT;
	$config["dateformat"] = ZF_DATEFORMAT;
	$config["nofuture"] = ZF_NOFUTURE;
	$config["rendermode"] = ZF_RENDERMODE;
	$config["ownername"] = ZF_OWNERNAME;
	$config["owneremail"] = ZF_OWNEREMAIL;
	$config["newitems"] = ZF_NEWITEMS;



	if ($ok && saveConfig($config)) {
		displayStatus('Basic configuration saved.');
		echo 'You must now go to the administration panel to complete the installation<br/><br/>';
		echo 'For security reasons, make sure to delete the file <code>install.php</code><br/><br/>';
		echo 'Go to the <a href="newsfeeds/admin/index.php?zfaction=config">Administration Panel</a>';

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

