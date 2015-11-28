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
		$this->cache = FeedCache::getInstance();
	}


	/*
	get a single NewsItem object from cache
	 */
	public function getItem($itemId) {
		return $this->cache->getItem($itemId);
	}

	/*
	get a single NewsItem object downloaded by Readability reader by FiveFilters
	 */
	public function downloadItem($itemId) {
		$item = $this->cache->getItem($itemId);
		$html = file_get_contents($item->link);
		$reader = new Readability($html, $item->link);
		if ($reader->init()) {
			$item->description = $reader->articleContent->innerHTML;
			// save the downloaded content to cache, just in case
			$this->cache->saveItemDescription($item);
		}
		return $item;

	}

	/*
	get a single NewsItem object downloaded by Readability reader by FiveFilters
	 */
	public function saveItem($itemId, $saveFlag) {
		$this->cache->lockItem($itemId,$saveFlag);
		return $this->cache->getItem($itemId);
	}

	/*
	get feeds for a tag

	$tag: get feeds matching this tag
	$aggregate: if true, merge all feeds into one, sorted by date.
	$trim: shorten the news list to Xnews; Xdays or Xhours, or auto (=config or subscription setting).
	$onlyNew: if 1, will keep only new items

	$result: array of feeds, single element if $aggregate = true, one element per subscription otherwise
	 */
	public function getFeedsForTag($tag, $aggregate, $trim, $onlyNew) {

		$subs = SubscriptionStorage::getInstance()->getActiveSubscriptions($tag);
		zf_debugRuntime("before feeds update");

		zf_debug('processing '.sizeof($subs).' subs for tag '.$tag, DBG_AGGR);
		if (ZF_DEBUG & DBG_AGGR) var_dump($subs);

		$this->cache->update($subs, ((ZF_REFRESHMODE=='automatic')?'auto':'none'));


		$feeds = array();

		$params1 = array();
		if ($onlyNew==1)
			$params1['impressedSince'] = time() - ZF_SESSION_DURATION;


		if ($aggregate) {
			if ($trim == 'auto') {
				$trim = ZF_TRIMSIZE.ZF_TRIMTYPE;
			}

			$params= array_merge($params1, $this->buildLimiterParam($trim));

			$feeds[] = $this->cache->getFeed($subs, $params);

		} else {
			// keeps feed separate (no aggregation): use individual subscription trim setting if auto is set
			foreach ($subs as $id => $sub) {
				if ($trim == 'auto') {
					$trim = $sub->shownItems.'news';
				}
				$params = array_merge($params1, $this->buildLimiterParam($trim));
				$feeds[] = $this->cache->getFeed($sub, $params);
			}
		}

		zf_debugRuntime("after feeds update and aggr");
		zf_debug('returning '.sizeof($feeds).' feeds for tag '.$tag, DBG_AGGR);

		return $feeds;
	}


	/*
	get all news for a single channel feed

	$channelId: identifier (from subscription list)
	$updateMode: force, none, auto
	$trim: shorten the news list to Xnews; Xdays or Xhours, or auto (=config or subscription setting).
	$onlyNew: if 1, will keep only new items

	$result: single feed
	 */
	public function getPublisherFeed($sourceId, $updateMode, $trim, $onlyNew) {

		$sub = SubscriptionStorage::getInstance()->getSubscription($sourceId);

		$this->cache->update(array($sub->id => $sub), $updateMode);

		if ($trim == 'auto') {
			$trim = $sub->shownItems.'news';
		}
		$params1 = array();
		if ($onlyNew == 1) {
			$params1['impressedSince'] = time() - ZF_SESSION_DURATION;
		}
		$params = array_merge($params1, $this->buildLimiterParam($trim));
		$feed = $this->cache->getFeed($sub, $params);

		return $feed;

	}

	private function buildLimiterParam($trim) {
		zf_debug("handling trim string $trim", DBG_AGGR);

		$result = array();

		if (preg_match("/([0-9]+)(.*)/",$trim, $matches)) {
            $type = $matches[2];
            $size = $matches[1];

			// get timestamp we don't want to go further
			switch ($type) {
				case 'hours':
				// earliest is the timestamp before which we should ignore news
				$result['sincePubdate'] = time() - (3600 * $size);
				break;
			case 'days':
				// earliest is the timestamp before which we should ignore news

				// get timestamp of today at 0h00
				$todayts = strtotime(date("F j, Y"));

				// substract x-1 times 3600*24 seconds from that
				// x-1 because the current day counts, in the last x days
				$result['sincePubdate'] = $todayts -  (3600*24*($size-1));
				break;

			case 'news':
				$result['max'] = $size;
				break;
			}

		}
		return $result;
	}

}


