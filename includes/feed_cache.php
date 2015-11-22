<?php
/*
 * Feed cache class for ZebraFeeds.

 */

if (!defined('ZF_VER')) exit;


class FeedCache {


	public $ERROR	   = "";		   // accumulate error messages
	private $MAX_AGE;

	static private $instance = NULL;

	private function __construct() {

		$this->MAX_AGE = ZF_DEFAULT_REFRESH_TIME * 60;


	}

	static public function getInstance() {
		if (self::$instance == NULL){
			self::$instance = new FeedCache();
		}
		return self::$instance;
	}


	/*
	Store the new items out of a freshly fetched feed into the cache
	input:
	 	source id
		SimplePie feed

	returns the list of ids in the cache after the ingest
	*/
	private function ingestFeed($sourceId, $feed) {
		// get list of item ids, flip to get them as keys iso values,
		// to allow checking key existence iso searching
		zf_debug('ingesting feed for '.$sourceId, DBG_FEED);
		$ids = array_flip(array_column(DBProxy::getInstance()->getCacheContent($sourceId), 'id'));
		$currentCache = array();

		zf_debug('IDs in cache: ');if (ZF_DEBUG & DBG_FEED) var_dump($ids);
		foreach ($feed->get_items() as $item) {
			$id = zf_makeId($sourceId, $item->get_link().$item->get_title());
			$currentCache[] = $id;
			zf_debug("must $id be recorded?",DBG_FEED);
			// if this item not already cached
			if (!array_key_exists($id, $ids)) {
				zf_debug('storing item '.$id, DBG_FEED);
				// add it to the cache
				DBProxy::getInstance()->recordItem( new IngestableItem($sourceId, $id, $item) );
			}
		}
		// mark items as seen in latest feed
		DBProxy::getInstance()->recordItemFetchTime($currentCache);
		return $currentCache;
	}


	/*
	return either feed for single source or aggregated feed for multiple source
	$sub = Subscription object(s). if array indexed by source_id -> aggregation
	$params
	- max
	- sincePubdate
	- impressedSince
	*/
	public function getFeed($sub, $params) {
		zf_debug('getting feed from cache ', DBG_FEED);
		if (ZF_DEBUG & DBG_FEED) var_dump($params);
		if (is_array($sub)) {
			$max = array_key_exists('max',$params)?$params['max']:1000;
			/*foreach($sub as $subscription) {
				$target[] = $subscription->source->id;
			}*/
			$target = array_keys($sub);
		} else {
			$max = array_key_exists('max',$params)?$params['max']:$sub->shownItems;
			$target = array($sub->source->id);
		}
		$sincePubdate = array_key_exists('sincePubdate',$params)?$params['sincePubdate']:0;
		$impressedSince = array_key_exists('impressedSince',$params)?$params['impressedSince']:0;

		$db = DBProxy::getInstance();

		$items = $db->getItems($target, $max, $sincePubdate, $impressedSince);
		$feed = new Feed();
		if (!is_array($sub)) {
			$feed->source = $sub->source;
			$feed->last_fetched = $db->getLastUpdated($sub->source->id);
		}
		$sessionStart = time() - ZF_SESSION_DURATION;
		foreach ($items as $item) {
			$source = is_array($sub)?$sub[$item['source_id']]->source:$sub->source;
			$feed->addItem( NewsItem::createFromFlatArray($source, $item, $sessionStart));
		}
		$db->markItemsAsImpressed(array_column($items, 'id'));

		return $feed;
	}


	public function getItem($itemId) {
		$itemarr = DBProxy::getInstance()->getSingleItem($itemId);
		$sub = SubscriptionStorage::getInstance()->getSubscription($itemarr['source_id']);
		$item = NewsItem::createFromFlatArray($sub->source, $itemarr, time() - ZF_SESSION_DURATION);
		DBProxy::getInstance()->markItemsAsImpressed(array($itemId));
		return $item;
	}

	public function saveItemDescription($item) {
		DBProxy::getInstance()->recordItemDescription($item->id, $item->description);
	}

