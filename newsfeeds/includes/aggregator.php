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

/*
TODO: in post process

					$tracker->markNewFeedItems($sub->id, $feed->items, $params['lastvisit'], time());
					$tracker->purge($sub->id);
*/

}


