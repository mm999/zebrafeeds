<?php
// ZebraFeeds 1.3_DEV_20111022 - copyright (c) 2006 Laurent Cazalet
// configuration file


define("ZF_CONFIGVERSION","1.3_DEV_201101022");
// general configuration options //

define("ZF_LOGINTYPE","server"); // server - server HTTP auth; session - PHP sessions auth
define("ZF_HOMEURL","http://ssss.ss"); // URL to your web page, were feeds are included; 
define("ZF_URL","http://ssss.ss/newsfeeds"); // URL to ZebraFeeds directory installation; 
define("ZF_ADMINNAME","admin"); // admin username
define("ZF_ADMINPASS",""); // crypted admin password, default is "admin" (without quotes). Leave empty to reset.


// feeds options //

define("ZF_USEOPML","yes"); // if yes the subscription file will be used, else the manual feed configuration
define("ZF_HOMELIST","1-Home"); // name of the default feed list in the subscriptions directory which holds the subscriptions data
define("ZF_REFRESHMODE","automatic"); // automatic: feeds are refreshed when page is generated. request: use a refresh link. see admin page for details


// general display options //

define("ZF_TEMPLATE","newsflow"); // the default templates used to display the news (subdirectory name from templates directory)
define("ZF_DISPLAYERROR","yes"); // if yes then when a feed cannot be read (or has errors) formatted error message shows in {description}


// localization options //

define("ZF_ENCODING","UTF-8"); // character encoding for output
define("ZF_LOCALE","french"); // language for dates, system messages
define("ZF_PUBDATEFORMAT","%H:%M"); // format passed to strftime to convert dates got from RSS feeds
define("ZF_DATEFORMAT","%e %B"); // format passed to strftime to display date when displaying news grouped by date


// advanced options //

define("ZF_NEWITEMS","server"); //No: doesn't mark new items, client: marks new items for each visitor (cookie based). server: marks new items since last refresh (common to all visitors)
define("ZF_RENDERMODE","automatic"); // automatic: always display aggregated feeds when zebrafeeds.php is included. manual: user the manual integration in scripts with ZebraFeeds user functions
define("ZF_NOFUTURE","no"); // if yes then does not show news with a timestamp from the future
define("ZF_OWNERNAME","My portal"); // owner name which will appear in the OPML file (optional)
define("ZF_OWNEREMAIL",""); // owner email which will appear in the OPML file (optional)


//////END OF CONFIGURATION///////////////////////////////////////////////////



?>