	public function lockItem($itemId,$flag) {
		DBProxy::getInstance()->setItemSaved($itemId, $flag);
	}

/*=======================================================================*\
	Function:	check_cache
	Purpose:	check a feed source for membership in the cache
				and whether the object is older than MAX_AGE (ie. STALE)
	Input:		source id from wich the rss file was fetched
	Output:		HIT, STALE or MISS
\*=======================================================================*/
	private function check_cache($sourceId){

		$now = time();
		$last = DBProxy::getInstance()->getLastUpdated($sourceId);

		zf_debug('checking cache age for '.$sourceId, DBG_FEED);

		if ($last > 0){
			$age = $now - $last;
			zf_debug("now is $now, last: $last => age: $age", DBG_FEED);
			if ($this->MAX_AGE > $age){
				// items exist and are current
				return 'HIT';
			}
			else {
				// items exist but are old
				return 'STALE';
			}
		}
		else {
			// no items in cache
			return 'MISS';
		}
	}

	/* force update the cache for a single feed
		ZebraFeeds addition

	$subscription: a subscription object

		returns: nothing
	*/
	public function updateSingle($source) {
		zf_debug('fetching remote file: '.$source->xmlurl, DBG_FEED);
		$proxy = new SourceProxy();
		$SPfeed = $proxy->fetchFeed($source, $resultString);
		if ($SPfeed) {
			zf_debug("Fetch successful: ".$source->title, DBG_FEED);

			// add object to cache
			$keepers = $this->ingestFeed($source->id, $SPfeed);
			//after update, clean up old items not present in the feed,
			// except the savec ones
			DBProxy::getInstance()->purgeSourceItems($source->id, $keepers);
		} else {
			zf_debug('failed fetching remote file '.$resultString.' - '.$source->xmlurl, DBG_FEED);
		}

	}


	/* update the cache for multiple feeds, in parallel
		ZebraFeeds addition

	$subscriptions: array of subscription objects
	$updateMode: force, none, auto

		returns: nothing
	*/
	public function update($subscriptions, $updateMode = 'auto') {

		$subsToRefresh = array();
		if ($updateMode !== 'none') {
			foreach ($subscriptions as $sub) {

				zf_debug('Checking cache for '. $sub->source->title, DBG_FEED);
				$status = $this->check_cache($sub->source->id);
				zf_debug("status: $status", DBG_FEED);
				$needsRefresh = ($status == 'STALE') || ($status == 'MISS');

				if ($needsRefresh) {
					$subsToRefresh[] = $sub;
				}

			}
			if (sizeof($subsToRefresh)>0) {
				$this->updateAllParallel($subsToRefresh);
			} else {
				zf_debug('nothing to refresh', DBG_FEED);
			}
		} else {
				zf_debug('update mode is none, no refresh', DBG_FEED);
		}
	}


	/* a big thanks to FiveFilters for this */
	protected function updateAllParallel($subscriptions) {

		zf_debugRuntime("before feeds parallel update");

		$urls= array();
		foreach ($subscriptions as $sub) {
			$url = ZF_URL.'/pub/index.php?q=force-refresh&id='.$sub->source->id;
			$urls[] = $url;
		}


		// Request all feed items in parallel (if supported)
		$http = new HumbleHttpAgent();
		$http->userAgentDefault = HumbleHttpAgent::UA_PHP;
		zf_debug('fetching all '.sizeof($urls).' feeds', DBG_FEED);

		$http->fetchAll($urls);

		foreach($urls as $url){
			zf_debug('going after '.$url, DBG_FEED);
			if ($url && ($response = $http->get($url, true)) && ($response['status_code'] < 300 || $response['status_code'] > 400)) {
				$effective_url = $response['effective_url'];
				/*zf_debug('response: '. $response['body'], DBG_FEED);
				if(DBG_FEED & ZF_DEBUG) var_dump($response);*/
			}

		}

		zf_debugRuntime("End of parallel update");
	}

	public function compact() {
		DBProxy::getInstance()->vacuum();
	}

	public function flush() {
		DBProxy::getInstance()->purgeOldItems(time());
	}


}

