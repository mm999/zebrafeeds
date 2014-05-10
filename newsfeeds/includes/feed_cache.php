<?php
/*
 * Feed cache class for ZebraFeeds.


 Borrowed from MagpieRSS:

 * Author:		Kellan Elliott-McCrea <kellan@protest.net>
 * Version:		0.51
 * License:		GPL
 *
 */

if (!defined('ZF_VER')) exit;


class FeedCache {
	private $BASE_CACHE = './cache';	// where the cache files are stored
	public $ERROR	   = "";		   // accumulate error messages
	private $MAX_AGE;

	static private $instance = NULL;

	private function __construct($base='') {
		if ( $base ) {
			$this->BASE_CACHE = $base;
		}

		$this->MAX_AGE = ZF_DEFAULT_REFRESH_TIME * 60;

		// attempt to make the cache directory
		if ( ! file_exists( $this->BASE_CACHE ) ) {
			$status = @mkdir( $this->BASE_CACHE, 0755 );

			// if make failed
			if ( ! $status ) {
				zf_error("Cache couldn't make dir '" . $this->BASE_CACHE . "'.");
			}
		}

	}

	static public function getInstance() {
		if (self::$instance == NULL){
			self::$instance = new FeedCache(ZF_CACHEDIR);
		}
		return self::$instance;
	}


/*=======================================================================*\
	Function:	set
	Purpose:	add an item to the cache, keyed on url
	Input:		url from wich the rss file was fetched
	Output:		true on sucess
\*=======================================================================*/
	public function set ($key, $rss) {
		$cache_file = $this->file_name( $key );
		$fp = @fopen( $cache_file, 'w' );

		if ( ! $fp ) {
			zf_error(
				"Cache unable to open file for writing: $cache_file"
			);
			return 0;
		}


		$data = $this->serialize( $rss );
		fwrite( $fp, $data );
		fclose( $fp );

		return $cache_file;
	}

/*=======================================================================*\
	Function:	get
	Purpose:	fetch an item from the cache
	Input:		url from wich the rss file was fetched
	Output:		cached object on HIT, false on MISS
\*=======================================================================*/
	public function get ($key) {
		$cache_file = $this->file_name( $key );

		if ( ! file_exists( $cache_file ) ) {
			zf_debug("Cache doesn't contain: $key (cache file: $cache_file)", DBG_FEED);
			return 0;
		}

		$fp = @fopen($cache_file, 'r');
		if ( ! $fp ) {
			zf_error(
				"Failed to open cache file for reading: $cache_file"
			);
			return 0;
		}

		if ($filesize = filesize($cache_file) ) {
			$data = fread( $fp, filesize($cache_file) );
			$rss = $this->unserialize( $data );

			return $rss;
		}

		return 0;
	}

/*=======================================================================*\
	Function:	check_cache
	Purpose:	check a url for membership in the cache
				and whether the object is older then MAX_AGE (ie. STALE)
	Input:		url from wich the rss file was fetched
				MAX_AGE to check against
	Output:		HIT, STALE or MISS
\*=======================================================================*/
	public function check_cache ( $key ) {

		$age = $this->cache_age($key);

		if ( $age > -1 ) {
			if ( $this->MAX_AGE > $age ) {
				// object exists and is current
				return 'HIT';
			}
			else {
				// object exists but is old
				return 'STALE';
			}
		}
		else {
			// object does not exist
			return 'MISS';
		}
	}

	public function cache_age( $key ) {
		$filename = $this->file_name($key);
		if ( file_exists( $filename ) ) {
			$mtime = filemtime( $filename );
			$age = time() - $mtime;
			return $age;
		}
		else {
			return -1;
		}
	}

/*=======================================================================*\
	Function:	serialize
\*=======================================================================*/
	private function serialize ( $feed ) {
		return serialize( $feed );
	}

/*=======================================================================*\
	Function:	unserialize
\*=======================================================================*/
	private function unserialize ( $data ) {
		return unserialize( $data );
	}

/*=======================================================================*\
	Function:	file_name
	Purpose:	map url to location in cache
	Input:		url from wich the rss file was fetched
	Output:		a file name
\*=======================================================================*/
	private function file_name ($key) {
		$filename = $key;
		return join( DIRECTORY_SEPARATOR, array( $this->BASE_CACHE, $filename ) );
	}



	public function updateSingle($sub) {
		zf_debug('fetching remote file: '.$sub->title, DBG_FEED);
		$feed = zf_xpie_fetch_feed($sub->id, $sub->xmlurl, $resultString);
		if ( $feed ) {
			zf_debug("Fetch successful: ".$sub->title, DBG_FEED);

			$feed->normalize($sub->title, $sub->link, $sub->xmlurl, $sub->description);

			// add object to cache
			$this->set( $sub->id, $feed );
		} else {
			zf_debug('failed fetching remote file '.$sub->xmlurl, DBG_FEED);
		}

	}


	/* update the cache for the array of subscriptions provided
		updateMode: force, none, auto
		ZebraFeeds addition

		returns: nothing
	*/
	public function update($subscriptions, $updateMode = 'auto') {

		$subsToRefresh = array();

		foreach ($subscriptions as $sub) {

			zf_debug("Checking cache for $sub->title", DBG_FEED);
			$status = $this->check_cache($sub->id);
			zf_debug("status: $status", DBG_FEED);
			$needsRefresh = ($status == 'STALE') || ($status == 'MISS');

			if ($updateMode == 'force' || ($needsRefresh && $updateMode == 'auto') ) {
				//$this->updateSingle($sub);
				$subsToRefresh[] = $sub;
			}

		}
		if (sizeof($subsToRefresh)>0) {
			$this->updateAllParallel($subsToRefresh);
		} else {
			zf_debug('nothing to refresh', DBG_FEED);
		}
	}


	/* a big thanks to FiveFilters for this */
	protected function updateAllParallel($subscriptions) {

		zf_debugRuntime("before feeds parallel update");

		$urls= array();
		foreach ($subscriptions as $sub) {
			$url = ZF_URL.'/index.php?q=force-refresh&id='.$sub->id;
			$urls[] = $url;
		}


		// Request all feed items in parallel (if supported)
		$http = new HumbleHttpAgent();
		$http->userAgentDefault = HumbleHttpAgent::UA_PHP;
		zf_debug('fetching all '.sizeof($urls).' feeds', DBG_FEED);

		$http->fetchAll($urls);

		foreach($urls as $url){
			if ($url && ($response = $http->get($url, true)) && ($response['status_code'] < 300 || $response['status_code'] > 400)) {
				$effective_url = $response['effective_url'];
				zf_debug('response: '. $response['body'], DBG_FEED);
			}

		}

		zf_debugRuntime("End of parallel update");
	}


	public function getFeeds($subscriptions, $chain = null) {
		$feeds = array();
		foreach($subscriptions as $sub) {
			$feed = $this->get($sub->id);

			if ($feed) {
				$feed->filter($chain);
				$feeds[$sub->id] = $feed;

			} else {
				zf_debug('empty feed returned', DBG_FEED);
				$feeds[$sub->id] = new PublisherFeed($sub->id);
			}

		}
		return $feeds;
	}


	public function getItem($key, $itemId, $filter = null) {
		$feed = $this->get($key);
		return $feed->getItem($itemId, $filter);
	}


}

