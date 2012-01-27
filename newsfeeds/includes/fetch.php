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
// ZebraFeeds RSS fetch layer
// embeds the cache access policy and relies on a parser and a cache storage
//
/* 
- check cache. if cache oK, read Feed object from cache
- otherwise, fetch basic Feed object from either SimplePie or Magpie. 
   Feed object is then a simple data structure translation
- process feed object, clean up dates and polish data
- save to cache
 */
if (!defined('ZF_VER')) exit;

require_once($zf_path . 'includes/feed.php');
require_once($zf_path . 'includes/feed_cache.php');

if (ZF_RSSPARSER == "magpie") {
	require_once($zf_path . 'includes/magpie_fetch.php');
} else {
	require_once($zf_path . 'includes/simplepie_fetch.php');
}	 



/* steal of the magpieRSS fetch_rss function
	We just want to be able to have a cache age time on a per-feed basis
	while in the original function it's common to all feeds
 channeldata : array with indexes
		xmlurl
		refreshtime
		showeditems

	$refreshtime argumentis expected to be in minutes

	should be the only call to magpie or simplepie

	if refreshtime == -1 (infinite): force the use of the cached version
	if refreshtime == 0	 : force refresh from publisher's website
	else, use refreshtime in minutes

	returns an object of the Feed class
 */
function zf_fetch_rss(&$channeldata, $refreshtime, &$resultString) {
	
	if (ZF_RSSPARSER == "magpie") {
		// initialize constants
		init();
	}

	if ( !isset($channeldata['xmlurl']) ) {
		error("zf_fetch_rss called without a url");
		return false;
	} else {
		$url = $channeldata['xmlurl'];
		if ( ZF_DEBUG > 1) {
			zf_debug('requested feed '.$url.'<br/>', E_USER_NOTICE) ;
		}
	}

	// Flow
	// 1. check cache
	// 2. if there is a hit, make sure its fresh
	// 3. if cached obj fails freshness check, fetch remote
	// 4. if remote fails, return stale object, or error

		/* ZF change here: instead of a constant, use our variable
		magpieRSS cache age is in seconds, but $refreshtime is in minutes */

	//debug("Refresh:".$refreshtime , E_USER_WARNING);
	$cache = new FeedCache( ZF_CACHEDIR, $refreshtime*60 );
	if ( ZF_DEBUG > 1) {
		zf_debug("Requested refresh time ".$refreshtime, E_USER_NOTICE);
	}

	if (ZF_DEBUG && $cache->ERROR) {
		zf_debug($cache->ERROR, E_USER_WARNING);
	}


	$cache_status	 = 0;		// response of check_cache
	$request_headers = array(); // HTTP headers to send with fetch
	$rss			 = 0;		// parsed RSS object
	$errormsg		 = 0;		// errors, if any

	// store parsed XML by desired output encoding
	// as character munging happens at parse time
	$cache_key		 = $url . ZF_ENCODING;

	if (!$cache->ERROR) {
		// return cache HIT, MISS, or STALE
		$cache_status = $cache->check_cache( $cache_key);
		if ( ZF_DEBUG > 1) {
			zf_debug("Cache ok. Cache age ".($cache->cache_age($cache_key)/60).', '.md5($cache_key). ' modif:'.date ("F d Y H:i:s.", filemtime(ZF_CACHEDIR.'/'.md5($cache_key))), E_USER_NOTICE);
		}
	}

	// if object cached, and cache is fresh, return cached obj
	// ZebraFeeds tweak: use cache only if refresh not forced or if explicitely requested
	if ($refreshtime != 0) {
		if ( $cache_status == 'HIT' || $refreshtime == -1) {
			$feed = $cache->get( $cache_key );
			if ( isset($feed) and $feed ) {
				// should be cache age
				$feed->from_cache = 1;
				if ( ZF_DEBUG > 1) {
					zf_debug("Cache ($cache_status, refreshtime: $refreshtime)<br/>", E_USER_NOTICE);
				}
			   /* set channel data, like title and description from what's 
				configured in the subscription list */
				$feed->customizeChannel($channeldata);
				/* for each item: $item['channel'] = &$rss->channel */
				$feed->bindItemsToChannel();

				return $feed;
			} else {
				if ( ZF_DEBUG > 1) {
					zf_debug("invalid Cache ($cache_status, refreshtime: $refreshtime)<br/>", E_USER_NOTICE);
				}

			}
		}
	}

		// if we got there, it means we have to fetch from network
	zf_debug('fetching remote file '.$url.'<br/>', E_USER_NOTICE);

	$feed = zf_xpie_fetch_feed($channeldata, $resultString);
	if ( $feed ) {
		zf_debug("Fetch successful<br/>");
		/* one shot: add our extra data and do our post processing
		BEFORE storing to cache */
		$feed->normalize($channeldata);

		/* set channel data, like title and description from what's 
		configured in the subscription list */
		$feed->customizeChannel($channeldata);
		
		// add object to cache
		$cache->set( $cache_key, $feed );
		/* for each item, link it to its channel array
		must be done after caching*/
		$feed->bindItemsToChannel();

		return $feed;
	}


	if ( ZF_DEBUG) {
		zf_debug('failed fetching remote file '.$url.'<br/>', E_USER_NOTICE);
	}

	// if we are here, fetch failed

	// attempt to return cached object
    $feed = $cache->get( $cache_key );
    if ( isset($feed) and $feed ) {
        // should be cache age
        $feed->from_cache = 1;
        if ( ZF_DEBUG ) {
            zf_debug("Returning STALE object for $url");
        }
        /* set channel data, like title and description from what's 
        configured in the subscription list */
        $feed->customizeChannel($channeldata);
        /* for each item: $item['channel'] = &$rss->channel */
		$feed->bindItemsToChannel();
				
		return $feed;
	}

	// else we totally failed
	$resultString = "Fetch error and no valid cache for ".$channeldata['xmlurl'];

	return false;

}







?>
