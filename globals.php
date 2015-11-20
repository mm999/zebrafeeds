<?php

/* initialization for both admin and user areas */

// hidden settings, no GUI for these
define('ZF_DEFAULT_NEWS_COUNT', 3);
define('ZF_DEFAULT_REFRESH_TIME', 120);
define('ZF_SESSION_DURATION', 900); // 15 minutes before unmarking items as new
define('ZF_GROUP_BY_DAY', 'yes'); // if yes, items are grouped by day when multiple channels sorted by date
define('ZF_MAX_SUMMARY_LENGTH', 600); // if description is longer (tags-stripped), let's truncate to make the summary
define('ZF_SUMMARY_TRUNCATED_LENGTH', 300); // if truncate to summary, here's the remaining length
define('ZF_SHOWCREDITS', 'no');
// ideally, this should be defined in the template
//define('ZF_ISNEW_STRING', '<img src="'.ZF_URL.'res/img/new.png" border="0" title="is new since last visit" alt="New"/>');
define('ZF_ISNEW_STRING', 'newclass');
define('ZF_DEFAULT_ADMIN_VIEW', 'subscriptions');
define('ZF_MAXFEEDITEMS', '25'); // maximum number of feed items to keep in cache


/*--------------------------*/
/* bit debug values for ZF_DEBUG */
define('DBG_LIST', 2); // list handling & management
define('DBG_AGGR', 4); // feed aggregation/merging
define('DBG_RUNTIME', 8); // trace runtime
define('DBG_OPML', 16); // all subscription management
define('DBG_FEED', 64); // handling/fetching feeds
define('DBG_RENDER', 128); // view and template rendering
define('DBG_DB', 256); // database layer
define('DBG_ALL', 0xFFFFFFFFF); // very verbose

// use DBG_xxx | DBG_yyy | ... to select what to see in the logs
define('ZF_DEBUG', 0);

// debug output 1=console otherwise stdout
define('ZF_DEBUG_CONSOLE', 0);

// for stdout debug, log format
// 1=html otherwise text
define('ZF_DEBUG_HTML', ZF_DEBUG_CONSOLE==1?0:1);


/* dirs */

define("ZF_DATADIR", INITDIR.'/data');
define("ZF_OPMLFILE", ZF_DATADIR.'/zebrafeeds.opml');
define("ZF_TEMPLATESDIR", INITDIR.'/templates');
define("ZF_HISTORYDIR", ZF_DATADIR.'/history');
define("ZF_CACHEDIR", ZF_DATADIR.'/cache');

define ('SSL_PORT', 443);
define('ZF_VER', '2.0');
define('ZF_USERAGENT',"ZebraFeeds/".ZF_VER." (http://www.cazalet.org/zebrafeeds)");


