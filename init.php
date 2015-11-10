<?php
// ZebraFeeds - copyright (c) 2006-2013 Laurent Cazalet
// http://www.cazalet.org/zebrafeeds
//

define('INITDIR', dirname(__FILE__));
include_once INITDIR.'/globals.php';
define("ZF_CONFIGFILE", INITDIR.'/config.php');
include_once ZF_CONFIGFILE;



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

} else {
	error_reporting(0);
	ini_set('display_errors', 'Off');
}



require_once INITDIR.'/includes/common.php';

setlocale(LC_ALL, ZF_LOCALE);
define("ZF_HOMEURL",getZfUrl()); // URL to your web page, were feeds are included;
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
require_once INITDIR.'/includes/sourceproxy.php';
require_once INITDIR.'/includes/aggregator.php';
require_once INITDIR.'/includes/feed_cache.php';
require_once INITDIR.'/includes/feed.php';
require_once INITDIR.'/includes/view.php';
require_once INITDIR.'/includes/subscriptionstorage.php';
require_once INITDIR.'/includes/itemtracker.php';
require_once INITDIR.'/includes/visittracker.php';
require_once INITDIR.'/includes/template.php';
require_once INITDIR.'/includes/itemfilter.php';

require_once INITDIR . '/lib/simplepie/autoloader.php';
require_once INITDIR . '/lib/humble-http-agent/HumbleHttpAgent.php';
require_once INITDIR . '/lib/humble-http-agent/RollingCurl.php';
require_once INITDIR . '/lib/humble-http-agent/CookieJar.php';
require_once INITDIR . '/lib/readability/Readability.php';


