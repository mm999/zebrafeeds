<?php
// ZebraFeeds - copyright (c) 2006 Laurent Cazalet
// http://www.cazalet.org/zebrafeeds
//

global $zf_path;
$zf_path = str_replace("\\", "/", dirname(__FILE__)) . "/";
require_once($zf_path . 'config.php');


/* initialization for both admin and user areas */

// hidden settings, no GUI for these
define('ZF_DEFAULT_NEWS_COUNT', 10);
define('ZF_DEFAULT_REFRESH_TIME', 300);
define("ZF_DYNAMICNEWSLENGTH","500"); // news longer than that will be dynamically obtained. 0 to disable. Requires compatible templates. 
define('ZF_SESSION_DURATION', 900); // 15 minutes before unmarking items as new
define('ZF_VISITOR_COOKIE_EXPIRATION',60*60*24*30); //30 days life-expectancy for client-side cookies to mark items as new
define('ZF_FORCE_ENCODED_CONTENT', 'yes'); // ONLY FOR MAGPIE. stored in cache. if this is changed, it will be active only after cache is refreshed
define('ZF_GROUP_BY_DAY', 'yes'); // if yes, items are grouped by day in non-per-channel views
define('ZF_MAX_SUMMARY_LENGTH', 1200); // if description is longer (tags-stripped), let's truncate to make the summary
define('ZF_SUMMARY_TRUNCATED_LENGTH', 500); // if truncate to summary, here's the remaining length
define('ZF_SHOWCREDITS', 'no');
define('ZF_RSSEXPORTSIZE', 25); //25 news exported in RSS,  max
define('ZF_ONLYNEW', 'no'); // if yes, show only never seen news
define('ZF_NEWONTOP', 'no'); // if yes, will show first the new/unseen items on top in views grouped by date
define('ZF_ARTICLELINKPROCESSOR', 'http://www.instapaper.com/text?u=%s'); // when building article link, if process is to be used. %s replace with encoded link to original article link
// ideally, this should be defined in the template
//define('ZF_ISNEW_STRING', '<img src="'.ZF_URL.'/images/new.png" border="0" title="is new since last visit" alt="New"/>');
define('ZF_ISNEW_STRING', 'newclass');

/* debug values for ZF_DEBUG
 4: feed aggregation
 6: run time
 7: history, session and cookies
10: OPLM read
*/

define('ZF_DEBUG', 0);

/*--------------------------*/

error_reporting(0);

if (ZF_USEOPML == 'yes') {
    require_once($zf_path . 'includes/opml.php');
}
require_once($zf_path . 'includes/common.php');

setlocale(LC_ALL, ZF_LOCALE);
define('ZF_VER', '1.3_DEV_20111022');
define('ZF_USERAGENT',"ZebraFeeds/".ZF_VER." (http://www.cazalet.org/zebrafeeds)");

define("ZF_DATADIR", $zf_path.'data');
define("ZF_OPMLBASEDIR", 'categories');
define("ZF_OPMLDIR", $zf_path.ZF_OPMLBASEDIR);
define("ZF_TEMPLATESDIR", $zf_path.'templates');

// full path
define("ZF_CACHEDIR", ZF_DATADIR.'/cache');
//define("ZF_RSSPARSER", "magpie");
define("ZF_RSSPARSER", "simplepie");


//Defaults
function defaultConfig($name,$value) {

    if (!defined($name)) {
        define($name,$value);
    }
}

//default values for parameters with a UI
defaultConfig('ZF_LOGINTYPE', 'server');
defaultConfig('ZF_LOCALE', 'english');
defaultConfig('ZF_OWNEREMAIL', '');
defaultConfig('ZF_OWNERNAME', '');
defaultConfig('ZF_PUBDATEFORMAT', '%x, %X');
defaultConfig('ZF_DATEFORMAT', '%x');
defaultConfig('ZF_REFRESHMODE','automatic');
defaultConfig('ZF_RENDERMODE','automatic');
defaultConfig('ZF_NEWITEMS','no');
defaultConfig('ZF_NOFUTURE','no');
defaultConfig('ZF_HOMEURL','');
defaultConfig('ZF_USEOPML', 'yes');
defaultConfig('ZF_ENCODING', 'UTF-8');
defaultConfig('ZF_DISPLAYERROR', 'no');
defaultConfig('ZF_TEMPLATE', 'infojunkie');
defaultConfig('ZF_HOMELIST', 'sample');


//error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting (E_ALL ^ E_NOTICE);
if (ZF_DEBUG) {
    error_reporting (E_ALL);
	global $zf_debugData;
	$zf_debugData['clock'][] = microtime();
	if (function_exists('getrusage')) {
		$dat = getrusage();
		$zf_debugData['utime_before'] = $dat["ru_utime.tv_sec"].$dat["ru_utime.tv_usec"];
		$zf_debugData['stime_before'] = $dat["ru_stime.tv_sec"].$dat["ru_stime.tv_usec"];	
	}

	
}
?>
