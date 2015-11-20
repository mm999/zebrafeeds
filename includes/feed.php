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



class Feed {
	// aggregated, normalized items
	// if we aggregate several feeds, index is numeric position in the array
	public $items;

	public function __construct() {
		$this->items = array();
	}
	
	public function addItem($item) {
		$this->items[] = $item;
	}

}

/* publisher feed is obtained from the RSS/ATOM parser
can be trimmed to "shownitems" */
class PublisherFeed extends Feed {

	public $last_fetched = 0;
	public $source;
	public function __construct($source) {
		parent::__construct();
		$this->source = $source;
	}


}

/* end of Feed classes */

