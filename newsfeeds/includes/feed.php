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


// ZebraFeeds feed class

/* stores data for a feed (channel info + items)
 and allows merging (aggregating) several feed objects


 
 
	* channel array structure
		title: channel title
		link: url to website
		description: channel description
		xmlurl: address of the subscription file
		favicon: URL of favicon
		logo: URL to channel logo

	* item array structure
		channel: reference to associated channel array
		link
		title
		date_timestamp
		content
		enclosures
			link 
			length
			type
		istruncated

	* metadata
		showeditems
		refreshtime
		last_fetched
		listname
		
 TODO: handle other cases than standard per-channel view
	   try to get rid of foreach to use for instead
 */

if (!defined('ZF_VER')) exit;

require_once($zf_path . 'includes/history.php');

class feed {


	// aggregated, normalized items
	// if we aggregate several feeds, index is timestamp
	var $items;
	var $channel;

	// merging/filter options
	var $trimtype = 'none';
	var $trimsize = 0;
	var $matchExpression = '';

	// timestamp before which we don't want news
	var $_earliest;

	// virtual state of this feed
	// false if directly obtained from the parser
	// true if merged with another feed object
	var $isVirtual = false;

	// when the feed is virtual, this is the name of the list
	var $listName;
		
	var $refreshTime;
	var $showedItems;
	var $last_fetched = 0;

	function feed() {
		$this->items = array();
		$this->channel = array();
		$this->_earliest = 0;

	}	 


	function setMatchExpression($expr) {
		$this->matchExpression = $expr;
	}

	function setTrim($type,$size=0) {
		$this->trimsize = $size;
		$this->trimtype = $type;
		
		// get timestamp we don't want to go further
		if ($this->trimtype == 'hours') {
			// earliest is the timestamp before which we should ignore news
			$this->_earliest = time() - (3600 * $this->trimsize);
		}
		if ($this->trimtype =='days') {
			// earliest is the timestamp before which we should ignore news

			// get timestamp of today at 0h00
			$todayts = strtotime(date("F j, Y"));

			// substract x-1 times 3600*24 seconds from that
			// x-1 because the current day counts, in the last x days
			$this->_earliest = $todayts -  (3600*24*($this->trimsize-1));
		}
		if ($type =='news') {
			$this->trimsize = $size;
		}
	}



	/* function to call after all RSS have been merged
	in order to finalize processing, like sorting and trimming */
	function postProcess($sort = true) {
		if ($sort) {
			$this->sortItems();
		}
		$this->trimItems();
		
		if ((defined('ZF_ONLYNEW') && ZF_ONLYNEW == 'yes') ) {
			$this->filterNonNew();
		}
	}

	/* this feed is an aggregation of feeds from a list
	   this method initializes this
	 */
	function initVirtual($listname) {

		$this->isVirtual = true;
		$this->listName = $listname;

		$this->channel['title'] = (ZF_OWNERNAME ==""?"":ZF_OWNERNAME." - ").$this->listName;
		$this->channel['xmlurl'] = ZF_URL.'?f=rss&zflist='.urlencode($this->listName);
		$this->channel['link'] = ZF_HOMEURL.'?zflist='.urlencode($this->listName);
		$this->channel['id'] = zf_makeId($this->channel['xmlurl'],'');
		$this->channel['articlelink'] = 'default';


		// fill the description
		$description = "Viewing ";
		if ($this->trimtype == 'today') {
			$description .= 'today\'s news ';
			$this->channel['link'] .= '&zftrim=today';
		} else if ($this->trimtype == 'yesterday') {
			$description .= 'yesterday\'s news ';
			$this->channel['link'] .= '&zftrim=yesterday';
		} else if ($this->trimtype == 'onlynew') {
			$description .= 'only new news items ';
			$this->channel['link'] .= '&zftrim=onlynew';
		} else if ($this->trimtype == 'hours') {
			$description .= 'news of the last '.$this->trimsize.' hours ';
			$this->channel['link'] .= '&zftrim='.$this->trimsize.$this->trimtype;
		} else if ($this->trimtype == 'days') {
			$description .= 'news in the last '.$this->trimsize.' days ';
			$this->channel['link'] .= '&zftrim='.$this->trimsize.$this->trimtype;
		} else if ($this->trimtype == 'news') {
			$description .= 'latest '.$this->trimsize.' news ';
			$this->channel['link'] .= '&zftrim='.$this->trimsize.$this->trimtype;
		} else if ($this->trimtype == 'none') {
			$description .= "all news";
			$this->channel['link'] .= '&zfviewmode=date';
		}
		if (!empty($this->matchExpression) ){
			$description .= ' matching keyword \"'.$this->matchExpression.'\"';
		}
		$this->channel['description'] = $description;


	}
		

