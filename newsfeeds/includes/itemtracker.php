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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
//
// ZebraFeeds tracker  class

/* what does this class do?
it stores the time every item was seen for the first time

*/



if (!defined('ZF_VER')) exit;

/* class for object holding the tracker of news items seen from a feed */

class ItemTracker {


	/* array of News id => { timestamp first time seen, current -ie seen in last fetch} */
	private $_timestamps;
	private $since;

	// id of subscription for which tracker is loaded
	private $loadedId = '';

	private static $instance = null;
	public static function getInstance() {
		if (self::$instance == null) {
			self::$instance = new ItemTracker();
		}
		return self::$instance;
	}

	private function __construct() {

		// array index: item id
		// data, array of "timestamp of first seen", "just seen" flag
		$this->_timestamps = array();
		$this->now = time();


	}

	public function setLastSessionEnd($end) {
		$this->since = $end;
	}

	protected function fileName($subId) {
		return ZF_HISTORYDIR.'/'.$subId.'.hst';

	}


	protected function save(){
		$filename = $this->fileName($this->loadedId);
		$fp = @fopen( $filename, 'w' );

		if ( ! $fp ) {
			zf_debug( "tracker unable to open file for writing: $filename", DBG_SESSION);
			return 0;
		}


		$data = serialize( $this->_timestamps );
		fwrite( $fp, $data );
		fclose( $fp );
		zf_debug( "tracker file saved", DBG_SESSION);
	}


	protected function load($subId){

		if ($this->loadedId == $subId) {
			zf_debug( "Tracker file in cache for $subId", DBG_SESSION);
			return;
		}


		$this->loadedId = $subId;

		$filename = $this->fileName($subId);
		if ( ! file_exists( $filename ) ) {
			zf_debug( "Tracker file not found $filename", DBG_SESSION);
			return 0;
		}

		$fp = @fopen($filename, 'r');
		if ( ! $fp ) {
			zf_debug( "Failed to open tracker file for reading: $filename", DBG_SESSION);
			return 0;
		}

		if ($filesize = filesize($filename) ) {
			$data = fread( $fp, filesize($filename) );
			$this->_timestamps = unserialize( $data );
			zf_debug( "Tracker file loaded $filename", DBG_SESSION);
			return 1;
		}
		zf_debug( "Failed to open tracker file: $filename", DBG_SESSION);

		return 0;

	}

	public function delete($subId){
		$filename = $this->fileName($subId);
		$res = unlink( $filename );

		if ( ! $res ) {
			zf_debug( "Unable to delete tracker file: $filename", DBG_SESSION);
			return false;
		}
		return true;


	}



	/* record the presence of the item, and set its date if needed
	 */
	public function checkIn($subId, $item) {
		zf_debug('Checking in item- '. $item->id. ' sub '. $subId, DBG_SESSION);

		$this->load($subId);

		if (!isset($this->_timestamps[$item->id])) {

			zf_debug('New item '.$item->id.' (storing date of first time seen)', DBG_SESSION);

			$this->_timestamps[$item->id]['ts'] = time();
		} else {
			zf_debug('Item is known, already logged.', DBG_SESSION);
		}
		// whatever case, mark this news as current, we saw it in the feed
		$this->_timestamps[$item->id]['current'] = true;

		if ($item->date_timestamp == 0) {
			zf_debug('Item has no date. Fixing this', DBG_SESSION);
			$item->date_timestamp = $this->_timestamps[$item->id]['ts'];
		}

	}


	// inform the tracker that this items has been seen and get the "New" status assigned
	public function checkNewStatus($subId, $item) {

		$this->load($subId);
		$id = $item->id;

		zf_debug('setting New status (current is '.($item->isNew?'NEW':'NOT NEW').') for item '.$id.": ".$item->title, DBG_SESSION);
		zf_debug('last session end '.date('dS F Y h:i:s A', $this->since), DBG_SESSION);

		// did we have this item in our DB?
		if (isset($this->_timestamps[$id])) {
			// found in our tracker DB
			// it's new if it appeared after our since time stamp reference

			zf_debug('item last checked in on '.date('dS F Y h:i:s A', $this->_timestamps[$id]['ts']), DBG_SESSION);

			if ($this->_timestamps[$id]['ts'] - $this->since > 0 ) {
				zf_debug($id.' => NEW', DBG_SESSION);
				$item->isNew = true;
			} else {
				zf_debug($id.' => OLD', DBG_SESSION);
				$item->isNew = false;
			}


		} else {
			/* should happen only if items have no date */
			zf_debug('/!\ item not found '.$item->title, DBG_SESSION);

		}


	}



	// takes the list of items, and delete those
	// not seen during the last marking round
	public function purge() {


		// for each ids from our table
		// if id has not been seen, delete it
		// if id has been seen, mark it as unseen for next time
		// save

		$cnt = count($this->_timestamps);
		$tmpts = $this->_timestamps;

		//echo "<code>"; print_r($tmpts); echo "</code>";
		zf_debug($cnt. " news to check for purge on " . $this->loadedId, DBG_SESSION);

		foreach ($tmpts as $id => $entry) {

			if (!$entry['current']) {
				//zf_debug('news is not current. deleting from tracker');
				unset ($this->_timestamps[$id]);
			} else {
				//zf_debug('news is current, keeping it in tracker');
				/* reset the 'current' flag before save, so that it comes reset at next load */
				$this->_timestamps[$id]['current'] = false;
			}

		}
		zf_debug(count($this->_timestamps). " news left after purge on " .$this->loadedId, DBG_SESSION);
		$this->save();

	}

}

