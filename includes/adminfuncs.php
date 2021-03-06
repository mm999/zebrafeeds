<?php
// ZebraFeeds - copyright (c) 2006 Laurent Cazalet
// http://www.cazalet.org/zebrafeeds
//
// zFeeder 1.6 - copyright (c) 2003-2004 Andrei Besleaga
// http://zvonnews.sourceforge.net
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

if (!defined('ZF_VER')) exit;

/* checks for authentication, and gives a login form if needed */
function zfLogin() {

	if(ZF_LOGINTYPE=='server') {
		if (($_SERVER['PHP_AUTH_USER'] != ZF_ADMINNAME || crypt($_SERVER['PHP_AUTH_PW'], ZF_ADMINPASS) != ZF_ADMINPASS) && (ZF_ADMINPASS!='')) {
			header("WWW-Authenticate: Basic realm=\"ZebraFeeds Authentication\"");
			header("HTTP/1.0 401 Unauthorized");
			zfLoginFailed();
		}
	} elseif(ZF_LOGINTYPE=='session') {
		session_start(); // needed if authentication mechanism is session
		if (isset($_POST['submit_login']) && $_POST['submit_login'] == 'Log In!')
		{
			if (($_POST['admin_user'] != ZF_ADMINNAME || crypt($_POST['admin_pass'], ZF_ADMINPASS) != ZF_ADMINPASS) && (ZF_ADMINPASS!=''))
			{
				zfLoginFailed();
			} else	{
				$_SESSION['admin_user'] = $_POST['admin_user']; // set username
				$_SESSION['admin_pass'] = $_POST['admin_pass']; // set password
				$_SESSION['logged_in'] = 1;
			}
		}
		if ($_SESSION['logged_in'] != 1) {
			echo "<!DOCTYPE html";
			echo "<head><title>ZebraFeeds Authentication</title>";
			echo '<link rel="stylesheet" type="text/css" href="res/css/admin.css" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0"/>';
			echo "</head><body>";
			echo "<header class=\"top\"><a href=\"http://cazalet.org/zebrafeeds\"><img src=\"res/img/logo-new.png\" border=\"0\" alt=\"ZebraFeeds\"/></a></header>";
			echo "<div id=\"core\"><form action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">";
			echo "<div id=\"loginform\"><h3>Login</h3>";
				echo "<div id=\"user\"><label for=\"username\">Username</label>";
			echo "<br/><input type=\"text\" id=\"username\" name=\"admin_user\" /><br/><br/>";
				echo "<div id=\"pass\"><label for=\"password\">Password</label>";
			echo "<br/><input type=\"password\" id=\"password\" name=\"admin_pass\" /></div>";
				echo "</div><input type=\"submit\" name=\"submit_login\" value=\"Log In!\" />";
			echo "</div></div>";
			echo "</form></body></html>";
			exit;
		}
	} else {
		echo "<html><head><title>ZebraFeeds - auth not set</title></head><body><div align=\"center\"><br/><h3>Authentication method not set!</h3></div></body></html>";
		exit;
	}
}



function zfLoginFailed() {
	echo "<!DOCTYPE html";
	echo "<head><title>Unauthorized Access</title>";
	echo '<link rel="stylesheet" type="text/css" href="res/css/admin.css" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0"/>';
	echo "</head><body>";
	echo "<header class=\"top\"><a href=\"http://cazalet.org/zebrafeeds\"><img src=\"res/img/logo-new.png\" border=\"0\" alt=\"ZebraFeeds\"/></a></header>";
	echo "<div id=\"core\"><span class=\"accessdenied\">ACCESS DENIED</span><br/><br/>";
	echo "<a href=\"./\">Please login</a></div>";
	echo "</body></html>";
	exit;
}

function zfLogout() {
	if (ZF_LOGINTYPE == 'server') {
		header("WWW-Authenticate: Basic realm=\"ZebraFeeds Authentication\"");
		header("HTTP/1.0 401 Unauthorized");
	} elseif (ZF_LOGINTYPE == 'session') {
		session_start();
		$_SESSION['logged_in'] = 0; // just in case
		$_SESSION['admin_user'] = '';
		$_SESSION['admin_pass'] = '';
		session_unset();			// kill all session globals
		session_destroy();			// kill everything
	} else {
		echo "<!DOCTYPE html><html><head><title>ZebraFeeds - auth not set</title></head><body><div align=\"center\"><br/><h3>Authentication method not configured !</h3></div></body></html>";
	}
	echo '<!DOCTYPE html><html><head>	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0"/>
<title>ZebraFeeds logout</title></head><body><div align="center"><br/><h3>You are logged out !</h3><a href="./">Login again !</a></div></body></html>';
	exit;
}


