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

 */

if (!defined('ZF_VER')) exit;

require_once($zf_path . 'includes/history.php');

abstract class AbstractFeed {
	// aggregated, normalized items
	// if we aggregate several feeds, index is numeric position in the array
	protected $items;
	public $last_fetched = 0;

	// merging/filter options
	protected $_feedOptions = null;

	public function __construct() {
		$this->items = array();
	}

	public function addItem($item) {
		if (function_exists('zf_itemfilteronfetch')) {
			$keepit = zf_itemfilteronfetch($item);
			if (!$keepit) {
				// finally add our item to the news array
				zf_debug('Item discarded by user function: \"'.$item->title.'\"', DBG_AGGR);
				return;
			}
		}
		$this->items[$item->id]= $item;
	}

	public function setTrim($opt) {
		$this->_feedOptions = $opt;
	}


	/* get rid of superfluous items exceeding our limit, removing the bottom
	of the array, only by numbers (trimSize)*/
	public function trimItems($trimsize) {
		zf_debug("Keeping only last $trimsize items", DBG_AGGR);
		$this->items = array_slice($this->items, 0, $trimsize);
	}

/* TODO: all this trimming functions in one, applying to any kind of feed */
	public function filterNonNew() {
		$currentitems = clone($this->items);
		$this->items = array();
		foreach ($currentitems as $item) {
			if ($item->isNew) {
				$this->items[$item->id] = $item;
			}
		}
	}

	public function getItems() {
		return $this->items;
	}

	public function getItem($itemid) {

		if (isset($this->items[$itemid])) {
			return $this->items[$itemid];
		} else {
			return NULL;
		} 
	}

}

/* publisher feed is obtained from the RSS/ATOM parser
can be trimmed to "shownitems" */
class PublisherFeed extends AbstractFeed {

	public $from_cache;
	public $subscriptionId;

	public function __construct($subId) {
		parent::__construct();
		$this->subscriptionId = $subId;
	}


	/* make sure our channel array has all what we need
	 this data will get cached, so this function is called only once,
	 right after the feed is fetched over http */
	public function normalize($history){

		foreach ($this->items as $item) {
			$item->normalize($history);
		}

	}


}

/* AggregatedFeed is made of publisher feeds, er... aggregated
can be trimmed to last X [news|days|hours] */
class AggregatedFeed extends AbstractFeed {


	// timestamp before which we don't want news
	private $_earliest;

	/* this feed is an aggregation of feeds from a list
	   this method initializes this
	 */
	public function __construct($feeds) {

		parent::__construct();

		$this->_earliest = 0;


		foreach($feeds as $pubfeed) {
			$this->mergeItems($pubfeed);
		}
		$this->postProcess();

	}


	public function setTrim($opt) {
		parent::setTrim($opt);



		zf_debug("AggregatedFeed, trim set to ". $this->_feedOptions->trimType.', '.
		$this->_feedOptions->trimSize, DBG_AGGR);

		// get timestamp we don't want to go further
		if ($this->_feedOptions->trimType == 'hours') {
			// earliest is the timestamp before which we should ignore news
			$this->_earliest = time() - (3600 * $this->_feedOptions->trimSize);
		}
		if ($this->_feedOptions->trimType =='days') {
			// earliest is the timestamp before which we should ignore news

			// get timestamp of today at 0h00
			$todayts = strtotime(date("F j, Y"));

			// substract x-1 times 3600*24 seconds from that
			// x-1 because the current day counts, in the last x days
			$this->_earliest = $todayts -  (3600*24*($this->_feedOptions->trimSize-1));
		}
	}



	/* function to call after all RSS have been merged
	in order to finalize processing, like sorting and trimming */
	protected function postProcess($sort = true) {
		/*zf_debug("Post processing aggregated feed: sort=$sort, trimType =".
				, area$this->_feedOptions->trimType, DBG_AGGR);*/
		if ($sort) {
			$this->sortItems();
		}
		/*if ($this->_feedOptions->trimType == 'news')
			$this->trimItems($this->_feedOptions->trimSize);
*/
		if ((defined('ZF_ONLYNEW') && ZF_ONLYNEW == 'yes') ) {
			$this->filterNonNew();
		}
	}


