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

$zf_aggregator = new Aggregator();




/* Asynchronous requests section
use the global zf_page array only to tell if the template used is dynamic

parameters dictionary
======================
q : query type. Values:
 - item: a single new item, with article view
 - summary
 - channel: we want channel news, sorted by date
 - list (always by date)
 - subs (for one single list)
 - allsubs

zflist: name of the OPML list to lookup channel in.

id: id of the channel (computed from the xmlurl)

itemid : the news item unique id for lookup

f: output type (json, html) default to json

max: max number of items to show for a channel.
     Applicable only when q=channel
     0: auto (default)
     -1: all

mode: feed fetch mode. applicable only for type=channel
	- auto: let subscription decide, according to refresh time (default)
	- cache: force from cache
	- refresh: force refresh feed from source

sum: 1 summary included in news item header, 0 no summary (default)
     Applicable only when q=channel or q=list

trim: how to shorten the number of items when getting a list, to get only news
	  or the last hour, since 4 days, or only new ones.
	  only when q=list. Allowed values are:
	           none (default), <N>days, <N>hours,
              <N>news, today, yesterday, onlynew

	  when q=channel allowed values are:
	           none (show all), auto (default), <N>news
 */

/* record a visit for operations that resend a list of news
	do both server and client, one of them will possibly do something
 */

/* === 1: define output type =====*/
/* output type: JSON or HTML or (TODO) RSS */
$f = isset($_GET['f'])?$_GET['f']:'json';

if ($f =='html') {
	$zf_aggregator->useTemplate(zf_getDisplayTemplateName());
	header('Content-Type: text/html; charset='.ZF_ENCODING);
} else {
	$zf_aggregator->useJSON();
	header('Content-Type: application/json; charset='.ZF_ENCODING);
}

$zf_aggregator->recordServerVisit();
$zf_aggregator->recordClientVisit();



/* === 2: find out query type and main parameters =====*/
if (isset($_GET['q'])) {
	$type = $_GET['q'];
} else {
	die('no query type defined');
}

$channelId = isset($_GET['id']) ? $_GET['id'] : -1;
$itemid = isset($_GET['itemid']) ? $_GET['itemid'] : -1;
$sum = isset($_GET['sum']) ? $_GET['sum'] : 0;


/* === 3: are we dealing with a list? load it ===== */
// this will go away when we only have one single OPML list
// then we'll get the list name explicitely
$zf_list = zf_getCurrentListName();
if (strlen($zf_list)>0) $zf_aggregator->useList($zf_list);

/* === 4: Process our request type and dispatch ==== */

switch ($type) {

	case 'item':
		$zf_aggregator->printArticle($channelId, $itemid);
		break;


	case 'channel':
		$trim = isset($_GET['trim']) ? $_GET['trim'] : 'auto';
		if ($trim != 'none') $zf_aggregator->setTrimString($trim);

		//$numItems = isset($_GET['max']) ? $_GET['max'] : 0;
		$mode = isset($_GET['mode']) ? $_GET['mode'] : 'auto';

		// channel with header and items, auto cache/refresh
		$zf_aggregator->printSingleChannelById($channelId, $mode, $sum==1);
		break;

	case 'list':
		$trim = isset($_GET['trim']) ? $_GET['trim'] : 'none';
		if ($trim!='none') $zf_aggregator->setTrimString($trim);
		$zf_aggregator->printListByDate();
		break;

	case 'summary':
		$zf_aggregator->printSummary($channelId, $itemid);
		break;

	case 'subs':

	case 'allsubs':

		$catlist = $zf_aggregator->getListNames();
		$cats = Array();
		foreach ($catlist as $categf) {
			$list = new opml($categf);
			if ($list->load()) {
				$sortedchannels = array();
				foreach($list->subscriptions as $i => $subscription) {
					if ($subscription->isSubscribed) {
						$sortedchannels[$subscription->position] = $subscription;
						$subscription->opmlindex = $i;
					}
				}
				ksort($sortedchannels);
				$cats[$categf] = $sortedchannels;
			}
		}
		echo json_encode($cats);
		break;
}


?>
