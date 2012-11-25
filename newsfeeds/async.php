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

if (isset($_GET['type']) && isset($_GET['xmlurl'])) {

	$type = $_GET['type'];
	// don't know why the + is turned back into a space, but this breaks
	// everything on complex URLs


	$itemid = isset($_GET['itemid']) ? $_GET['itemid'] : '';
	// a data structure just as if extracted from an opml file


	$zf_aggregator->useTemplate(new template(zf_getDisplayTemplateName()));

	$zf_list = zf_getCurrentListName();
	$zf_aggregator->useList($zf_list);

	// force output encoding for AJAX request
	Header('Content-Type: text/html; charset='.ZF_ENCODING);

	if ($type == "item") {
		$zf_aggregator->printArticleFromCache($pos, $itemid);
		exit;
	}

// to do one day: list of channels for a list, list of subscription lists...


	/* record a visit for operations that resend a list of news
		do both server and client, one of them will possibly do something
	 */

	$zf_aggregator->recordServerVisit();
	$zf_aggregator->recordClientVisit();

	if ($type == "channel") {

		// channel with header and items, auto cache/refresh
		$zf_aggregator->printSingleChannel($pos);

	} else if ($type == "channelallitems") {

		$zf_aggregator->displayStatus('Showing all news');
		$zf_aggregator->printAllItemsFromCache($pos);
		}

	} else if ($type == "channelforcerefresh") {

		$zf_aggregator->displayStatus('Showing refreshed news');
		$zf_aggregator->printDefaultItemsRefreshed($pos);
	}

	$zf_aggregator->printErrors();

}

?>
