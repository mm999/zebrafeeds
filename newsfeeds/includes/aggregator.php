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
require_once($zf_path . 'includes/feed.php');
require_once($zf_path . 'includes/feedhandler.php');
require_once($zf_path . 'includes/view.php');
require_once($zf_path . 'includes/template.php');
require_once($zf_path . 'includes/history.php');
require_once($zf_path . 'includes/fetch.php');


class aggregator {

	// output template of this aggregation
	public $_template;

	// array of channels we have to get the feeds for
	// might come from an OPML list, or not
	public $channels;

	// if the aggregator use a list to get its channels from,
	// here is the list. In this case $this->channels is a reference to
	// $list->channels
	public $subscriptions;

	public $errorLog;

	// feed options: only when viewmode is not feed
	// array with
	// - trimtype
	// - trimsize
	// - userfunction
	// - matchexpression

	private $_feedOptions;
	private $_viewMode;
	private $_view;

	// private properties
	// last Feed object got from the parser, the next to be
	// aggregated
	private $_feed;

	/* array to help to track when the user came before */
	private $_visits;

	// timestamp of start of processing
	private $_now;

	public function __construct() {
		$this->_feedOptions = new FeedOptions();
		$this->channels = array();
		$this->list = null;

		$this->_feed = null;
		$this->_template = null;
		$this->view = null;

		$this->errorLog = '';
		$this->_viewMode = 'feed';
		$this->_feedOptions->trimType = 'none';
		/* lastvisit= absolute last time seen here */
		$this->_visits['lastvisit'] = 0;
		/* lastsessionend= the time of end of previous session */
		$this->_visits['lastsessionend'] = 0;
		$this->_now = time();
	}

	/* AGGREGATOR CONFIGURATION ---------------------  */


	public function useDefaultTemplate(){
		/* set config template */
		$this->useTemplate(ZF_TEMPLATE);
	}

	public function useTemplate($templateName) {
		$this->_template = new template($templateName);

		if ($this->view) unset($this->view);
		$this->view = new TemplateView($this->_template);
	}

	public function useJSON() {
		if ($this->view) unset($this->view);
		$this->view = new JSONView();
	}


	public function useDefaultList(){
		/* set config template */
		$this->useList(ZF_HOMELIST);
	}

	public function useList($listName) {

		if (ZF_USEOPML) {

			$subscriptionsList = new opml($listName);
			if ($subscriptionsList->load()) {
				zf_debug('loaded list '.$listName);
				// record the information saying that this channel list
				// actually comes from a subscription list, not from
				// a zf_addFeed call
				$this->list = $subscriptionsList;

				/* sets the default options from the list*/
				$this->_viewMode = $this->list->viewMode;

				//we set this only if we have to trim. otherwise we might be forcing an unwanted trimming
				if ($this->_viewMode == 'trim') {
					$this->_feedOptions->trimType = $this->list->trimType;
					$this->_feedOptions->trimSize = $this->list->trimSize;
				}
			} else {
				echo '<strong>'.$subscriptionsList->lastError.'<br />Make sure OPML file exists and is readable...</strong>';
			}
		}
	}


	/* behavior settings */
	public function setViewMode($mode) {
		$this->_viewMode = $mode;
	}


	/* changes the trimming options. Also forces the view mode to trim,
	overruling the setting saved in the OPML list */
	public function setTrim($trimtype, $trimsize) {
		$this->_viewMode = 'trim';
		$this->_feedOptions->trimSize = $trimsize;
		$this->_feedOptions->trimType = $trimtype;
	}

	/* changes the trimming options. Also forces the view mode to trim */
	public function setTrimString($str) {
		$this->setViewMode('trim');
		$this->_feedOptions->setTrimStr($str);
	}


	public function matchNews($expression) {
		$this->_feedOptions->matchExpression = $expression;
	}


	/* AGGREGATOR OPERATION & RENDERING ---------------------  */