		/* merge the news items from the RSS object into our list of items
		but before, do some stuff, like
		- keep only the ones we want on a timeframe basis
		- add additional data to items
		 */
	protected function mergeItems($feed) {

		zf_debug( 'Merging aggregated feed of sub '.$feed->subscriptionId, DBG_AGGR);
		$itemcount = 0;
		foreach ($feed->items as $item) {

			$itemts = (isset($item->date_timestamp)) ? $item->date_timestamp: 0;
			$basetime = time();

			// get timestamp of today at 0h00
			$todayts = strtotime(date("F j, Y"));
			$yesterdayts = $todayts - (3600*24);

			zf_debug( 'Merging item "'.$item->title.'"', DBG_AGGR);
			// optionally exclude news with date in future
			if (ZF_NOFUTURE == 'yes') {
				if ($itemts > $basetime ) {
					zf_debug('Item has future date. Skipped.'.$basetime.' lower than '.$itemts, DBG_AGGR);
					continue;
				}
			}

			if ($this->_feedOptions->trimType !== 'none') {
				if ($this->_feedOptions->trimType == 'hours' || $this->_feedOptions->trimType =='days') {
					// consider onlyrecent items
					zf_debug( "comparing item date ".date("F j, Y, g:i a",$itemts)."(".$itemts.") to earliest wanted ". $this->_earliest ." : ".date("F j, Y, g:i a",$this->_earliest), DBG_AGGR);

					if ( $itemts >= $this->_earliest) {
						zf_debug( 'Item within time frame', DBG_AGGR);
					} else {
						zf_debug( 'Item outside time frame', DBG_AGGR);
						continue;
					}
				} else if($this->_feedOptions->trimType == 'today') {
					if ($itemts < $todayts ) {
						zf_debug('Item is not from today. Skipped.'.$itemts.' lower than '.$todayts, DBG_AGGR);
						continue;
					}
				} else if($this->_feedOptions->trimType == 'yesterday') {
					if ($itemts >= $todayts || $itemts < $yesterdayts) {
						zf_debug('Item is not from yesterday. Skipped.', DBG_AGGR);
						continue;
					}
				} else if($this->_feedOptions->trimType == 'onlynew') {
					if ( ! $item->isnew ) {
						zf_debug('Item is not new/unseen. Skipped.', DBG_AGGR);
						continue;
					}
				}
				zf_debug( 'Item passes timeframe check', DBG_AGGR);
			}

			// finally add our item to the news array
			zf_debug('Item merged', DBG_AGGR);

			// dont use addItem not to call the filter callback again
			array_push($this->items, $item);

			$itemcount++;

		}// foreach item of feed
		zf_debug("Merged $itemcount item(s) from ".$feed->subscriptionId, DBG_AGGR);

	}


	/* matching function. check an expression against
		title and description of an item
		defaut: case insensitive keyword match
		could be regexp
		return true if match
		*/
	public function itemMatches($item) {
		$subject = strip_tags($item->title) . ' ' .strip_tags($item->description);
		//echo "checking ".$item->title']." for ". $exp.":".strpos(strtolower($subject), strtolower($exp))."<br/>";
		return !(strpos(strtolower($subject), strtolower($this->matchExpression))===false);
	}



	/* sort our aggregated items */
	public function sortItems() {
		zf_debug('sorting items', DBG_AGGR);

		/* sort by timestamp */
		usort($this->items, 'zf_compareItemsDate');

	}


}
/* end of Feed classes */

/* compare the date of two news items
used as callback in the sorting items call*/

function zf_compareItemsDate($a, $b) {
	return $a->date_timestamp < $b->date_timestamp;
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

