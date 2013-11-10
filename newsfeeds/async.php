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
 - tag (always news by date)
 - subs (subs being tagged with specified tag, all if no tag specified)
 - tags (all tags available in subscriptions)

tag: use only subscription with this tag. default empty. applicable only for
    q=subs and q=tag

id: id of the channel (computed from the xmlurl)

itemid : the news item unique id for lookup

f: output type (json, html) default to json

mode: feed fetch mode. applicable only for q=channel
	- auto: let subscription decide, according to refresh time (default)
	- cache: force from cache
	- refresh: force refresh feed from source

sum: 1 summary included in news item header, 0 no summary (default)
     Applicable only when q=channel or q=tag

trim: how to shorten the number of items when getting tagged feeds, to get only news
	  or the last hour, since 4 days, or only new ones.
	  only when q=tag. Allowed values override default settings and are:
	          none, auto (default - use list settings), <N>days, <N>hours,
              <N>news, today, yesterday, onlynew

	  when q=channel allowed values are:
	           none (show all), auto (default, use subscription setting), <N>news
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
//$zf_aggregator->recordClientVisit();



/* === 2: find out query type and main parameters =====*/
if (isset($_GET['q'])) {
	$type = $_GET['q'];
} else {
	die('no query type defined');
}

$channelId = isset($_GET['id']) ? $_GET['id'] : -1;
$itemId = isset($_GET['itemid']) ? $_GET['itemid'] : -1;
$sum = isset($_GET['sum']) ? $_GET['sum'] : 0;
$tag = isset($_GET['tag']) ? $_GET['tag'] : '';

/* === 3: Process our request type and dispatch ==== */


switch ($type) {

	case 'item':
		//refresh: from cache always
		$zf_aggregator->printArticle($channelId, $itemId);
		break;


	case 'channel':
		//refresh: user defined
		$trim = isset($_GET['trim']) ? $_GET['trim'] : 'auto';
		if ($trim != 'auto') $zf_aggregator->setTrimString($trim);

		//$numItems = isset($_GET['max']) ? $_GET['max'] : 0;
		$mode = isset($_GET['mode']) ? $_GET['mode'] : 'auto';

		// channel with header and items, auto cache/refresh
		$zf_aggregator->printSingleFeed($channelId, $mode, $sum==1);
		break;

	case 'tag':
		//refresh: auto refresh always
		$trim = isset($_GET['trim']) ? $_GET['trim'] : 'auto';
		if ($trim!='auto') $zf_aggregator->setTrimString($trim);
		$zf_aggregator->printTaggedFeeds($tag);
		break;

	case 'summary':
		//refresh: from cache always
		$zf_aggregator->printSummary($channelId, $itemId);
		break;

	case 'subs':
		$sortedchannels = array();
		$subs = $zf_aggregator->storage->getActiveSubscriptions($tag);
		foreach( $subs as $i => $subscription) {
			if ($subscription->isActive) {
				$sortedchannels[$subscription->position] = $subscription;
				//Why this??? $subscription->opmlindex = $i;
			}
		}
		ksort($sortedchannels);
		echo json_encode($sortedchannels);
		break;

	case 'tags':

		$tags = $zf_aggregator->storage->getTags();
		echo json_encode($tags);
		break;
}


