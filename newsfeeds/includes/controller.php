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
	
	//refresh mode
	$mode = param('mode', 'auto');

	// TODO: view mode for tag request : by feed or by date
	// trim = auto -> use default config
	// trim = sth else -> always by date, trimmed


	$zf_aggregator = new Aggregator();
	zf_debug("Aggregator loaded");

	// record the time of the visit, server side mode. WHY???
	$zf_aggregator->recordServerVisit();

	if ($outputType =='html') {
		//$zf_aggregator->useTemplate($template);
		@header('Content-Type: text/html; charset='.ZF_ENCODING);
		$view = new TemplateView($template);
	} else {
		header('Content-Type: application/json; charset='.ZF_ENCODING);
		$view = new JSONView();
	}

	$storage = SubscriptionStorage::getInstance();
	$cache = FeedCache::getInstance();

	switch ($type) {

		case 'item':
			//refresh: from cache always
			// can be done in one step if moved to FeedCache
			$feed = $cache->get($channelId);
			$item = $feed->getItem($itemId);
			$view->renderArticle($item);
			break;

		case 'summary':
			//refresh: from cache always

			// can be done in one step if moved to FeedCache
			$feed = $cache->get($channelId);
			$item = $feed->getItem($itemId);
			$view->renderSummary($item);
			break;


		case 'channel':
			//refresh: user defined
			if ($trim != 'auto') $zf_aggregator->setTrimString($trim);

			$sub = $storage->getSubscription($channelId);
			$feeds = $cache->update(array($sub->id => $sub) );
			$feed = array_pop($feeds);

			// could become true if we wanted date grouping for every channel
			// will only be useful for TemplateView
			// TODO use ZF_VIEWMODE;
			$view->groupByDay = false;
			//render with no channel header if requested and applicable
			$view->summaryInFeed = ($sum==1);
			$view->renderFeed($feed);
			break;

		case 'tag':
			//refresh: auto refresh always
			//if ($trim!='auto') $zf_aggregator->setTrimString($trim);

			$subs = $storage->getActiveSubscriptions($tag);
			zf_debugRuntime("before feeds update");
			$feeds = $cache->update($subs);

			zf_debugRuntime("before aggregation");
			/* TODO: + mergeoptions*/ 
			$aggrfeed = new AggregatedFeed($feeds);

			$view->addTags(array( 'tag' => $tag));
			$view->groupByDay = true;
			zf_debugRuntime("before rendering");
			$view->renderFeed($aggrfeed);

/* TODO= implement mode of cache update according to request
		switch ($mode) {
			case 'auto':
				$cache->update(array($sub));
				break;
			case 'cache':
				break;
			case 'refresh':
				$cache->update(array($sub), true);
				break;
		} */

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
			$cache->update($channelId, true);
			break;

	}



}


