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

/* what does this class do?
this is the main facade class
- Configure the aggregation (template, source list, processing and rendering options
- aggregation and data preparation
- rendering of views

 */

if (!defined('ZF_VER')) exit;

require_once($zf_path . 'includes/classes.php');
require_once($zf_path . 'includes/history.php');


class aggregator {

	public function __construct() {

	}


	/*
	feeds: array of feeds to handle
	trim: trim parameters on request
	aggregate: if true, must aggregate all feeds in one
	onlyNew: if true, will keep only new items

	$result: feed or array of feeds, in function of aggregate
	 */
	public function processFeeds($feeds, $trim, $aggregate, $onlyNew) {

		if ($aggregate) {
			$feedParams = new FeedParams($trim);
			$feedParams->onlyNew = $onlyNew;
			$aggrfeed = new AggregatedFeed($feeds, $feedParams);
			//post process of aggregated feed took place during aggregation
			return $aggrfeed;

		} else {

			foreach($feeds as $feed) {
				$feedParams = SubscriptionStorage::getInstance()->getSubscription($feed->subscriptionId)->getFeedParams();
				$feedParams->setTrimStr($trim);
				$feedParams->onlyNew = $onlyNew;
				$feed->postProcess($feedParams);
			}
			return $feeds;
		}
	}


}


