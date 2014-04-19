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

 */


/* 4 functions borrowed from PicoFarad - by F. Guillot (author of Miniflux) */
function param($name, $default_value = null)
{
	return isset($_GET[$name]) ? $_GET[$name] : $default_value;
}


function int_param($name, $default_value = 0)
{
	return isset($_GET[$name]) && ctype_digit($_GET[$name]) ? (int) $_GET[$name] : $default_value;
}


function value($name)
{
	$values = values();
	return isset($values[$name]) ? $values[$name] : null;
}


function values()
{
	if (! empty($_POST)) {

		return $_POST;
	}

	$result = json_decode(body(), true);

	if ($result) {
		return $result;
	}

	return array();
}

function content_type($mimetype)
{
	header('Content-Type: '.$mimetype);
}


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

	// TODO: view mode for tag request : by feed or by date
	// trim = auto -> use default config
	// trim = sth else -> always by date, trimmed


	$zf_aggregator = new Aggregator();
	zf_debug("Aggregator loaded");

	// record the time of the visit, server side mode. WHY???
	$zf_aggregator->recordServerVisit();

	if ($outputType =='html') {
		//$zf_aggregator->useTemplate($template);
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
			//refresh: always from cache for newsitems
			// can be done in one step if getItem moved to FeedCache
			$feed = $cache->get($channelId);
			$item = $feed->getItem($itemId);
			$view->renderArticle($item);
			break;

		case 'summary':
			//refresh: always from cache for news items

			// can be done in one step if moved to FeedCache
			$feed = $cache->get($channelId);
			$item = $feed->getItem($itemId);
			$view->renderSummary($item);
			break;


		case 'channel':
			//refresh: user defined

			$sub = $storage->getSubscription($channelId);
			$feeds = $cache->update(array($sub->id => $sub), $updateMode );
			$feed = array_pop($feeds);

			if ($trim == 'auto') {
				$feedParams = $sub->getFeedParams();
			} else {
				$feedParams = new FeedParams();
			}
			$feedParams->onlyNew = $onlyNew;
			$feed->postProcess($feedParams);

			// could become true if we wanted date grouping for every channel
			// will only be useful for TemplateView
			$view->renderFeed($feed, array(
				'groupbyday' => false, 
				'summary' => ($sum==1)));
			break;

		case 'tag':
			//refresh: always auto refresh for tag view

			$subs = $storage->getActiveSubscriptions($tag);
			zf_debugRuntime("before feeds update");
			$feeds = $cache->update($subs, 'auto');

			zf_debugRuntime("before aggregation");
			/* TODO: + mergeoptions*/ 
			zf_debugRuntime("before rendering");
			if ($sort == 'feed' && $outputType == 'html') {

				foreach($feeds as $feed) {
					if ($trim == 'auto') {
						$feedParams = $storage->getSubscription($feed->subscriptionId)->getFeedParams();
					} else {
						$feedParams = new FeedParams();
					}
					$feedParams->onlyNew = $onlyNew;
					$feed->postProcess($feedParams);
				}

				$view->renderFeedlist($feeds, array( 
					'groupbyday' => false,
					'summary' => ($sum==1), 
					'tag' => $tag));

			} else { 
				$feedParams = new FeedParams();
				if ($trim != 'auto') {
					$feedParams->setTrimStr($trim);
				}
				$feedParams->onlyNew = $onlyNew;
				$aggrfeed = new AggregatedFeed($feeds, $feedParams);
				$view->renderFeed($aggrfeed, array(
					'groupbyday' => true, 
					'summary' => ($sum==1), 
					'tag' => $tag));
			}

			break;

		case 'subs':
			//only JSON
			$sortedchannels = array();
			$subs = SubscriptionStorage::getInstance()->getActiveSubscriptions($tag);
			foreach( $subs as $i => $subscription) {
				$sortedchannels[$subscription->position] = $subscription;
			}
			ksort($sortedchannels);
			echo json_encode($sortedchannels);
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