	/* main function, display the aggregator view
	   show and aggregated channels view, or a regular per-channel view
	   according to viewmode and if matching a keyword has been requested
	meant to be for an HTML page. shows errors and credit if configured*/
	public function printMainView() {

		if (count($this->list->subscriptions) > 0) {
			$this->_template->printHeader();
			zf_debug('Viewmode:'. $this->_viewMode);


			// sort if not by feed or if we want to match a string
			if (($this->_viewMode != 'feed') || !empty($this->_feedOptions->matchExpression)) {
				$this->printListByDate();
			} else {
				$this->printListByChannel();
			}
			$this->_template->printFooter();
		} else {
			$this->printStatus('No feeds');
		}
		// display errors at bottom. with a bit of JS trick, it could be at the top
		$this->printErrors();
		$this->printCredits();

	}

	/* renders a view showing news by channel. Traditional view (a la Yahoo)
	   channels of the channels list are rendered by position order
	 */
	private function printListByChannel() {

		/*if we have feeds to display */
		$subs = $this->list->subscriptions;
		foreach($subs as $i => $subscription) {
			if ($subscription->channel->xmlurl != '') {
				// change the array key to be the position
				$sortedChannels[$subscription->position] = $subscription;
			}
		}
		ksort($sortedChannels);
		//print_r($sortedChannels);

		foreach($sortedChannels as $subscription) {
			if ($subscription->isSubscribed) {
				if (isset($subscription->channel->xmlurl) && trim($subscription->channel->xmlurl) != '' && $subscription->shownItems > 0) {

					/* create feedhandler and get feed, auto mode */
					$handler = new FeedHandler($subscription);
					/* assign feed to $this->_feed;*/
					$this->_feed = $handler->getAutoFeed();
					$this->_printChannel($subscription, false, false);
				}
			}
		}
	}

	/* render a "virtual" channel
	 create a feed aggregating all channels
	renders the feed by date */
	private function printListByDate() {

		//consistency check
		if ($this->_viewMode != 'trim') {
			$this->_feedOptions->trimType = 'none';
		}

		$this->buildAggregatedFeed();
		if ($this->list != null) {
			//configure template to remove unhandled/unwanted buttons
			$this->view->addTags(array( 'list' => $this->list->name));
		}  else {
			$this->view->addTags(array( 'list' => ''));
		}

		$this->view->addTags(array( 'publisherurl' => ZF_HOMEURL));
		$this->view->groupByDay = true;
		zf_debugRuntime("after aggregated view created");
		$this->view->useFeed($this->_feed);
		$this->view->renderFeed();
	}

	/* configured number of channel's items, with header, auto cache/refresh*/
	public function printSingleChannel($pos) {

		/* get sub by pos from list */
		$sub = $this->list->getSubscription($pos);
		if ($sub) {
			//print $sub->__toString();
			/* create feedhandler and get feed, auto mode */
			$handler = new FeedHandler($sub);
			/* assign feed to $this->_feed;*/
			$this->_feed = $handler->getAutoFeed();
			$this->_printChannel($sub, false, false);
		}
	}

	/* all channel's items, no header, from cache */
	public function printAllCachedItems($pos) {

		/* get sub by pos from list */
		$sub = $this->list->getSubscription($pos);
		if ($sub) {
			zf_debug("loading all cached items for ".$sub->__toString());
			/* create feedhandler and get feed, auto mode */
			$handler = new FeedHandler($sub);
			/* assign feed to $this->_feed;*/
			$this->_feed = $handler->getFeedFromCache();
			$this->_printChannel($sub, true, true);
		}
	}

	/* prints configured number of channel's items, no header, force refresh */
	public function printRefreshedItems($pos) {

		/* get sub by pos from list */
		$sub = $this->list->getSubscription($pos);
		if ($sub) {
			/* create feedhandler and get feed, auto mode */
			$handler = new FeedHandler($sub);
			/* assign feed to $this->_feed;*/
			$this->_feed = $handler->getRefreshedFeed();
			$this->_printChannel($sub, false, true);
		}
	}

