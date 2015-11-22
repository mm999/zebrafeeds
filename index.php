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
require_once('init.php');
require_once('includes/adminfuncs.php');


$zfaction = param('zfaction', ZF_DEFAULT_ADMIN_VIEW);

zf_debug('zfaction is '.$zfaction);

// another quick thing to fix later.
// if zfaction default is subscriptions, we never get to
// see the article page when clicked from the feeds page
$q = param('q', '');
if (strlen($q) >0) $zfaction='feeds';



zfLogin();

//dirty... treat async calls first to avoid page header etc
switch ($zfaction) {
	case 'store':
		Header('Content-Type: text/html; charset='.ZF_ENCODING);
		$storage = SubscriptionStorage::getInstance();
		$id = $_POST['id'];

		$sub = $storage->getSubscription($id);
		if ($sub) {
			$sub->title = $_POST['title'];
			$sub->xmlurl =	 $_POST['xmlurl'];
			$sub->description = $_POST['description'];
			$sub->position = $_POST['position'];
			$sub->shownItems = $_POST['shownitems'];
			$sub->isActive = ($_POST['isactive'] =='yes');
			$sub->tags = explode(',', $_POST['tags']);

			if ($storage->storeSubscription($sub)) {
				echo 'Saved';
			} else {
				echo $storage->lastError;
			}
		} else {
			echo $storage->lastError;
		}
		exit;
		break;

	case 'delete':
		Header('Content-Type: text/html; charset='.ZF_ENCODING);
		$storage = SubscriptionStorage::getInstance();
		$id = $_POST['id'];

		if (!$storage->cancelSubscription($id)) echo $storage->lastError;
		exit;
		break;

}

?>
<!DOCTYPE html>
	<html>
	<head>
	<title>ZebraFeeds</title>
	<link rel="stylesheet" type="text/css" href="res/css/admin.css"/>
	<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
	<meta name="viewport" content="initial-scale=1.0"/>
<?php include('embed/header.php'); ?>

	</head>
	<body>


	<header class="top">
		<a href="<?echo $_SERVER['PHP_SELF'];?>"><img src="res/img/logo-new.png" alt="ZebraFeeds"></a>
	</header>
	<nav id="menu">
		<ul>
		<?php echo "<li"; if ($zfaction == "feeds") echo " class=\"active\""; echo ">" ?>
			<a href="<?php echo $_SERVER['PHP_SELF'] . '?zfaction=feeds';?>">Feeds</a>
		</li>
		<?php
			echo "<li"; if (in_array($zfaction, array('subscriptions', 'addnew' ,'importlist'))) echo " class=\"active\""; echo ">";
			echo "<a href=\"" . $_SERVER['PHP_SELF'] . "?zfaction=subscriptions\">Subscriptions</a></li>";?>
		<?php echo "<li"; if ($zfaction == "config") echo " class=\"active\""; echo ">" ?>
			<a href="<?php echo $_SERVER['PHP_SELF'] . '?zfaction=config';?>">Settings</a>
		</li>
		<li <?php if ($zfaction == "logout") echo " class=\"active\""; ?>>
			<a href="<?php echo $_SERVER['PHP_SELF'] . '?zfaction=logout';?>">Logout</a>
		</li>
		</ul>


	</nav>
<?php
// after the normal header for all admin pages, select now what we gonna show

	switch ($zfaction) {

		case 'logout':
			zfLogout();
			break;

		case 'feeds':
			// here: show tags menu
			$tags = SubscriptionStorage::getInstance()->getTags();
			echo '<div id="core">';
			echo '<div style="margin: 25px auto; max-width: 750px;">Tag: ';
			foreach ($tags as $tag) {
				echo '<a href="?zfaction=feeds&amp;tag='.rawurlencode($tag).'">'.$tag.'</a>&nbsp;';
			}
			$tag = param('tag',ZF_HOMETAG);
			if (ZF_ONLYNEW!=='no') {
				echo '| <em>showing only new</em>&nbsp;<a href="?zfaction=feeds&amp;tag='.rawurlencode($tag).'&onlynew=0">Show all</a>';
			} else {
				echo '| <em>showing all news</em>&nbsp;<a href="?zfaction=feeds&amp;tag='.rawurlencode($tag).'&onlynew=1">Show only new</a>';
			}

			echo '</div>';
			include('embed/feeds.php');
			echo '</div>';//core
			break;

		case 'subscriptions':
			include('includes/subscriptions.php');
			break;

		case 'addnew':
			include('includes/addnewfeed.php');
			break;

		case 'config':
			include('includes/changeconfig.php');

			break;

		case 'compact':
			FeedCache::getInstance()->compact();
			echo '<div id="core"><h3>Compact</h3>';
			echo 'Cache compacted</div>';
			break;

		case 'flush':
			FeedCache::getInstance()->flush();
			echo '<div id="core"><h3>Clean up</h3>';
			echo "Flushed cache";
			echo '<br/><br/></div>';
			break;


	case 'store':
		Header('Content-Type: text/html; charset='.ZF_ENCODING);
		$storage = SubscriptionStorage::getInstance();
		$id = $_POST['id'];

		$sub = $storage->getSubscription($id);
		if ($sub) {
			$sub->title = $_POST['title'];
			$sub->xmlurl =	 $_POST['xmlurl'];
			$sub->description = $_POST['description'];
			$sub->position = $_POST['position'];
			$sub->shownItems = $_POST['shownitems'];
			$sub->isActive = ($_POST['isactive'] =='yes');
			$sub->tags = explode(',', $_POST['tags']);

			if ($storage->storeSubscription($sub)) {
				echo 'Saved';
			} else {
				echo $storage->lastError;
			}
		} else {
			echo $storage->lastError;
		}
		break;

	case 'delete':
		Header('Content-Type: text/html; charset='.ZF_ENCODING);
		$storage = SubscriptionStorage::getInstance();
		$id = $_POST['id'];

		if (!$storage->cancelSubscription($id)) echo $storage->lastError;
		break;

	}

		// repeat of top bar
?>


	<div id="bottom">
		  <a href="http://cazalet.org/zebrafeeds">ZebraFeeds <?php echo ZF_VER;?></a>
	</div>
</body>
</html>