	function mergeWith( &$feed ) {
		$this->isVirtual = true;

		$this->touch($feed);
		$this->mergeItems($feed);
	}

		/* merge the news items from the RSS object into our list of items
		but before, do some stuff, like
		- normalize items
		- keep only the ones we want on a timeframe basis
		- add additional data to items
		 */
	function mergeItems(&$feed) {

		$itemcount = 0;
		foreach ($feed->items as $item) {

			$itemts = (isset($item['date_timestamp'])) ? $item['date_timestamp']: 0;
			$basetime = time();
			
			// get timestamp of today at 0h00
			$todayts = strtotime(date("F j, Y"));
			$yesterdayts = $todayts - (3600*24);
			
			// optionally exclude news with date in future
			if (ZF_NOFUTURE == 'yes') {
				if ($itemts > $basetime ) {
					if (ZF_DEBUG==4) {
						zf_debug('News item \"'.$item['title'].'\" has future date. Skipped.'.$basetime.' lower than '.$itemts);
					}
					continue;
				}
			}

			if ($this->trimtype == 'hours' || $this->trimtype =='days') {
				// consider onlyrecent items
				if (ZF_DEBUG==4) {
					zf_debug( "comparing item date ".date("F j, Y, g:i a",$itemts)."(".$itemts.") to earliest wanted ". $this->_earliest ." : ".date("F j, Y, g:i a",$this->_earliest));
				}

				if ( $itemts >= $this->_earliest) {
					if (ZF_DEBUG==4) {
						zf_debug( 'News item within time frame');
					}

				} else {
					if (ZF_DEBUG==4) {
						zf_debug( 'News item outside time frame');
					}
					continue;
				}
			} else if($this->trimtype == 'today') {
				if ($itemts < $todayts ) {
					if (ZF_DEBUG==4) {
						zf_debug('News item \"'.$item['title'].'\" is not from today. Skipped.'.$itemts.' lower than '.$todayts);
					}
					continue;
				}
			} else if($this->trimtype == 'yesterday') {
				if ($itemts >= $todayts || $itemts < $yesterdayts) {
					if (ZF_DEBUG==4) {
						zf_debug('News item \"'.$item['title'].'\" is not from yesterday. Skipped.');
					}
					continue;
				}	
			} else if($this->trimtype == 'onlynew') {
				if ( ! $item['isnew'] ) {
					if (ZF_DEBUG==4) {
						zf_debug('News item \"'.$item['title'].'\" is not new/unseen. Skipped.');
					}
					continue;
				}	
			} else {
				// no particular trim
				if (ZF_DEBUG==4) {
					zf_debug( 'Item '.$item['title'].' passes timeframe check');
				}
			}

			// last check: do we need to match, and does this news match
			if ( strlen($this->matchExpression) > 0) {

				if (!$this->itemMatches($item)) {
					if (ZF_DEBUG==4) {
						zf_debug( 'News item DOES NOT match. rejected');
					}
					continue;
				} else {
					if (ZF_DEBUG==4) {
						zf_debug( 'News item match');
					}
				}
			}

			if (function_exists('zf_CheckNewsItem')) {
				zf_CheckNewsItem(&$item);
				if ($item['discarded']) {
					// finally add our item to the news array
					if (ZF_DEBUG==4) {
						zf_debug('Item discarded by user function: \"'.$item['title'].'\"');
					}
					continue;
				}
			}
			// finally add our item to the news array
			if (ZF_DEBUG==4) {
				zf_debug('Item merged: "'.$item['title'].'" (trimtype: '.$this->trimtype.')');
			}
			$this->items[] = $item;
			$itemcount++;
			if (ZF_DEBUG==4) {
			zf_debug("Merged $itemcount item(s) from ".$feed->channel['title']); 
			}
			if ($itemcount >= $feed->showedItems) {
				if (ZF_DEBUG==4) {
					zf_debug("showedItems reached for $feed->channel['title']. Exiting merge loop."); 
				}
				break;
			}

		}// foreach item of feed


	}

	// retain the most recent fetch date of all feeds integrated in this one
	function touch(&$feedToMerge) {

		// if the feed we are merging into is more recent, the date of this feed is touched
		if ($this->last_fetched < $feedToMerge->last_fetched) {
			$this->last_fetched = $feedToMerge->last_fetched;
		}
	}

