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
// ZebraFeeds feed handler class

if (!defined('ZF_VER')) exit;

require_once($zf_path . 'includes/classes.php');
require_once($zf_path . 'includes/feed.php');
require_once($zf_path . 'includes/history.php');
require_once($zf_path . 'includes/feed_cache.php');
require_once($zf_path . 'includes/simplepie_fetch.php');


class FeedHandler {

	protected $_feed;
	protected $subscription;
	protected $lastvisit;
	protected $now;

	public function __construct($subscription, $lastvisit, $now) {
		$this->subscription = $subscription;
		$this->lastvisit = $lastvisit;
		$this->now = $now;
	}

	public function isFeedCached() {
		$cache = FeedCache::getInstance();
		if (!$cache->ERROR) {
			$status =  $cache->check_cache($this->subscription->xmlurl.ZF_ENCODING);
			return ($status == 'HIT');
		} else {
			return FALSE;
		}
	}

	/* get forcefully from cache. if not in cache or cache error, get refreshed from network */
	public function getFeedFromCache() {

		$cache = FeedCache::getInstance();
		if (!$cache->ERROR) {

			// store parsed XML by desired output encoding
			// as character munging happens at parse time
			$cache_key		 = $this->subscription->xmlurl . ZF_ENCODING;

			$feed = $cache->get( $cache_key );
			if ( isset($feed) and $feed ) {
				// should be cache age
				$feed->from_cache = 1;
				zf_debug("Read from cache", DBG_FEED);

			} else {
				zf_debug("invalid Cache, force refresh", DBG_FEED);
				$feed = $this->_getRefreshedFeed();
			}
		}
		return $feed;
	}

	/* get feed from network */
	public function getRefreshedFeed() {
		// if we got there, it means we have to fetch from network
		zf_debug('fetching remote file '.$this->subscription->xmlurl, DBG_FEED);

		$feed = zf_xpie_fetch_feed($this->subscription, $resultString);
		if ( $feed ) {
			zf_debug("Fetch successful", DBG_FEED);
			/* one shot: add our extra data and do our post processing
			  (we will here fix missing dates)
			BEFORE storing to cache */
			$feed->normalize($feedHistory);


			// add object to cache
			$cache = FeedCache::getInstance();
			$cache_key = $this->subscription->xmlurl . ZF_ENCODING;
			$cache->set( $cache_key, $feed );
			return $feed;
		} else {
			zf_debug('failed fetching remote file '.$this->subscription->xmlurl, DBG_FEED);
			return NULL;
		}
	}

	/* if cache stil valid, get from cache, otherwise force refresh */

	public function getAutoFeed() {
		// if is cached

		zf_debugRuntime("getting auto feed ".$this->subscription->xmlurl);
		$resultString = '';
		$history = new history($this->subscription->xmlurl);

		// GET
		if ($this->isFeedCached()) {
			$this->_feed = $this->getFeedFromCache();
		} else {
			$this->_feed = $this->getRefreshedFeed();
		}
		
		zf_debugRuntime("after fetch ".$this->subscription->xmlurl);

		if ($this->_feed) {
			/* detect new yet unseen items
			this happens now, because when this feed is already in the cache
			it makes no sense to use the cached value of "isNew" */
			$history->markNewFeedItems($this->_feed->items, $this->lastvisit, $this->now);
			$history->purge();
		}

		unset($history);

		// in case of Error
		// get error reason from zf_custom_fetch_rss
		if (strlen($resultString)>0) {
			// user friendly channel title
			//$channelTitle = isset($channeldata['title']) ? $channeldata['title']:$channeldata['xmlurl'];
			$this->errorLog .= $resultString.'<br/>';
			zf_debug('error reported: '. $resultString, DBG_FEED);

		}

		return $this->_feed;
	}


}
