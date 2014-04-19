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

		/* lastvisit= absolute last time seen here */
		$this->_visits['lastvisit'] = 0;
		/* lastsessionend= the time of end of previous session */
		$this->_visits['lastsessionend'] = 0;
		$this->_now = time();


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


