<?php
// ZebraFeeds - copyright (c) 2006-2013 Laurent Cazalet
// http://www.cazalet.org/zebrafeeds
//

define('INITDIR', dirname(__FILE__));
define("ZF_CONFIGFILE", INITDIR.'/config.php');
include_once ZF_CONFIGFILE;



/* initialization for both admin and user areas */

// hidden settings, no GUI for these
define('ZF_DEFAULT_NEWS_COUNT', 3);
define('ZF_DEFAULT_REFRESH_TIME', 120);
define('ZF_SESSION_DURATION', 900); // 15 minutes before unmarking items as new
define('ZF_GROUP_BY_DAY', 'yes'); // if yes, items are grouped by day when multiple channels sorted by date
define('ZF_MAX_SUMMARY_LENGTH', 300); // if description is longer (tags-stripped), let's truncate to make the summary
define('ZF_SUMMARY_TRUNCATED_LENGTH', 300); // if truncate to summary, here's the remaining length
define('ZF_SHOWCREDITS', 'no');
// ideally, this should be defined in the template
//define('ZF_ISNEW_STRING', '<img src="'.ZF_URL.'res/img/new.png" border="0" title="is new since last visit" alt="New"/>');
define('ZF_ISNEW_STRING', 'newclass');

/* bit debug values for ZF_DEBUG
*/
define('DBG_LIST', 2); // list handling & management
define('DBG_AGGR', 4); // feed aggregation/merging
define('DBG_RUNTIME', 8); // trace runtime
define('DBG_OPML', 16); // all subscription management
define('DBG_SESSION', 32); // history, session, cookies
define('DBG_FEED', 64); // handling/fetching feeds
define('DBG_RENDER', 128); // view and template rendering
define('DBG_FILTER', 256); // item filtering
define('DBG_ALL', 0xFFFFFFFFF); // very verbose

// use DBG_xxx | DBG_yyy | ... to select what to see in the logs
define('ZF_DEBUG', DBG_ALL );

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



define ('SSL_PORT', 443);
require_once INITDIR.'/includes/common.php';

setlocale(LC_ALL, ZF_LOCALE);
define('ZF_VER', '2.0');
define('ZF_USERAGENT',"ZebraFeeds/".ZF_VER." (http://www.cazalet.org/zebrafeeds)");

define("ZF_HOMEURL",getZfUrl()); // URL to your web page, were feeds are included;

//TODO: instead of saving ZF_URL in config, save ZF_INSTALLFOLDER detected at installation and use it to reconstruct ZF_URL
define("ZF_URLTEST",ZF_HOMEURL.ZF_INSTALLFOLDER.'/'); // URL to ZebraFeeds directory installation;

//echo ZF_HOMEURL.' ---- '. ZF_URLTEST;

define("ZF_DATADIR", INITDIR.'/data');
define("ZF_OPMLFILE", ZF_DATADIR.'/zebrafeeds.opml');
define("ZF_TEMPLATESDIR", INITDIR.'/templates');
define("ZF_HISTORYDIR", ZF_DATADIR.'/history');

// full path
define("ZF_CACHEDIR", ZF_DATADIR.'/cache');
// only simplepie supported;
define("ZF_RSSPARSER", "simplepie");

ini_set("user_agent",ZF_USERAGENT);

//Defaults
function defaultConfig($name,$value) {

    if (!defined($name)) {
        define($name,$value);
    }
}

//default values for parameters with a UI
defaultConfig('ZF_LOGINTYPE', 'session');
defaultConfig('ZF_LOCALE', 'english');
defaultConfig('ZF_PUBDATEFORMAT', '%x, %X');
defaultConfig('ZF_DATEFORMAT', '%x');
defaultConfig('ZF_REFRESHMODE','automatic');
defaultConfig('ZF_NOFUTURE','no');
defaultConfig('ZF_ENCODING', 'UTF-8');
defaultConfig('ZF_DISPLAYERROR', 'no');
defaultConfig('ZF_TEMPLATE', 'flow2');
defaultConfig('ZF_HOMETAG', '');
defaultConfig('ZF_SORT', 'date');
defaultConfig('ZF_TRIMTYPE', 'days');
defaultConfig('ZF_TRIMSIZE', 3);

require_once INITDIR.'/includes/controller.php';
require_once INITDIR.'/includes/classes.php';
require_once INITDIR.'/includes/aggregator.php';
require_once INITDIR.'/includes/feed_cache.php';
require_once INITDIR.'/includes/feed.php';
require_once INITDIR.'/includes/view.php';
require_once INITDIR.'/includes/subscriptionstorage.php';
require_once INITDIR.'/includes/itemtracker.php';
require_once INITDIR.'/includes/visittracker.php';
require_once INITDIR.'/includes/template.php';
require_once INITDIR.'/includes/simplepie_fetch.php';
require_once INITDIR.'/includes/itemfilter.php';

/*require_once INITDIR . '/lib/SimplePie/SimplePieAutoLoader.php';
require_once INITDIR . '/lib/SimplePie/SimplePie.php';*/
require_once INITDIR . '/lib/simplepie.php';
require_once INITDIR . '/lib/humble-http-agent/HumbleHttpAgent.php';
require_once INITDIR . '/lib/humble-http-agent/RollingCurl.php';
require_once INITDIR . '/lib/humble-http-agent/CookieJar.php';
require_once INITDIR . '/lib/readability/readability.php';