function displayStatus($message) {
	echo '<div id="status">'.$message.'</div>';
}

/* write the config array to the config file */
function saveConfig(&$config) {
	@$fp = fopen('config.php','w');
	if($fp) {
		fwrite($fp,"<?php\n// ZebraFeeds ".ZF_VER." - copyright (c) Laurent Cazalet\n");
		fwrite($fp,"// configuration file\n\n\n");
		fwrite($fp,"define('ZF_CONFIGVERSION','".ZF_VER."');\n");
		fwrite($fp,"// general configuration options //\n\n");
		fwrite($fp,"define('ZF_URL','".$config['zfurl']."'); // URL of ZebraFeeds folder\n");
		fwrite($fp,"define('ZF_LOGINTYPE','".$config['zflogintype']."'); // server - server HTTP auth; session - PHP sessions auth\n");
		fwrite($fp,"define('ZF_ADMINNAME','".$config['adminname']."'); // admin username\n");
		fwrite($fp,"define('ZF_ADMINPASS','".$config['adminpassword']."'); // crypted admin password. Leave empty to reset.\n");
		fwrite($fp,"\n\n// Aggregator options //\n\n");
		fwrite($fp,"define('ZF_HOMETAG','".$config['subtag']."'); // tag for the default list of subscriptions\n");
		fwrite($fp,"define('ZF_REFRESHMODE','".$config['refreshmode']."'); // automatic: feeds are refreshed when page is generated. request: use a refresh link. see admin page for details\n");
		fwrite($fp,"define('ZF_SORT','".$config['sortmode']."'); // \n");
		fwrite($fp,"define('ZF_TRIMTYPE','".$config['trimtype']."'); // \n");
		fwrite($fp,"define('ZF_TRIMSIZE','".$config['trimsize']."'); // \n");
		fwrite($fp,"define('ZF_NOFUTURE','".$config['nofuture']."'); // if yes then does not show news with a timestamp from the future\n");
		fwrite($fp,"\n\n// Template options //\n\n");
		fwrite($fp,"define('ZF_TEMPLATE','".$config['template']."'); // the default templates used to display the news (subdirectory name from templates directory)\n");
		fwrite($fp,"define('ZF_DISPLAYERROR','".$config['displayerror']."'); // if yes then when a feed cannot be read (or has errors) formatted error message shows in {description}\n");
		fwrite($fp,"\n\n// Localization options //\n\n");
		fwrite($fp,"define('ZF_ENCODING','".$config['encoding']."'); // character encoding for output\n");
		fwrite($fp,"define('ZF_LOCALE','".$config['locale']."'); // language for dates, system messages\n");
		fwrite($fp,"define('ZF_PUBDATEFORMAT','".$config['pubdateformat']."'); // format passed to strftime to convert dates got from RSS feeds\n");
		fwrite($fp,"define('ZF_DATEFORMAT','".$config['dateformat']."'); // format passed to strftime to display date when displaying news grouped by date\n");
		fwrite($fp,"\n\n//////END OF CONFIGURATION///////////////////////////////////////////////////\n\n");
		fwrite($fp,"\n\n?>");
		fclose($fp);

		return true;
	} else return false;

}


// delete all files in $dir that are older than $age
// restrict check to files with ext $ext, if provided
// returns number of bytes freed
function clearOldData($dir, $age, $ext='') {
	$now = time();
	$size = 0;

	if ($handle = opendir($dir)) {
		while( false !== ($file = readdir($handle))) {
			// skip ., .. and dirs
			if ($file == '.' && $file == '..') {
				continue;
			}

			if (!is_file($dir.'/'.$file)) {
				continue;
			}
			if ($ext != '' && !strpos($file, '.'.$ext) ) {
				continue;
			}
			if ($now - filemtime($dir.'/'.$file) > $age) {
				$size += filesize($dir.'/'.$file);
				unlink($dir.'/'.$file);
			}
		}
		closedir($handle);
	}

	return $size;
}

