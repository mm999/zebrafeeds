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
require_once($zf_path . 'includes/fetch.php');


class FeedHandler {

	protected $_feed;
	protected $_subscription;
	protected $lastvisit;
	protected $now;

	public function __construct($subscription, $lastvisit, $now) {
		$this->_subscription = $subscription;
		$this->lastvisit = $lastvisit;
		$this->now = $now;
	}

	public function getFeedFromCache() {
	  return $this->_getFeed(-1);
	}

	public function getRefreshedFeed() {
	  return $this->_getFeed(0);
	}

	public function getAutoFeed() {
	  return $this->_getFeed($this->_subscription->refreshTime);
	}

	/* refreshtime:
	 -1: force from cache
	 0: force refresh
	 >0: use this refreshtime
	 */
	protected function _getFeed($requestedRefreshtime) {
		// TODO implement single global refreshtime

		// Refresh-time decision algorithm
		/* if provided $refreshtime is default
		 *	 if global option refresh mode == automatic,
		 use channel's refreshtime
		 if global option refresh mode = on request
			 force use of cache: set refreshtime to -1
		 else
			 use provided refreshtime
		 */
		$channelDesc = $this->_subscription->channel;

		$usedrefreshtime = $requestedRefreshtime;

		//is auto requested, check against global variable
		if ($requestedRefreshtime > 0) {
			zf_debug("requesting default refresh time for ".$channelDesc->xmlurl, DBG_FEED);
			$usedrefreshtime = (ZF_REFRESHMODE == 'automatic')? $requestedRefreshtime: -1;
		}

		zf_debug("Refresh mode: ". ZF_REFRESHMODE." ; requested Refreshtime : $requestedRefreshtime ; used refresh time: $usedrefreshtime", DBG_FEED);


		// QUICK DEBUG $refreshtime = -1;

		zf_debugRuntime("before fetch ".$channelDesc->xmlurl);
		$resultString = '';
		$history = new history($channelDesc->xmlurl);

		$this->_feed = zf_fetch_rss($channelDesc, $history, $usedrefreshtime, $resultString);
		zf_debugRuntime("after fetch ".$channelDesc->xmlurl);

		if ($this->_feed) {
			/* detect new yet unseen items
			this happens now, because this feed is already in the cache and it makes
			no sens to use the cached value of "isNew" */
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