	/* matching function. check an expression against 
		title and description of an item
		defaut: case insensitive keyword match
		could be regexp
		return true if match
		*/
	function itemMatches(&$item) {
		$subject = strip_tags($item['title']) . ' ' .strip_tags($item['description']);
		//echo "checking ".$item['title']." for ". $exp.":".strpos(strtolower($subject), strtolower($exp))."<br/>";
		return !(strpos(strtolower($subject), strtolower($this->matchExpression))===false); 
	}


	/* get rid of superfluous items exceeding our limit ,  only by numbers (trimtype = news)*/
	function trimItems() {
		// trim items by number
		if ($this->trimtype == 'news') {
			if (ZF_DEBUG==4) {
				zf_debug( 'trimming to '.$this->trimsize.' news');
			}
			$this->items = array_slice($this->items, 0, $this->trimsize);
		}
	}


	/* sort our aggregated items */
	function sortItems() {
		if (ZF_DEBUG==4) {
			zf_debug('sorting items');
		}

		/* sort by timestamp */
		usort($this->items, 'zf_compareItemsDate');

	}

	function filterNonNew() {
		$currentitems = $this->items;
		$this->items=array();
		foreach ($currentitems as $item) {
			if ($item['isnew']) {
				$this->items[] = $item;
			}
		}
	}

	/* adapt the channel array from data set externally, or with default values */
	function customizeChannel(&$channeldata) {

		if (isset($channeldata['title'])) {
			$this->channel['title'] = $channeldata['title'];
		}
		if (isset($channeldata['description'])) {
			$this->channel['description'] = $channeldata['description'];
		}
		$this->channel['articlelink'] = $channeldata['articlelink'];

	}

	/* make sure our channel array has all what we need
	 this data will get cached, so this function is called only once, 
	 right after the feed is fetched over http */
	function normalize(&$channeldata){

		/* for this it's okay to store in cache */
		$this->channel['id'] = zf_makeId($this->channel['xmlurl'], '');
		array_walk($this->items, 'zf_normalizeItem', &$channeldata);
	}
	

	/* for non virtual feeds, we need to link items to their original channel
	 * we'll need it for the template */
	function bindItemsToChannel() {
		for ($i=0; $i<count($this->items); $i++) {
		   $this->items[$i]['channel'] = &$this->channel;
		}
 
	}

}
/* end of Feed class */


	/* compare the date of two news items
	used as callback in the sorting items call*/
function zf_compareItemsDate($a, $b) {
	return $a["date_timestamp"] < $b["date_timestamp"];

}

/*all sorts of processing to the item object
 Everything that happens here is cached
 - normalize items for dates and description
 - make relative paths absolute in item's description
 - set items and channel id
*/
function zf_normalizeItem(&$item, $key, &$channel){


	/* build our id, used as CSS element id. add timestamp to make it unique  */
	$item['id'] = zf_makeId($channel['xmlurl'], $item['link'].$item['title']);

	if ( $item['date_timestamp'] == 0) {

		// we should let our
		// history management system decide
		//$item['date_timestamp'] = 0;
		//print_r($channel);
		$history= $channel['history'];
		$firstseen = $history->getDateFirstSeen($item['id']);
		if ($firstseen == 0) {
			$firstseen = time();
		}
		$item['date_timestamp'] = $firstseen;
		if (ZF_DEBUG==2) {
			zf_debug('-- using history time '. $item['date_timestamp']);
		}

	}

	if (!isset($item['summary']) || (strlen($item['summary']) == 0) ) {
		  $item['summary'] = $item['description'];
	}
	
	$strsum = strip_tags($item['summary']);
	$item['istruncated'] = false;
	if (strlen($strsum) > ZF_MAX_SUMMARY_LENGTH ) {
		$item['summary'] = substr($strsum, 0, ZF_SUMMARY_TRUNCATED_LENGTH).'...';
		$item['istruncated'] = true;
	}
}


/* completely empirical function to try to cope with exotic date formats 
in order to have them accepted by strtotime (and get a real nice timestamp)
it works for feeds I read

2006-02-14T14:50:04Z -> 2006-02-14T14:50:04
2006-02-15T23:25:00+00:00 -> 2006-02-15T23:25:00
2006-02-15T23:25:00-08:00 -> 2006-02-15T23:25:00

sometimes you also see:
2006-02-15T23:25:00.736+01:00 -> 2006-02-15T23:25:00

drawback: it discards Timezone information

input: a date string with unhandled format
output: a unix timestamp of this date, coming from strtotime
*/
function zf_cleanupDate($datestr) {
	$search = array("/\.[0-9]+Z$/", "/Z$/", "/\.[0-9]+[\+-][0-9]+:[0-9]+/", "/[\+-][0-9]+:[0-9]+/");
	$replace = array(" ");
	$newdate = preg_replace($search, $replace, $datestr);
	return strtotime($newdate);

}


?>
