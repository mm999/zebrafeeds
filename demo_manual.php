<!DOCTYPE html>
<html>
<head>
<title>ZebraFeeds manual setup - Demo </title>
<?php 
define('ZF_RENDERMODE','manual' );

require ('newsfeeds/zebraheader.php'); ?>
</head>

<body>
<H1>ZebraFeeds manual setup</H1>
<?php
// MANUAL FEED CONFIGURATION //////////////////////////////////////////////////
//
// NOTE: this is to be used ONLY when the option "rendering mode" is "manual" 
// (in the administration panel :: config)
// whenever you disable that option (or it is not working) you can configure 
// the feeds in your page
//
// feeds are displayed in the order they are below
// follow the below format (all three fields are required) to add/modify/delete 
// RSS feeds
//
// first parameter is : the RSS url
// second parameter is : the number of news which should be displayed from this
//                       newsfeed
// the third parameter is : the refreshtime - number of minutes when feed is 
//                          re-read from it's url and stored in the cache dir
//
// examples:
//
// zf_addFeed("http://rss.com.com/2547-12-0-20.xml", 3, 100);
// zf_addFeed("http://slashdot.org/slashdot.rdf", 3, 120);
// zf_addFeed("http://www.wired.com/news_drop/netcenter/netcenter.rdf", 3, 240);
// zf_addFeed("http://newsforge.com/newsforge.rss", 3, 120);
// zf_addFeed("http://www.explodingcigar.com/backend.xml", 3, 1440);

// Alternately, one can also use a subscription list. See below
//
// END OF MANUAL FEED CONFIGURATION /////////////////////////////////////////////




echo "This demo has manual mode forced. Make sure you configure the manual/scheduled mode in the admin config page.<br/>";
echo "or define ZF_MODE to manual in newsfeeds/config.php";

require ('newsfeeds/zebrafeeds.php');


zf_addFeed("http://www.wired.com/news_drop/netcenter/netcenter.rdf", 3, 240);
zf_addFeed("http://daringfireball.net/index.xml", 3, 120);
zf_addFeed("http://www.explodingcigar.com/backend.xml", 3, 1440);

// customisations

// show all news sorted by date/time
zf_groupByDate();

// show only news from the last 4 days, sorted by date/time
//zf_trim(4, 'days');

// show only news from the last 8 hours, sorted by date/time
//zf_trim(8, 'hours');

// show only the last 15 news, sorted by date/time
//zf_trim(15, 'news');

// show only items which contain this word in title or description
// filter is independant of trim. trim occurs after filtering
// search is fulltext case insensitive
//zf_match("review");


/* 
	zf_groupByDate and zf_trim are configuration statements. They don't actually 
 do anything but setting variables,
 and only the last of whichever zf_groupByDate and zf_trim 
 is called is actually taken into account.
 */



// with zf_useTemplate, you can chose which template to use when rendering
// it has priority on what's configured on the admin page 

// but this trick works only if your template has an empty header section
// or no style sheet (that is, the template uses the embedding page styles)
// because this call is performed after the header is rendered, 
// at this time it's too late to change the styles
zf_useTemplate('modern');

zf_renderView();




// you can have several setups on the same page. Reset ZebraFeeds for another run

zf_reset();

zf_addFeed("http://www.slashdot.org/index.rss", 13, 240);
zf_addFeed("http://www.cazalet.org/feed", 6, 120);
zf_trim(15, 'news');
zf_renderView();

//then, you might want to disable the credits line, then add a line
//define('ZF_SHOWCREDITS', 'no');
//before including any of the ZebraFeeds scripts


/*-----------------------------------*/
/*This next run experiments with the user-defined news filtering*/

/* this is a sample filtering function
  it receives a reference to the the current item (an array)
  wrapped as first element of an an array 
 (trick to receive it by reference and being able to modifiy the item)

  the item array contains (among others) the following elements
  'title'
  'description'
  'pubdate' unix timestamp of the publishing time
  'isnew' true if the item is a new one
  'channel' an array containing channel data


  if when leaving the function, the item array contains an element
  named 'discarded' with value true, then the item will 
  _not_ be aggregated.
  dig into the code to learn more about the content of the item array
  template.php is probably a good place to start
 */
function myfilter(&$item) {

	/* in this example we discard news whose title is longer than
	 25 characters */
	/*
	if (strlen($item[0]['title']) > 25) {
		$item[0]['discarded'] = true;
	}
	*/

	/* here we keep only new news items */
	if (!$item[0]['isnew']) {
		$item[0]['discarded'] = true;
	}
}

zf_reset();

zf_useTemplate('css'); 
zf_addFeed("http://www.slashdot.org/index.rss", 13, 240);
zf_addFeed("http://www.cazalet.org/feed", 6, 120);
zf_setNewsFilterFunction('myfilter');

zf_renderView();

?>

</body>
</html>

