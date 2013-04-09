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
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
//
// ZebraFeeds main


require_once('init.php');
require_once($zf_path . 'includes/aggregator.php');
require_once($zf_path . 'includes/controller.php');

if (strlen(ZF_URL) == 0) {
	echo "ZebraFeeds is not properly configured!<br/>";
	exit;
}

global $zf_aggregator;
if (!isset($zf_aggregator)) {
	$zf_aggregator = new aggregator();
}




/* AJAX+API requests section
use the global zf_page array only to tell if the template used is dynamic
parameters
type : request type
 - item: AJAX we want a news item
 - channelallitems: AJAX we want all the news items available for a channel
 - channelforcerefresh : AJAX we want a refreshed list of items of a channel
 - channel: AJAX we want channel head + showed items
 - article: API we want the single page article
 
xmlurl :
   URL of the newsfeed (XML - RSS/RDF/ATOM file)

itemid :
   html page element id. to be returned to the javascript side for update
   due to the asynchronous nature of AJAX (or my poor knowledge)
   if the item is a channel, this is also the MD5 of the feed XML URL
   if the item is a news, this is the MD5 of 'channel url + item link)


 */

if (isset($_GET['type']) && isset($_GET['xmlurl'])) {

	$type = $_GET['type'];
	// don't know why the + is turned back into a space, but this breaks
	// everything on complex URLs
	$xmlurl = str_replace(' ', '+', $_GET['xmlurl']);


	$itemid = isset($_GET['itemid']) ? $_GET['itemid'] : '';
	$outputid = isset($_GET['outputelementid']) ? $_GET['outputelementid'] : '';
	//set only if force refresh
	$maxitems = isset($_GET['maxitems']) ? $_GET['maxitems'] : '';
	$refreshtime = isset($_GET['refreshtime']) ? $_GET['refreshtime'] : '';

	// a data structure just as if extracted from an opml file
	$channeldata['xmlurl'] = $xmlurl;
	$channeldata['showeditems'] = $maxitems;
	$channeldata['refreshtime'] = $refreshtime;


	$zf_aggregator->useTemplate(new template(zf_getDisplayTemplateName()));

	/* if one of our AJAX requests: process and exit */
	if ($type == "item") {
		// force output encoding for AJAX request
		Header('Content-Type: text/html; charset='.ZF_ENCODING);

		echo $outputid."|,|,|";
		
		// if outputid is empty, display nothing, it comes probably from a "Read full story" call


		// last param is true because we don't want the history
		// to be checked against, we only want news content
	
		if ($zf_aggregator->loadFeed($channeldata, -1, true) ) {
			$zf_aggregator->printItemContent($itemid);
		}
		$zf_aggregator->displayErrors();
		exit;
	}

	if ($type == "channel") {

		// force output encoding for AJAX request
		Header('Content-Type: text/html; charset='.ZF_ENCODING);
		if ($zf_aggregator->loadFeed($channeldata, -1)) {
			// false, false: only showed items, title and items
			$zf_aggregator->viewSingleChannel(true, false);
		}
		$zf_aggregator->displayErrors();
		exit;
	}
	

	/* record a visit for operations that resend a list of news 
		do both server and client, one of them will possibly do something
	 */
  
	$zf_aggregator->recordServerVisit();
	$zf_aggregator->recordClientVisit();

	if ($type == "article") {
		//not an ajax call, just full article page

		if ($zf_aggregator->loadFeed($channeldata, -1, true) ) {
			$zf_aggregator->printItemContent($itemid);
		}
		$zf_aggregator->displayErrors();

		//no exit call to keep the rest of the calling script running
	}


	if ($type == "channelallitems") {
		// force output encoding for AJAX request
		Header('Content-Type: text/html; charset='.ZF_ENCODING);
		echo $outputid."|,|,|";
		$zf_aggregator->displayStatus('Showing all news');
		if ($zf_aggregator->loadFeed($channeldata, -1)) {
			// true, true: all items, only items (no title)
			$zf_aggregator->viewSingleChannel(true, true);
		}
		$zf_aggregator->displayErrors();

		exit;
	}


	if ($type == "channelforcerefresh") {
		// force output encoding for AJAX request
		Header('Content-Type: text/html; charset='.ZF_ENCODING);
		echo $outputid."|,|,|";
		$zf_aggregator->displayStatus('Showing refreshed news');
		// false: dont show all
		// true: we only want the items
		if ($zf_aggregator->loadFeed($channeldata, 0)) {
			$zf_aggregator->viewSingleChannel(false, true);
		}
		$zf_aggregator->displayErrors();
		exit;
	}
	
	
	/*
	 * returns a JSON object containing 
	 * all the items of the chosen channel
	 */
	if ($type == "jsonchannelallitems") {
		header('Content-Type: application/json; charset='.ZF_ENCODING);
		$id = zf_makeId($xmlurl, "");
		if ($zf_aggregator->loadFeed($channeldata, -1)) {
			echo json_encode($zf_aggregator->getFeedItems());
		}
		exit;
	}
	
	
	/*
	 * simply shows the content of an article
	 */
	if ($type == "jsonitem") {
		header('Content-Type: text/html; charset='.ZF_ENCODING);
		if ($zf_aggregator->loadFeed($channeldata, -1, true) ) {
			$zf_aggregator->printItemContent($itemid);
		}
		$zf_aggregator->displayErrors();
		exit;
	}
	
} elseif ( isset( $_GET['type'] ) ) {
    $type = $_GET['type'];
    
    
    /**
     * returns all categories and their channels
     * as a JSON object indexed by categories names
     */
    if ($type == 'listswithchannels') {
        header('Content-Type: application/json; charset='.ZF_ENCODING);
        $catlist = zf_getListNames();
        $cats = Array();
    	foreach ($catlist as $categf) {
            $list = new opml($categf);
            if ($list->load()) {
                $sortedchannels = array();
            	foreach($list->channels as $i => $channel) {
            		if ($channel['xmlurl'] != '' && $channel['issubscribed'] == 'yes') {
            			$sortedchannels[$channel['position']] = $channel;
            			$sortedchannels[$channel['position']]['opmlindex'] = $i;
            		}
            	}
                ksort($sortedchannels);
                $cats[$categf] = $sortedchannels;
            }
    	}
    	echo json_encode($cats);
    	exit;
    }
} else {

	
	zf_debug("Aggregator loaded");
	
	// record the time of the visit, server side mode
	$zf_aggregator->recordServerVisit();
	
	
	
	if (ZF_RENDERMODE == 'automatic') {
		zf_debug("Using automatic mode");
		$zf_list = zf_getCurrentListName();
		zf_useList($zf_list);
		zf_renderView();
	
	} else {
		// ZF_MODE set to manual: output will be configured by whoever is integrating 
		//the script on their site
		zf_debug("Using manual mode");
	}

}
?>
