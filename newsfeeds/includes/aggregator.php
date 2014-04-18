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
require_once($zf_path . 'includes/feed_cache.php');
require_once($zf_path . 'includes/view.php');
require_once($zf_path . 'includes/template.php');
require_once($zf_path . 'includes/history.php');


class aggregator {

	// output template of this aggregation
	public $_template;

	private $subscriptions;

	public $errorLog;

	// what will printMainView use, by feed or by date, or trimmed
	private $_mainViewMode;

	// feed options: how to trim for the Feed object
	private $_ViewOptions;
	private $_view;
	private $_currentTag;

	// private properties
	// last Feed object got from the parser, the next to be
	// aggregated
	private $_feed;

	/* array to help to track when the user came before */
	private $_visits;

	// timestamp of start of processing
	private $_now;

	public function __construct() {
		$this->_ViewOptions = new ViewOptions();
		$this->_ViewOptions->setTrim('auto', 0);
		$this->subscriptions = array();

		$this->_feed = null;
		$this->view = null;

		$this->errorLog = '';
		$this->_mainViewMode = 'feed';
		/* lastvisit= absolute last time seen here */
		$this->_visits['lastvisit'] = 0;
		/* lastsessionend= the time of end of previous session */
		$this->_visits['lastsessionend'] = 0;
		$this->_now = time();

		/* sets the default options
		Only used for printMainView
		can be date, feed or trim */
		$this->_mainViewMode = ZF_VIEWMODE;

		/* trimType will be news or days or hours */
		$this->_ViewOptions->setTrim(ZF_TRIMTYPE, ZF_TRIMSIZE);
	}

	/* AGGREGATOR CONFIGURATION ---------------------  */


	public function useDefaultTemplate(){
		/* set config template */
		$this->useTemplate(ZF_TEMPLATE);
	}

	public function useTemplate($templateName) {

		if ($this->view) unset($this->view);
		$this->view = new TemplateView($templateName);
	}

	public function useJSON() {
		if ($this->view) unset($this->view);
		$this->view = new JSONView();
	}


	public function useDefaultTag(){
		/* TODO get config tag*/
		$this->useTag();
	}


	public function useTag($tag='') {
		// true: only active
		$this->subscriptions = SubscriptionStorage::getInstance()->getActiveSubscriptions($tag,true);
		$this->_currentTag = $tag;
		zf_debug('loaded subscriptions', DBG_LIST);
		$cache = FeedCache::getInstance();
		$cache->update($this->subscriptions);
	}

	public function useSubscription($subId, $mode) {

		$sub = SubscriptionStorage::getInstance()->getSubscription($subId);
		$this->subscriptions[$sub->id]=$sub;
		
		$cache = FeedCache::getInstance();

		/* assign feed to $this->_feed;*/
		switch ($mode) {
			case 'auto':
				$cache->update(array($sub));
				break;
			case 'cache':
				break;
			case 'refresh':
				$cache->update(array($sub), true);
				break;
		}

		$this->_feed = $cache->get($sub->xmlurl);

	} 

	public function prepareFeed() {
		$this->_buildAggregatedFeed();

	}

	/* tells which ordering mode to use when viewing a list
	can override the list settings
	values= feed or date*/
	public function setViewMode($mode) {
		$this->_mainViewMode = $mode;
	}


	/* changes the trimming options. Also forces the view mode to trim,
	overruling the setting saved in the OPML list */
	public function setTrim($trimtype, $trimsize) {
		$this->_mainViewMode = 'trim';
		$this->_ViewOptions->setTrim($trimsize, $trimtype);
	}

	/* changes the trimming options. Also forces the view mode to trim */
	public function setTrimString($str) {
		zf_debug('trim set to:'. $str, DBG_AGGR);
		$this->setViewMode('trim');
		$this->_ViewOptions->setTrimStr($str);
	}


	/* AGGREGATOR OPERATION & RENDERING ---------------------  */

	
	/* main function, display the aggregator view
	   show and aggregated channels view, or a regular per-channel view
	   according to viewmode and if matching a keyword has been requested
	meant to be for an HTML page. shows errors and credit if configured*/
	public function printMainView() {

		if (count($this->subscriptions) > 0) {
			zf_debug('Viewmode:'. $this->_mainViewMode, DBG_RENDER);

			$this->view->renderHeader();

			// by date if not explicitly by feed
			if ($this->_mainViewMode != 'feed') {
				$this->_printFeedsByDate();
			} else {
				$this->_printFeedsByChannel();
			}
			$this->view->renderFooter();

		} else {
			$this->printStatus('No feeds');
		}
		// display errors at bottom. with a bit of JS trick, it could be at the top
		$this->printErrors();
		$this->printCredits();

	}


