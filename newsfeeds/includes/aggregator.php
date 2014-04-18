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
	/* array to help to track when the user came before */
	private $_visits;

	// timestamp of start of processing
	private $_now;

	public function __construct() {
		//$this->_ViewOptions->setTrim('auto', 0);
		/* lastvisit= absolute last time seen here */
		$this->_visits['lastvisit'] = 0;
		/* lastsessionend= the time of end of previous session */
		$this->_visits['lastsessionend'] = 0;
		$this->_now = time();


		/* trimType will be news or days or hours */
		//$this->_ViewOptions->setTrim(ZF_TRIMTYPE, ZF_TRIMSIZE);
	}

	/* AGGREGATOR CONFIGURATION ---------------------  */


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


}


