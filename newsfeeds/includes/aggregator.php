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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
//
// ZebraFeeds aggregator class

/* what does this class do?
this is the main facade class
- Configure the aggregation (template, source list, processing and rendering options
- aggregation and data preparation
- rendering of views

 */

if (!defined('ZF_VER')) exit;


class aggregator {

	private $cache;

	public function __construct() {
		$visitTracker = VisitTracker::getInstance();
		$visitTracker->checkIn();
		$end = $visitTracker->getLastSessionEnd();
		ItemTracker::getInstance()->setLastSessionEnd($end);

		$this->cache = FeedCache::getInstance();
	}


	public function getItem($channelId, $itemId) {
		return $this->cache->getItem($channelId, $itemId);
	}



	public function getFeedsForTag($tag, $aggregate, $trim, $onlyNew) {

		$subs = SubscriptionStorage::getInstance()->getActiveSubscriptions($tag);
		zf_debugRuntime("before feeds update");

		$this->cache->update($subs, 'auto');

		// create filters
		$chain = new FilterChain();
		$chain->addFilter(new MarkNewItemFilter());
		$feeds = $this->cache->getFeeds($subs, $chain);

		if ($aggregate) {
			$feeds = array(new AggregatedFeed($feeds, $this->makeFilterChain($trim, $onlyNew)));

		} else {
			$feeds = $this->processFeeds($feeds, $trim, $onlyNew);
		}
		zf_debugRuntime("after feeds update and aggr");
		zf_debug('returning '.sizeof($feeds).' feeds for tag '.$tag, DBG_AGGR);

		return $feeds;
	}

	public function getChannelFeed($channelId, $updateMode, $trim, $onlyNew) {

		$sub = SubscriptionStorage::getInstance()->getSubscription($channelId);

		$this->cache->update(array($sub->id => $sub), $updateMode);

		// create filters
		$chain = new FilterChain();
		$chain->addFilter(new MarkNewItemFilter());
		// TODO : filter by size
		$feeds = $this->cache->getFeeds(array($sub->id => $sub), $chain);

		$feeds = $this->processFeeds($feeds, $trim, $onlyNew);
		$feed = array_pop($feeds);

		return $feed;

	}


	/*
	feeds: array of feeds to handle
	trim: trim parameters on request
	onlyNew: if true, will keep only new items

	$result: feed or array of feeds, in function of aggregate
	 */
	private function processFeeds($feeds, $trim, $onlyNew) {

		$chain = $this->makeFilterChain($trim, $onlyNew);

		foreach($feeds as $feed) {
			// TODO get size filter from subscription setting
			// use the trim setting if trim is Xnews
			// filter news
			$feed->filter($chain);
			$feed->prepareRendering();
		}
		return $feeds;
	}

	private function makeFilterChain($trim, $onlyNew){
		$chain = new FilterChain();
		if ($onlyNew) {
			$chain->addFilter(new OnlyNewFilter());
		}

		if ($trim !== 'news') {
			$chain->addFilter(new AgeFilter($trim));
		}
		$chain->addFilter(new MarkNewItemFilter());

		return $chain;
	}


}