	/* render a "virtual" channel
	 create a feed aggregating all channels
	renders the feed by date */
	public function printTaggedFeeds($tag) {
		$this->useTag($tag);
		$this->_buildAggregatedFeed();

		$this->_printFeedsByDate();
	}

	/* renders a view showing news by channel. Traditional view (a la Yahoo)
	   channels of the tag are rendered by position order

	   AUTO fetch mode
	 */
	private function _printFeedsByChannel() {

		/*if we have feeds to display */
		$subs = $this->subscriptions;
		foreach($subs as $i => $subscription) {
			// change the array key to be the position
			$sortedChannels[$subscription->position] = $subscription;
		}
		ksort($sortedChannels);
		//print_r($sortedChannels);

		foreach($sortedChannels as $subscription) {
			zf_debug('printing feed'. $subscription->title, DBG_RENDER);
			if (trim($subscription->xmlurl) != '' && $subscription->shownItems > 0) {
				$feed = FeedCache::getInstance()->get($subscription->xmlurl);
				$this->printSingleSubscribedFeed($feed);
			}
		}
	}

	/* render a "virtual" channel
	 create a feed aggregating all channels
	renders the feed by date */
	private function _printFeedsByDate() {

		$this->view->addTags(array( 'tag' => $this->_currentTag));

		$this->view->addTags(array( 'publisherurl' => ZF_HOMEURL));
		$this->view->groupByDay = true;
		zf_debugRuntime("after aggregated view created");
		$this->view->renderFeed($this->_feed);
	}

	/* output a single channel, obtained by position
	 pos : position in the list
	 $mode: 'auto', 'refresh', 'cache'
	 $max: number of channel's items,
	 	0: auto from subscription list
	 	-1 is all
	 */
	public function printSingleFeed($channelId, $wantSummary) {
		/* get sub by pos from list */
		$sub = $this->subscriptions[$channelId];
		$feed = FeedCache::getInstance()->get($sub->xmlurl);
		$this->printSingleSubscribedFeed($feed, $wantSummary);
	}

	/* output a single channel, obtained by position
	 pos : position in the list
	 $mode: 'auto', 'refresh', 'cache'
	 $wantSummary: do we want summary included? default false

	 */
	public function printSingleSubscribedFeed($feed, $wantSummary=false) {


		//zf_debug("viewing channel ".$sub->__toString(), DBG_RENDER);

/*
	 if we get here, it's either
	 - async request: viewMode does not matter (feed or trim),
	                  trimType can be auto (if default) or news
	                -> if trimType is auto, trim feed to "$sub->shownItems" items
	                -> otherwise, trim feed to "$this->ViewOptions" items
	 - printMainView: viewMode is feed then trimType is auto
	                -> trim feed to "$this->ViewOptions" items


		(trimType,trimSize) is either
		 ('auto',0) => use subscription value for trimSize
			 OR
		 ('news', <N>) => trim to trimSize

*/
		if ($feed) {

			zf_debug('Trimming type '.$this->_ViewOptions->trimType, DBG_RENDER);
			//TODO fix this
			$this->_ViewOptions->trimType = 'none';
			switch ($this->_ViewOptions->trimType) {
				case 'auto':
					zf_debug('Trimming to subscription shownItems: '.$sub->shownItems, DBG_RENDER);
					$this->_feed->trimItems($sub->shownItems);
					break;
				case 'news':
					zf_debug('Trimming to requested nr of items: '.$this->_ViewOptions->trimSize, DBG_RENDER);
					$this->_feed->trimItems($this->_ViewOptions->trimSize);
					break;
				case 'none':
					zf_debug('No trimming', DBG_RENDER);
					break;

			}

			//TODO: use tag
			$this->view->addTags(array( 'list' => ''));

			// could become true if we wanted date grouping for every channel
			// will only be useful for TemplateView
			$this->view->groupByDay = false;

			//render with no channel header if requested and applicable
			$this->view->summaryInFeed = $wantSummary;
			$this->view->renderFeed($feed);
		} else {
			zf_debug('Internal error. no feed loaded.', DBG_AGGR);
		}

	}


  /* prints a single news description.
    Always taken from cache.
	$channelId: position of channel in list
	$itemid : our own item id
   */
	public function printArticle($channelId, $itemid) {

		/* get sub by pos from list */
		$sub = $this->subscriptions[$channelId];
		if ($sub) {
			zf_debug("printing article for ".$sub->__toString());
			/* create feedhandler and get feed, auto mode */
			$this->view->renderArticle($this->_feed, $itemid);
		}
	}