	/* renders a single real channel, the old way: channel header,
	natural order, only a max number of items
	viewAll: if true, as many items as possible
	onlyItems: if true, does not show channel header
	 */
	private function _printChannel($subscription, $viewAll = false, $onlyItems = false) {
		zf_debug("viewing channel ".$subscription->__toString());

		if ($this->_feed != null) {

			if (!$viewAll) {
				zf_debug('Triming to items: '.$subscription->shownItems);
				$this->_feed->trimItems($subscription->shownItems);
			}
			// true: we want sorting
			//$this->_feed->postProcess(true);
			if ($this->list != null) {
				$this->view->addTags(array( 'list' => $this->list->name));
			}  else {
				$this->view->addTags(array( 'list' => ''));
			}

			$this->view->useFeed($this->_feed);

			// could become true if we wanted date grouping for every channel
			$this->view->groupByDay = false;

			//render with no channel header if requested and applicable
			if ($onlyItems) {
				$this->view->renderNewsItems();
			} else {
				$this->view->renderFeed();
			}
		} else {
			zf_debug('Internal error. no feed loaded.');
		}
	}

	/* Display a simple export of aggregated channels
	 create a feed aggregating all channels
	 renders the feed by date. It's a much stripped down version
	 of viewPage, that doesn't display error, nor credits
	doesn't set content type*/
	function printRSSFeed() {

		$this->_feedOptions->trimType = "news";
		$this->_feedOptions->trimSize = ZF_RSSEXPORTSIZE;

		$this->buildAggregatedFeed();
		$view = new TemplateView($this->_template);
		zf_debugRuntime("after aggregated view created");

		$view->addTags(array('encoding' => ZF_ENCODING,
			'publisherurl' => ZF_HOMEURL ));
		$this->_template->printHeader();
		$view->render();
		$this->_template->printFooter();
	}



  /* prints a single news description.
    Always taken from cache.
	$pos: position of channel in list
	$itemid : our own item id
   */
	public function printArticle($pos, $itemid) {

		/* get sub by pos from list */
		$sub = $this->list->getSubscription($pos);
		if ($sub) {
			zf_debug("printing articles for ".$sub->__toString());
			/* create feedhandler and get feed, auto mode */
			$handler = new FeedHandler($sub);
			/* assign feed to $this->_feed;*/
			$this->_feed = $handler->getFeedFromCache();
			$this->view->useFeed($this->_feed);
			$this->view->renderArticle($itemid);
		}
	}


	/* generate bottom line */
	private function printCredits() {
	//TODO: handle JSON output
		if ((!defined("ZF_SHOWCREDITS")) || (ZF_SHOWCREDITS!='no')) {
			echo ' <div id="generator">aggregated by <a href="http://www.cazalet.org/zebrafeeds">ZebraFeeds</a></div>';
		}

		zf_debugRuntime("after credits");
	}

	public function printStatus($message) {
	//TODO: handle JSON output
		echo '<div class="zfchannelstatus">'.$message.'</div>';
	}

	public function printErrors() {
	//TODO: handle JSON output
		if ((ZF_DISPLAYERROR =="yes")  && (!empty($this->errorLog)) ) {
			$this->printStatus($this->errorLog);
		}
	}


	/* AGGREGATOR DATA PREPARATION */

	/*Feed object factory: build the feed object, ready to be used by the view
	will load all RSS objects from the channels list and merge
	them in a feed object, on which a pointer is returned
	 */
	private function buildAggregatedFeed() {

		// use subscription array from list as source.
		//

		// create an empty, meant to be virtual, feed object
		// we'll merge all feeds containing actual data into it
		$this->_feed = new AggregatedFeed($this->list);
		$this->_feed->setTrim($this->_feedOptions->trimType, $this->_feedOptions->trimSize);

		$subs = $this->list->subscriptions;

		foreach($subs as $sub) {
			if ($sub->isSubscribed) {
				/* create feedhandler and get feed, auto mode */
				$handler = new FeedHandler($sub);
				/* assign feed to $this->_feed;*/
				$feed = $handler->getAutoFeed();
				if($feed !=null ) {
					$this->_feed->mergeWith($feed);
				} else
					zf_debug("feed is null");
			}
		}
		$this->_feed->postProcess();
	}




