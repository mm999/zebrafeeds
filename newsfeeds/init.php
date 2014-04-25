<?php
// ZebraFeeds - copyright (c) 2006-2013 Laurent Cazalet
// http://www.cazalet.org/zebrafeeds
//

include_once __DIR__.'/config.php';




/* initialization for both admin and user areas */

// hidden settings, no GUI for these
define('ZF_DEFAULT_NEWS_COUNT', 5);
define('ZF_DEFAULT_REFRESH_TIME', 120);
define('ZF_SESSION_DURATION', 900); // 15 minutes before unmarking items as new
define('ZF_VISITOR_COOKIE_EXPIRATION',60*60*24*30); //30 days life-expectancy for client-side cookies to mark items as new
define('ZF_FORCE_ENCODED_CONTENT', 'yes'); // ONLY FOR MAGPIE. stored in cache. if this is changed, it will be active only after cache is refreshed
define('ZF_GROUP_BY_DAY', 'yes'); // if yes, items are grouped by day when multiple channels sorted by date
define('ZF_MAX_SUMMARY_LENGTH', 300); // if description is longer (tags-stripped), let's truncate to make the summary
define('ZF_SUMMARY_TRUNCATED_LENGTH', 300); // if truncate to summary, here's the remaining length
define('ZF_SHOWCREDITS', 'no');
define('ZF_RSSEXPORTSIZE', 25); //25 news exported in RSS,  max
define('ZF_ONLYNEW', 'no'); // if yes, show only never seen news
define('ZF_NEWONTOP', 'no'); // if yes, will show first the new/unseen items on top in views grouped by date
// ideally, this should be defined in the template
//define('ZF_ISNEW_STRING', '<img src="'.ZF_URL.'/images/new.png" border="0" title="is new since last visit" alt="New"/>');
define('ZF_ISNEW_STRING', 'newclass');

/* bit debug values for ZF_DEBUG
*/
define('DBG_LIST', 2); // list handling & management
define('DBG_AGGR', 4); // feed aggregation/merging
define('DBG_RUNTIME', 8); // trace runtime
define('DBG_OPML', 16); // opml load
define('DBG_SESSION', 32); // history, session, cookies
define('DBG_FEED', 64); // handling/fetching feeds
define('DBG_RENDER', 128); // view and template rendering
define('DBG_FILTER', 256); // item filtering
define('DBG_ALL', 0xFFFFFFFFF); // very verbose

// use DBG_xxx | DBG_yyy | ... to select what to see in the logs
define('ZF_DEBUG', 0 );

// debug output 1=console otherwise stdout
define('ZF_DEBUG_CONSOLE', 1);

// for stdout debug, log format
// 1=html otherwise text
define('ZF_DEBUG_HTML', 0);

/*--------------------------*/

error_reporting(0);
ini_set('display_errors', 'Off');

//error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting (E_ALL ^ E_NOTICE);
if (ZF_DEBUG) {
	ini_set('display_errors', ZF_DEBUG_CONSOLE==0?'On':'Off');
	ini_set('html_errors', ZF_DEBUG_HTML==1?'On':'Off');
	error_reporting (E_ALL| E_STRICT);

	// preparation of performance monitoring
	global $zf_debugData;
	$zf_debugData['clock'][] = microtime();
	if (function_exists('getrusage')) {
		$dat = getrusage();
		$zf_debugData['utime_before'] = $dat["ru_utime.tv_sec"].$dat["ru_utime.tv_usec"];
		$zf_debugData['stime_before'] = $dat["ru_stime.tv_sec"].$dat["ru_stime.tv_usec"];
	}


}



setlocale(LC_ALL, ZF_LOCALE);
define('ZF_VER', '2.0');
define('ZF_USERAGENT',"ZebraFeeds/".ZF_VER." (http://www.cazalet.org/zebrafeeds)");

define("ZF_DATADIR", __DIR__.'/data');
define("ZF_OPMLFILE", __DIR__.'/zebrafeeds.opml');
define("ZF_TEMPLATESDIR", __DIR__.'/templates');
define("ZF_HISTORYDIR", ZF_DATADIR.'/history');

// full path
define("ZF_CACHEDIR", ZF_DATADIR.'/cache');

// only simplepie supported;
define("ZF_RSSPARSER", "simplepie");


//Defaults
function defaultConfig($name,$value) {

    if (!defined($name)) {
        define($name,$value);
    }
}

//default values for parameters with a UI
defaultConfig('ZF_LOGINTYPE', 'session');
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
defaultConfig('ZF_TEMPLATE', 'newsflow');
defaultConfig('ZF_HOMELIST', 'sample');
defaultConfig('ZF_SORT', 'date');
defaultConfig('ZF_TRIMTYPE', 'days');
defaultConfig('ZF_TRIMSIZE', 3);

require_once __DIR__.'/includes/controller.php';
require_once __DIR__.'/includes/classes.php';
require_once __DIR__.'/includes/aggregator.php';
require_once __DIR__.'/includes/feed_cache.php';
require_once __DIR__.'/includes/feed.php';
require_once __DIR__.'/includes/view.php';
require_once __DIR__.'/includes/subscriptionstorage.php';
require_once __DIR__.'/includes/itemtracker.php';
require_once __DIR__.'/includes/visittracker.php';
require_once __DIR__.'/includes/template.php';
require_once __DIR__.'/includes/common.php';
/*require_once __DIR__ . '/includes/SimplePie/SimplePieAutoLoader.php';
require_once __DIR__ . '/includes/SimplePie/SimplePie.php';*/
require_once __DIR__ . '/includes/simplepie.php';
require_once __DIR__ . '/includes/itemfilter.php';