  /* prints a single news summary.
    Always taken from cache.
	$channelId: position of channel in list
	$itemid : our own item id
   */
	public function printSummary($channelId, $itemid) {

		/* get sub by pos from list */
		$sub = $this->subscriptions[$channelId];
		if ($sub) {
			zf_debug("printing articles for ".$sub->__toString());
			$this->view->renderSummary($itemid);
		}
	}

	/* Display a simple export of aggregated channels
	 create a feed aggregating all channels
	 renders the feed by date. It's a much stripped down version
	 of viewPage, that doesn't display error, nor credits
	doesn't set content type*/
	function printRSSFeed() {

		$this->_ViewOptions->trimType = "news";
		$this->_ViewOptions->trimSize = ZF_RSSEXPORTSIZE;

		$this->_buildAggregatedFeed();
		$view = new TemplateView("System.RSS");
		zf_debugRuntime("after aggregated view created");

		$view->addTags(array('encoding' => ZF_ENCODING,
			'publisherurl' => ZF_HOMEURL ));
		$view->renderFeed();
	}



	/* generate bottom line NOT USED IF JSON*/
	private function printCredits() {
		if ((!defined("ZF_SHOWCREDITS")) || (ZF_SHOWCREDITS!='no')) {
			echo ' <div id="generator">aggregated by <a href="http://www.cazalet.org/zebrafeeds">ZebraFeeds</a></div>';
		}

		zf_debugRuntime("after credits");
	}

	/*public function printStatus($message) {
	//TODO: handle JSON output -> NOT USED IF JSON
		echo '<div class="zfchannelstatus">'.$message.'</div>';
	}*/

	public function printErrors() {
		if ((ZF_DISPLAYERROR =="yes")  && (!empty($this->errorLog)) ) {
			$this->printStatus($this->errorLog);
		}
	}


	/* AGGREGATOR DATA PREPARATION */

	/*Feed object factory: build the feed object, ready to be used by the view
	will load all RSS objects from the channels list and merge
	them in a feed object, on which a pointer is returned
	 */
	private function _buildAggregatedFeed() {

		// create an empty, meant to be virtual, feed object
		// we'll merge all feeds containing actual data into it
		$this->_feed = new AggregatedFeed($this->_currentTag);
		$this->_feed->setTrim($this->_ViewOptions);

		$subs = $this->subscriptions;

// TODO: here apply parallel cURl fetch

// find out which of the subscriptions have expired

		$feedhandlers = array();
		foreach($subs as $sub) {

			$feed = FeedCache::getInstance()->get($sub->xmlurl);
			if($feed !=null ) {
				zf_debug('merging into Aggregated feed', DBG_AGGR);
				$this->_feed->mergeWith($feed);
			} else
				zf_debug("feed $sub is null", DBG_AGGR);

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
				zf_debug("Failed to open visit file for reading: $name", DBG_SESSION);
			} else {
				if ($filesize = filesize($name) ) {
					$data = fread( $fp, filesize($name) );
					$this->_visits = unserialize( $data );
				}
				zf_debug('last visit in server file: '.date('dS F Y h:i:s A', $this->_visits['lastvisit']), DBG_SESSION);
			}

		} else {

			// read visit time from cookie
			$this->_visits['lastvisit'] = $_COOKIE['lastvisit'];
			$this->_visits['lastsessionend'] = $_COOKIE['lastsessionend'];
			zf_debug('last visit in cookie: '.date('dS F Y h:i:s A', $this->_visits['lastvisit']), DBG_SESSION);
			zf_debug('last session end in cookie: '.date('dS F Y h:i:s A', $this->_visits['lastsessionend']), DBG_SESSION);

		}

		// if our last visit happened X seconds ago
		if ($this->_now - $this->_visits['lastvisit'] > ZF_SESSION_DURATION) {
			$this->_visits['lastsessionend'] = $this->_visits['lastvisit'];
			zf_debug("Session expired, last session end is now set to last visit", DBG_SESSION);

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
			zf_debug('writing last visit in cookie: '.date('dS F Y h:i:s A', $this->_visits['lastvisit'])." ($res1)", DBG_SESSION);
			zf_debug('writing last session end in cookie: '.date('dS F Y h:i:s A', $lastsessionend)." ($res2)", DBG_SESSION);

		}// ZF_NEWITEMS==server
	}

	public function getListNames() {
		$data=array();
		$handle = opendir(ZF_OPMLDIR);
		while($dirfile = readdir($handle)) {
			if (is_file(ZF_OPMLDIR.'/'.$dirfile) && substr($dirfile,strlen($dirfile)-4,strlen($dirfile))=='opml' ) {
				$data[] = substr($dirfile,0,strlen($dirfile)-5);
			}
		}
		sort($data);
		closedir($handle);
		return $data;
	}

}


