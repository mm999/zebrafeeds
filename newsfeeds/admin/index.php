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
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.


/* template for the admin page */

/* initialization part */
require_once('../init.php');
require_once($zf_path.'admin/adminfuncs.php');
ini_set("user_agent",ZF_USERAGENT);

$zfaction = isset($_GET['zfaction']) ? $_GET['zfaction'] : 'subscriptions';

if ($zfaction == 'logout')
    zfLogout();


zfLogin();


?>
<!DOCTYPE html>
	<html>
	<head>
	<title>ZebraFeeds admin panel</title>
	<link rel="stylesheet" type="text/css" href="admin.css"/>
	<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
	<meta content="utf-8" http-equiv="encoding">	</head>
	<body>


	<div id="top">
<div class="normaltext"><a href="http://cazalet.org/zebrafeeds"><img src="<?php echo ZF_URL;?>/images/logo_admin.png" alt="ZebraFeeds"/></a></div>
       <div class="normaltext">administration panel :: <?php echo '<a href="',ZF_HOMEURL,'">View website</a> - <a href="', $_SERVER['PHP_SELF'] , '?zfaction=logout';?>">Logout</a></div>
	</div>
<div id="header">
			<ul class="tabs">
		<?php if (ZF_USEOPML == 'yes')
            echo "<li"; if (in_array($zfaction, array('subscriptions', 'addnew' ,'importlist'))) echo " class=\"active\""; echo ">";
			echo "<a href=\"" . $_SERVER['PHP_SELF'] . "?zfaction=subscriptions\">Subscriptions</a></li>";?>
        <?php echo "<li"; if ($zfaction == "config") echo " class=\"active\""; echo ">" ?>
			<a href="<?php echo $_SERVER['PHP_SELF'] . '?zfaction=config';?>">Settings</a>
		</li>
		<li <?php if ($zfaction == "maintenance") echo " class=\"active\""; ?>>
			<a href="<?php echo $_SERVER['PHP_SELF'] . '?zfaction=maintenance';?>">Maintenance</a>
		</li>
		</ul>


</div>
<?php
// after the normal header for all admin pages, select now what we gonna show

	if ($zfaction == 'subscriptions') {
        include($zf_path.'admin/subscriptions.php');
    } elseif ($zfaction == 'addnew') {
        include($zf_path.'admin/addnewfeed.php');
    } elseif ($zfaction == 'importlist') {
        include($zf_path.'admin/importlist.php');
	} elseif ($zfaction == 'config') {
        include($zf_path.'admin/changeconfig.php');
	} elseif ($zfaction == 'maintenance') {

        echo '<div id="core">
				<div class="frame"><strong>Manage cached data</strong>
					<ul>
					<li>
						<a href="';
				echo $_SERVER['PHP_SELF'] . '?zfaction=cleanold" onclick="return confirm(\'Are you sure you want to delete old cache and history data?\');">Clean up</a>
						- clean up caches and history older than 2 weeks.
					</li>
					<li>
						<a href="';
						echo $_SERVER['PHP_SELF'] . '?zfaction=flush" onclick="return confirm(\'Are you sure you want to delete ALL cache and history data?\');">Flush!</a>
						- flush ALL caches and history.
					</li>
					</ul>
				</div>';

        echo '<div class="frame"><strong>Updates</strong><br/><br/>';
        echo "Your ZebraFeeds version: " . ZF_VER . "<br/><br/>";
        @$update = readfile('http://www.cazalet.org/zebrafeeds/latest.php');
        if (!$update)
            echo "Error: could not open update file.<br/><br/>You can check it manually at: <a href=\"http://cazalet.org/zebrafeeds/latest.php\">http://cazalet.org/zebrafeeds/latest.php</a>.";
        echo '</div></div>';
	} elseif ($zfaction == 'cleanold') {
       $size = clearOldData(ZF_DATADIR, 60*60*24*14, "hst");
       $size += clearOldData(ZF_CACHEDIR, 60*60*24*14);
        echo '<div id="core"><h3>Clean up</h3>';
        echo "Cleared cache and history files older than 2 weeks.<br/>";
        $size = sprintf("%01.2f KiloBytes",$size / 1024);
        echo "$size recovered on server.";
        echo '<br/><br/></div>';
	} elseif ($zfaction == 'flush') {
       $size = clearOldData(ZF_DATADIR, 0, "hst");
       $size += clearOldData(ZF_CACHEDIR, 0);
        echo '<div id="core"><h3>Clean up</h3>';
        echo "Flushed cache and history files.<br/>";
        $size = sprintf("%01.2f KiloBytes",$size / 1024);
        echo "$size recovered on server.";
        echo '<br/><br/></div>';
    } else {

        // repeat of top bar
?>


<?php
    }
// Standard footer for all admin pages
?>
    <div id="bottom">
          powered by <a href="http://cazalet.org/zebrafeeds">ZebraFeeds <?php echo ZF_VER;?></a>
          - &copy;2006-2012 by Laurent Cazalet
    </div>
</body>
</html>
