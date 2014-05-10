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

/* class in charge of providing models
- feeds
- items
- summaries

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

		$feeds = $this->cache->getFeeds($subs);

		if ($aggregate) {
			// aggregate all feeds in one: use global trim setting if auto is set
			if ($trim == 'auto') {
				$trim = ZF_TRIMSIZE.ZF_TRIMTYPE;
			}
			$feeds = array(new AggregatedFeed($feeds, $this->makeFilterChain($trim, $onlyNew)));

		} else {
			// keeps feed separate (no aggregation): use individual subscription trim setting if auto is set
			$feeds = $this->processSingleFeeds($feeds, $trim, $onlyNew);
		}
		zf_debugRuntime("after feeds update and aggr");
		zf_debug('returning '.sizeof($feeds).' feeds for tag '.$tag, DBG_AGGR);

		return $feeds;
	}


	public function getChannelFeed($channelId, $updateMode, $trim, $onlyNew) {

		$sub = SubscriptionStorage::getInstance()->getSubscription($channelId);

		$this->cache->update(array($sub->id => $sub), $updateMode);

		// create filters
		$chain = $this->makeFilterChain($trim, $onlyNew);
		$feeds = $this->cache->getFeeds(array($sub->id => $sub), $chain);

		$feeds = $this->processSingleFeeds($feeds, $trim, $onlyNew);
		$feed = array_pop($feeds);

		return $feed;

	}


	/*
	feeds: array of feeds to handle
	trim: trim parameters on request. if auto use subscription settings
	onlyNew: if true, will keep only new items

	$result: feed or array of feeds, in function of aggregate
	 */
	private function processSingleFeeds($feeds, $trim, $onlyNew) {


		foreach($feeds as $feed) {

			// use the subscription setting if auto
			// keep the trim parameter if not auto
			if ($trim == 'auto') {
				//$trim = get subscription settings
				$sub = SubscriptionStorage::getInstance()->getSubscription($feed->subscriptionId);
				$trim = $sub->shownItems.'news';
			}
			$chain = $this->makeFilterChain($trim, $onlyNew);
			$feed->sortItems();
			$feed->filter($chain);
		}
		return $feeds;
	}


	private function makeFilterChain($trim, $onlyNew){

		$chain = new FilterChain();
		if ($onlyNew) {
			$chain->addFilter(new OnlyNewFilter());
		}

		if ($trim !== 'none') {
			$chain->setFeedTrim($trim);
		}
		// always add the 'mark new' filter
		$chain->addFilter(new MarkNewItemFilter());

		return $chain;
	}



}