	/* HISTORY MGNT */

	// fronts to the recordVisit method
	// as they are not called from the same place
	// whether set as client or server
	public function recordServerVisit() {
		if (ZF_NEWITEMS=='server') {
			$this->_recordVisit();
		}
	}

	public function recordClientVisit() {
		if (ZF_NEWITEMS=='client') {
			$this->_recordVisit();
		}
	}

	// store the current time
	private function _recordVisit() {

		// 1: read visit information

		$this->_visits['lastvisit'] = 0;
		$this->_visits['lastsessionend'] = 0;

		if (ZF_NEWITEMS=='server') {

			$name = ZF_HISTORYDIR.'/visit.txt';

			$fp = @fopen($name, 'r');
			if ( ! $fp ) {
				if (ZF_DEBUG==7) {
					zf_debug("Failed to open visit file for reading: $name");
				}
			} else {
				if ($filesize = filesize($name) ) {
					$data = fread( $fp, filesize($name) );
					$this->_visits = unserialize( $data );
				}
				if (ZF_DEBUG == 7 ) {
					zf_debug('last visit in server file: '.date('dS F Y h:i:s A', $this->_visits['lastvisit']));
				}
			}

		} else {

			// read visit time from cookie
			$this->_visits['lastvisit'] = $_COOKIE['lastvisit'];
			$this->_visits['lastsessionend'] = $_COOKIE['lastsessionend'];
			if (ZF_DEBUG == 7 ) {
				zf_debug('last visit in cookie: '.date('dS F Y h:i:s A', $this->_visits['lastvisit']));
				zf_debug('last session end in cookie: '.date('dS F Y h:i:s A', $this->_visits['lastsessionend']));
			}

		}

		// if our last visit happened X seconds ago
		if ($this->_now - $this->_visits['lastvisit'] > ZF_SESSION_DURATION) {
			$this->_visits['lastsessionend'] = $this->_visits['lastvisit'];
			if (ZF_DEBUG==7) {
				zf_debug("Session expired, last session end is now set to last visit");
			}

		}
		//echo date('dS F Y h:i:s A', $this->_now) . ' - '. date('dS F Y h:i:s A', $this->_visits['lastvisit']);
		$this->_visits['lastvisit'] = $this->_now;

		//STEP 2: record visit time
		if (ZF_NEWITEMS=='server') {
			// write visit information
			$fp = @fopen( $name, 'w' );

			if ( ! $fp ) {
				zf_debug("History unable to open visit file for writing: $name");

			} else {
				$data = serialize( $this->_visits );
				fwrite( $fp, $data );
				fclose( $fp );
			}
		} else {
			// write visit info to cookie
			$expire = time()+ZF_VISITOR_COOKIE_EXPIRATION;
			$res1 = setcookie('lastvisit', $this->_visits['lastvisit'], $expire);
			// save last session end to cookie, but keep its value in our array
			/*if ($this->_visits['lastsessionend'] == 0) {
				$lastsessionend = $this->_visits['lastvisit'];
			} else {*/
			$lastsessionend = $this->_visits['lastsessionend'];
			/*}*/
			$res2 = setcookie('lastsessionend', $this->_visits['lastsessionend'], $expire);
			if (ZF_DEBUG == 7 ) {
				zf_debug('writing last visit in cookie: '.date('dS F Y h:i:s A', $this->_visits['lastvisit'])." ($res1)");
				zf_debug('writing last session end in cookie: '.date('dS F Y h:i:s A', $lastsessionend)." ($res2)");
			}

		}// ZF_NEWITEMS==server
	}
	
	public function getFeedItems() {
		return $this->_feed->items;
	}

	public function getFeedItems() {
		return $this->_feed->items;
	}

}



?>
