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


abstract class AbstractFeed {
	// aggregated, normalized items
	// if we aggregate several feeds, index is numeric position in the array
	protected $items;
	public $last_fetched = 0;

	public function __construct() {
		$this->items = array();
	}

	public function addItem($item, $filterChain = null) {
		if ($filterChain) {
			if ($filterChain->acceptItem($item)) {
				$this->items[$item->id]= $item;
			}
		} else {
			$this->items[$item->id]= $item;
		}
	}

	/* get rid of superfluous items exceeding our limit, removing the bottom
	of the array, only by numbers (trimSize)*/
	public function trimItems($trimsize) {
		zf_debug("Keeping only last $trimsize items", DBG_AGGR);
		$this->items = array_slice($this->items, 0, $trimsize);
	}

	public function getItems($filterChain=null) {
		if ($filterChain) {
			$result = $filterChain->filter($this->items);
		} else {
			$result = $this->items;
		}
		return $result;
	}

	public function getItem($itemid, $filter=null) {

		if (isset($this->items[$itemid])) {
			$item = $this->items[$itemid];
			if ($filter) {
				// ignore return, we are not rejecting items in this case
				$item = $filter->accept($item);
			}
		} else {
			$item = NULL;
		}
		return $item;
	}

	public function filter($chain) {
		if ($chain && sizeof($chain)>0) {
			$this->items = $chain->filter($this->items);
		}
	}


	/* sort our items */
	public function sortItems() {
		zf_debug('sorting items', DBG_AGGR);

		/* sort by timestamp */
		usort($this->items, 'zf_compareItemsDate');

	}


}

/* publisher feed is obtained from the RSS/ATOM parser
can be trimmed to "shownitems" */
class PublisherFeed extends AbstractFeed {

	public $from_cache;
	public $subscriptionId;
	public $title;
	public $xmlurl;
	public $link;
	public $description;

	public function __construct($subId) {
		parent::__construct();
		$this->subscriptionId = $subId;
	}


	/* make sure our channel array has all what we need
	 this data will get cached, so this function is called only once,
	 right after the feed is fetched over http */
	public function normalize($title, $link, $xmlurl, $description){
		$this->title = $title;
		$this->description = $description;
		$this->xmlurl = $xmlurl;
		$this->link = $link;

	}

}

/* AggregatedFeed is made of publisher feeds, er... aggregated
can be trimmed to last X [news|days|hours] */
class AggregatedFeed extends AbstractFeed {


	/* this feed is an aggregation of feeds from a list
	   this method initializes this
	 */
	public function __construct($feeds, $filterChain) {

		parent::__construct();

		foreach($feeds as $pubfeed) {
	  		/* multiple approaches for merging

			loop here, accept individual items
			loop here, accept in the addItem method
			in-place filter feed to be merged, add all items in one go to items array
			get from feed filtered items list and append to array

			*/
			$itemsToMerge = $pubfeed->getItems();
			$this->items = array_merge($this->items, $itemsToMerge);
			zf_debug("Merged ".$pubfeed->subscriptionId, DBG_AGGR);
		}
		$this->sortItems();
		$this->filter($filterChain);

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

