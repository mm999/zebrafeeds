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
	$zf_aggregator = new Aggregator();
}




/* Asynchronous requests section
use the global zf_page array only to tell if the template used is dynamic
parameters
type : request type
 - item: a single new item, with article view
 - channelallitems: we want all the news items available for a channel
 - channelforcerefresh : we want a refreshed list of items of a channel
 - channel: we want channel header + showed items

zflist:
   name of the OPML list to lookup channel in.

pos: position of the channel in the list

itemid : the news item unique id for lookup


 */

/* record a visit for operations that resend a list of news
	do both server and client, one of them will possibly do something
 */


/* output type: JSON or HTML or (TODO) RSS */
$f = isset($_GET['f'])?$_GET['f']:'html';

if ($f =='json') {
	$zf_aggregator->useJSON();
	header('Content-Type: application/json; charset='.ZF_ENCODING);
} else {
	$zf_aggregator->useTemplate(zf_getDisplayTemplateName());
	header('Content-Type: text/html; charset='.ZF_ENCODING);
}

$zf_aggregator->recordServerVisit();
$zf_aggregator->recordClientVisit();


/* type of content:
	channel, item, channelforcerefresh, channelallitems,
	listwithchannels
*/
if (isset($_GET['type'])) {
	$type = $_GET['type'];
} else {
	die('no query defined');
}


if (isset($_GET['pos'])) {


	/* position-id of the channel in the OPML list */
	$pos = $_GET['pos'];


	$itemid = isset($_GET['itemid']) ? $_GET['itemid'] : '';
	// a data structure just as if extracted from an opml file


	$zf_list = zf_getCurrentListName();
	$zf_aggregator->useList($zf_list);

	// force output encoding for AJAX request

	if ($type == "item") {
		$zf_aggregator->printArticle($pos, $itemid);
	}


	if ($type == "channel") {

		// channel with header and items, auto cache/refresh
		$zf_aggregator->printSingleChannel($pos);

	}

	if ($type == "channelallitems") {
		$zf_aggregator->printStatus('Showing all news');
		$zf_aggregator->printAllCachedItems($pos);

	}

	if ($type == "channelforcerefresh") {
		$zf_aggregator->printStatus('Showing refreshed news');
		$zf_aggregator->printRefreshedItems($pos);
	}

	$zf_aggregator->printErrors();
	exit;
}

if ($type == 'listswithchannels') {

	/**
	 * TODO
	 * returns all categories and their channels
	 * as a JSON object indexed by categories names
	 */
	$catlist = zf_aggregagor->getListNames();
	$cats = Array();
	foreach ($catlist as $categf) {
		$list = new opml($categf);
		if ($list->load()) {
			$sortedchannels = array();
			foreach($list->subscriptions as $i => $subscription) {
				if ($subscription->isSubscribed) {
					$sortedchannels[$subscription->position] = $subscription;
					$sortedchannels[$subscription->position]['opmlindex'] = $i;
				}
			}
			ksort($sortedchannels);
			$cats[$categf] = $sortedchannels;
		}
	}
	echo json_encode($cats);
	exit;
}


?>
