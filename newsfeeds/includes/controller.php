<?php
// ZebraFeeds - copyright (c) 2006 Laurent Cazalet
// http://www.cazalet.org/zebrafeeds
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
//
// ZebraFeeds user functions.
// For the case where ZF_USEOPML is set to no.
// allow to manually configure the output

if (!defined('ZF_VER')) exit;

require_once($zf_path . 'includes/aggregator.php');
require_once($zf_path . 'includes/feed_cache.php');
require_once($zf_path . 'includes/classes.php');
require_once($zf_path . 'includes/subscriptionstorage.php');
require_once($zf_path . 'includes/view.php');
require_once($zf_path . 'includes/itemtracker.php');
require_once($zf_path . 'includes/visittracker.php');


/*
parameters dictionary
======================
q : query type. Values:
 - item: a single new item, with article view
 - channel: we want channel news, sorted by date
 - tag (always news by date)
 - subs (subs being tagged with specified tag, all if no tag specified)
 - tags (all tags available in subscriptions)
 - refresh: force refresh a feed

tag: use only subscription with this tag. default empty. applicable only for
	q=subs and q=tag

id: id of the channel to deal with

itemid : the news item unique id for lookup

f: output type (json, html) default to html

tags: list of existing tags. JSON output only

subs: list of subscriptions for tag. JSON output only

mode: feed update mode. applicable only for q=channel
	- auto: let subscription decide, according to refresh time (default)
	- none: force from cache
	- force: force refresh feed from source

sum: if 1 then summary included in news item header, 0 no summary (default)
	 Applicable only when q=channel or q=tag

trim: how to shorten the number of items when getting feeds with tag, to get only news
	  or the last hour, since 4 days...
	  when q=tag. Allowed values override default settings and are:
			  <N>days, <N>hours, <N>news,
	  when q=channel allowed values are:
			   none (show all), auto (default, use subscription setting), <N>news

onlynew: default to 0. If 1 will show only newly fetched items. Valid for q=tag or channel

sort: feed or date. only for q=tag
      if trim is set, ignored and forced to date
      only valid for html output

decoration: for q=channel and f=html only
            default to 0. if 1, will output channel header
 */




/* main routing function */
function handleRequest() {

	global $zf_aggregator;

	$type = param('q', 'tag');
	$channelId = param('id');
	$itemId = param('itemid');
	$sum = int_param('sum');
	$tag = param('tag','');
	$trim = param('trim', 'auto');
	$outputType = param('f','html');
	$template = param('zftemplate', ZF_TEMPLATE);
	$onlyNew = int_param('onlynew',0);
	$sort = param('sort', ZF_VIEWMODE);

	//refresh mode
	$updateMode = param('mode', 'auto');

	$zf_aggregator = new Aggregator();
	zf_debug("Aggregator loaded");

	// record the time of the visit, server side mode. WHY???
	VisitTracker::getInstance()->recordVisit();

	if ($outputType =='html') {
		$contenttype = 'text/html';
		$view = new TemplateView($template);
	} else {
		$contenttype = 'application/json';
		$view = new JSONView();
	}
	if (!headers_sent()) header('Content-Type: '.$contenttype.'; charset='.ZF_ENCODING);

	$storage = SubscriptionStorage::getInstance();
	$cache = FeedCache::getInstance();

	switch ($type) {

		case 'item':
			//refresh: always from cache
			$item = $cache->getItem($channelId, $itemId);
			$view->renderArticle($item);
			break;

		case 'summary':
			//refresh: always from cache
			$item = $cache->getItem($channelId, $itemId);
			$view->renderSummary($item);
			break;



		case 'channel':
			//refresh: user defined
			$sub = $storage->getSubscription($channelId);
			$feeds = $cache->update(array($sub->id => $sub), $updateMode);

			$feeds = $zf_aggregator->processFeeds($feeds, $trim, false, $onlyNew);
			$feed = array_pop($feeds);

			// could become true if we wanted date grouping for every channel
			// will only be useful for TemplateView
			$view->renderFeed($feed, array(
				'groupbyday' => false,
				'decoration' => int_param('decoration'),
				'summary' => ($sum==1)));
			break;



		case 'tag':
			//refresh: always auto refresh for tag view

			$subs = $storage->getActiveSubscriptions($tag);
			zf_debugRuntime("before feeds update");
			$feeds = $cache->update($subs, 'auto');

			zf_debugRuntime("before aggregation");

			// if html output & sorted by feed, trim every single item
			// according to subcription's settings
			if ($sort == 'feed' && $outputType == 'html') {

				$feeds = $zf_aggregator->processFeeds($feeds, $trim, false, $onlyNew);
				$groupbyday = false;

			} else {
				// otherwise just aggregate in a single feed
				$feed = $zf_aggregator->processFeeds($feeds, $trim, true, $onlyNew);
				$feeds = array($feed);
				$groupbyday = true;
			}

			zf_debugRuntime("before rendering");

			$view->renderFeedList($feeds, array(
				'groupbyday' => $groupbyday,
				'summary' => ($sum==1),
				'tag' => $tag));
			break;



		case 'subs':
			//only JSON
			$subs = SubscriptionStorage::getInstance()->getSortedActiveSubscriptions($tag);
			echo json_encode($subs);
			break;


		case 'tags':

			$tags = $storage->getTags();
			echo json_encode($tags);
			break;


		case 'refresh':
			// TODO: check API key
			$sub = $storage->getSubscription($channelId);
			$feeds = $cache->update(array($sub->id => $sub), 'force');
			if (array_pop($feeds) != null) echo $sub->title. ' DONE. ';
			break;


	}

}


