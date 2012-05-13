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
this is the controller class (sort of)
- figure out where to get a list of channels from 
- aggregate those channels according to options: by channel, by date, trim...
  using a template
- display news from a single channel
- display content from a single news

this class shouldn't deal with refreshing feeds. it uses a single function 
to load a feed from a channel. 
how, how often this feed is refreshed is handled elsewhere.


list of channel object, some are real, some are made of

channel['type'] = 'real' or 'virtual'
if real : regular array
if virtual, has a list element, full of regular channel arrays
channel['list']


 */

if (!defined('ZF_VER')) exit;

require_once($zf_path . 'includes/feed.php');
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
	public $list;

	public $errorLog;

	// feed options: only when viewmode is not feed
	// array with
	// - trimtype
	// - trimsize
	// - userfunction
	// - matchexpression

	private $_feedOptions;
	public $viewMode;

	// private properties
	// last Feed object got from the parser, the next to be 
	// aggregated
	private $_feed;

	/* array to help to track when the user came before */
	private $_visits; 

	// timestamp of start of processing
	private $_now;

	public function __constructor() {
		$this->_feedOptions = array();
		$this->channels = array();
		$this->list = null;

		$this->_feed = null;
		$this->_template = null;
		$this->errorLog = '';
		$this->_viewMode = 'feed';
		$this->_feedOptions['userfunction'] = '';
		$this->_feedOptions['trimtype'] = 'none';
		/* lastvisit= absolute last time seen here */
		$this->_visits['lastvisit'] = 0;
		/* lastsessionend= the time of end of previous session */
		$this->_visits['lastsessionend'] = 0;
		$this->_now = time();
	}

	public function useList(&$subscriptionsList) {
		// record the information saying that this channel list
		// actually comes from a subscription list, not from 
		// a zf_addFeed call
		$this->list = &$subscriptionsList;
		$this->channels = &$subscriptionsList->channels;


		/* sets the default options from the list*/
		$this->_viewMode = $this->list->options['viewmode'];		

		//we set this only if we have to trim. otherwise we might be forcing an unwanted trimming
		if ($this->_viewMode == 'trim') {
			$this->_feedOptions['trimtype'] = $this->list->options['trimtype'];
			$this->_feedOptions['trimsize'] = $this->list->options['trimsize'];
		}
	}

	/* behavior settings */
	public function setViewMode($mode) {
		$this->_viewMode = $mode;
	}


	public function filterChannelPos($posString) {
		/* TODO: allow multiple positions */
		// so far, only one:

		$newchannels = array();
		for ($i=0; $i<count($this->channels); $i++) {
			if ($this->channels[$i]['position'] == $posString) {
				$newchannels[] = &$this->channels[$i];
			}
		}
		$this->channels = &$newchannels;

	}

	/* changes the trimming options. Also forces the view mode to trim */
	public function setTrimOptions($trimtype, $trimsize) {
		$this->_viewMode = 'trim';
		$this->_feedOptions['trimsize'] = $trimsize;
		$this->_feedOptions['trimtype'] = $trimtype;
	}

	public function matchNews($expression) {
		$this->_feedOptions['match'] = $expression;
	}

	public function useTemplate(&$template) {
		$this->_template = &$template;
	}

	/* main function, display the aggregator view 
	   show and aggregated channels view, or a regular per-channel view
	   according to viewmode and if matching a keyword has been requested
	meant to be for an HTML page. shows errors and credit if configured*/
	public function viewPage() {
		if (count($this->channels) > 0) {
			$this->_template->printHeader();
			zf_debug('Viewmode:'. $this->_viewMode);


			// sort if not by feed or if we want to match a string
			if (($this->_viewMode != 'feed') || isset($this->_feedOptions['match'])) {
				$this->viewAggregatedChannels();
			} else {
				$this->viewChannels();
			}
			$this->_template->printFooter();
		} else {
			$this->displayStatus('No feeds');
		}
		// display errors at bottom. with a bit of JS trick, it could be at the top
		$this->displayErrors();
		$this->displayCredits();

	}

	/* renders a view showing news by channel. Traditional view (a la Yahoo) 
	   channels of the channels list are rendered by position order
	 */
	public function viewChannels() {

		/*if we have feeds to display */
		foreach($this->channels as $i => $channeldata) {
			if ($channeldata['xmlurl'] != '') {
				// change the array key to be the position
				$sortedChannels[$channeldata['position']] = $channeldata;
			}
		}
		ksort($sortedChannels);
		//print_r($sortedChannels);

		foreach($sortedChannels as $channeldata) {
			if ($channeldata['issubscribed'] == "yes") {
				if (isset($channeldata['xmlurl']) && trim($channeldata['xmlurl']) != '' && $channeldata['showeditems'] > 0) {
					if ($this->loadFeed($channeldata)) {
							$this->viewSingleChannel();
					}
				}
			}
		}
	}


	/* renders a single real channel, the old way: channel header, 
	all items, natural order, only a max number of items
	optionally, we can render only the items, no header (this is for ajax calls)
	 */
	public function viewSingleChannel($viewAll = false, $onlyItems = false) {
		zf_debug("viewing channel ".$this->_feed->channel['xmlurl']);

		$feedOptions = array();
		if ($viewAll) {
			$this->_feed->setTrim('none');
			//$feedOptions['trimtype'] = 'none';
		} else {
			$this->_feed->setTrim('news', $this->_feed->showedItems);
			//$feedOptions['trimtype'] = 'news';
			//$feedOptions['trimsize'] = ;			  
		}

		if ($this->_feed != null) {
			// true: we want sorting
			$this->_feed->postProcess(true);			
			if ($this->list != null) {
				$this->_template->addTags(array( 'list' => $this->list->name));
			}  else {
				$this->_template->addTags(array( 'list' => ''));
			}

			$view = new view($this->_template, $this->_feed);

			// could become true if we wanted date grouping for every channel
			$view->groupByDay = false;

			//render with no channel header if requested
			if ($onlyItems) {
				$view->renderNewsItems();
			} else {
				$view->render();
			}
		} 
		else {
			zf_debug('Internal error. no feed loaded.');			
		}
	}

	/* AggregatedChannels: render a "virtual" channel
	 create a feed aggregating all channels
	renders the feed by date */
	public function viewAggregatedChannels() {

		//consistency check
		if ($this->_viewMode != 'trim') {
			$this->_feedOptions['trimtype'] = 'none';
		}

		$feed = &$this->makeFeed();
		if ($this->list != null) {
			//configure template to remove unhandled/unwanted buttons
			$this->_template->addTags(array( 'list' => $this->list->name));
		}  else {
			$this->_template->addTags(array( 'list' => ''));
		} 

		$this->_template->addTags(array( 'publisherurl' => ZF_HOMEURL)); 
		$view = new view($this->_template, $feed);
		$view->groupByDay = true;
		zf_debugRuntime("after aggregated view created");
		$view->render();
	}

	/* Display a simple export of aggregated channels
	 create a feed aggregating all channels
	 renders the feed by date. It's a much stripped down version
	 of viewPage, that doesn't display error, nor credits
	doesn't set content type*/
	function exportAggregatedChannels() {

		$this->_feedOptions['trimtype'] = "news";
		$this->_feedOptions['trimsize'] = ZF_RSSEXPORTSIZE;

		$feed = &$this->makeFeed();
		$view = new view($this->_template, $feed);
		zf_debugRuntime("after aggregated view created");

		$this->_template->addTags(array('encoding' => ZF_ENCODING,
			'publisherurl' => ZF_HOMEURL ));
		$this->_template->printHeader();
		$view->render();
		$this->_template->printFooter();
	}



  /* prints a single news description, peeking from the last
	feed loaded. Does not use the template.
	Is called up on ajax requests
	$itemid : our own item id, generated internally
	$single : if true, we must use the appropriate template section
	instead of returning the bare news content
   */
	public function printItemContent($itemid) {

		/* force use of cache */

		if ( $this->_feed != null ) {
			zf_debug("checking ".$this->_feed->channel['xmlurl']." for ".$itemid);
			// let's lookup which item we want
			foreach ($this->_feed->items as $item) {
				// the news item
				$thisId = zf_makeId($this->_feed->channel['xmlurl'], $item['link'].$item['title']);
				// is it the one ?
				zf_debug('checking item with id '.$thisId);
				if ( $thisId == $itemid ) {

					zf_debug('Item Matches');
					// HERE, we could/should use channel title/description

					/* use the template if we are asked to */

					$this->_template->printArticle($item);
					return;
				}
			}
			echo  "Content not available for ".$this->_feed->channel['xmlurl']." (".$itemid.")";
		} 
	}


	/*Feed object factory: build the feed object, ready to be used by the view
	will load all RSS objects from the channels list and merge 
	them in a feed object, on which a reference is returned
	 */
	public function makeFeed() {

		// use channel array for sources.

		// create an empty, meant to be virtual, feed object
		// we'll merge all feeds containing actual data into it
		$feed = new feed();
		$feed->setTrim($this->_feedOptions['trimtype'], $this->_feedOptions['trimsize']);

		// if we are making a feed for a list, we have to initialize it's channel 
		// data structure, since it's not obtained from a real RSS feed.
		if ($this->list != null) {
			$feed->initVirtual($this->list->name);
		}

		foreach($this->channels as $channel) {
			if ($channel['issubscribed'] == "yes") {
				if (isset($channel['xmlurl']) && trim($channel['xmlurl']) != '' ) {
					if ($this->loadFeed($channel) ) {
						$feed->mergeWith($this->_feed);							   
					}
				}
			}
		}
		$feed->postProcess();
		return $feed; 
	}





	/* Load the actual feed from a channel 
	  (either from cache, or refresh from publisher)
	 and make it available to other methods.
	 wrapper around fetch_rss to enrich the rss array we get from magpieRSS 
	 if successfull, the RSS object carries then all channel information

	return true if the feed was obtained, otherwise false*/
	public function loadFeed($channeldata, $refreshtime = 'default', $ignorehistory = false) {
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

		$usedrefreshtime = $refreshtime;
		if (!is_numeric($refreshtime) && $refreshtime == 'default') {
			zf_debug("requesting default refresh time for ".$channeldata['xmlurl']);
			$usedrefreshtime = (ZF_REFRESHMODE == 'automatic')? $channeldata['refreshtime']: -1;
		} 

		zf_debug("Refresh mode: ". ZF_REFRESHMODE." ; Refreshtime : $refreshtime ; used refresh time: $usedrefreshtime");


		if (!$ignorehistory) {
			$channeldata['history'] = new history($channeldata['xmlurl']);
		}

		// QUICK DEBUG $refreshtime = -1;

		zf_debugRuntime("before fetch ".$channeldata['xmlurl']);
		$resultString = '';
		$this->_feed = zf_fetch_rss( $channeldata, $usedrefreshtime, $resultString );
		zf_debugRuntime("after fetch ".$channeldata['xmlurl']);

		if ($this->_feed) {
			// append our o/replace data that magpie gave us
			// with our own configuration
			$this->_feed->refreshTime= $channeldata['refreshtime'];
			$this->_feed->showedItems = $channeldata['showeditems'];

			// compare each item id with our fetch history, for this feed		 
			// mark new items as such
			if ( !$ignorehistory) {

				$channeldata['history']->handleCurrentItems($this->_feed->items, 
					$this->_visits['lastsessionend'], 
					$this->_now);
				// delete unseen items from db
				$channeldata['history']->purge();
			}

			//print_r($this->_feed->items);

		}

		// in case of Error
		// get error reason from zf_custom_fetch_rss
		if (strlen($resultString)>0) {
			// user friendly channel title 
			//$channelTitle = isset($channeldata['title']) ? $channeldata['title']:$channeldata['xmlurl'];
			$this->errorLog .= $resultString.'<br/>';
		}

		return ($this->_feed != null);
	}

	/* generate bottom line */
	public function displayCredits() {
		if ((!defined("ZF_SHOWCREDITS")) || (ZF_SHOWCREDITS!='no')) {
			echo ' <div id="generator">aggregated by <a href="http://www.cazalet.org/zebrafeeds">ZebraFeeds</a></div>';
		}

		zf_debugRuntime("after credits");
	}

	public function displayStatus($message) {
		echo '<div class="zfchannelstatus">'.$message.'</div>';
	}

	public function displayErrors() {
		if ((ZF_DISPLAYERROR =="yes")  && (!empty($this->errorLog)) ) {
			$this->displayStatus($this->errorLog);
		}
	}

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

			$name = ZF_DATADIR.'/visit.txt';

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

}



?>
