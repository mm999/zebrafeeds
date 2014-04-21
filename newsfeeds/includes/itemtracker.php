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
    }


    protected function load($subId){

        if ($this->loadedId == $subId) {
            zf_debug( "Tracker file already loaded for $subId", DBG_SESSION);
            return;
        }


        $this->loadedId = '';
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
            $this->loadedId = $subId;
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



    /* returns the timestamp of the first time the item was seen in the DB
     * called during caching of RSS object
     */
    public function getDateFirstSeen($subId, $itemId) {
    	zf_debug($subId .': getting time first seen for item- '. $itemId, DBG_SESSION);

        $this->load($subId);

        if (!isset($this->_timestamps[$itemId])) {

            zf_debug('New item '.$itemId.' (getting date of first time seen)', DBG_SESSION);

            $this->_timestamps[$itemId]['ts'] = time();
        }
        return $this->_timestamps[$itemId]['ts'];

    }

    /* mark new items and register still existing items as current
    * $items: array from the Feed object
    * $since: time stamp to tell when to go back in time from
    * $now: compare at timestamp X
    */
    public function markNewFeedItems($subId, $items, $since) {
        zf_debug($subId .': marking new items ', DBG_SESSION);

        $this->load($subId);

        $now = time();
		zf_debug($subId .': marking items newer than: '.date('dS F Y h:i:s A', $since), DBG_SESSION);
		zf_debug('now it\'s '.date('dS F Y h:i:s A', $now), DBG_SESSION);
        // for each item
        // if item in timestamps table
        //   check 'timestamp' against 'now'
        //   if item is new
        //      calculate id
        //      mark id as seen
        //   otherwise, mark it as new
        foreach($items as $item) {

            //$id = zf_makeId('',$items[$i]['link']);
            $id = $item->id;

			zf_debug('checking item '.$id.": ".$item->title, DBG_SESSION);

			// did we have this item in our DB?
            if (isset($this->_timestamps[$id])) {
                // found in our tracker DB
                // it's new if it appeared after our since time stamp reference

                if (ZF_NEWITEMS != 'no') {
                    zf_debug('item last seen on '.date('dS F Y h:i:s A', $this->_timestamps[$id]['ts']), DBG_SESSION);

                    if ($this->_timestamps[$id]['ts'] - $since > 0 ) {
                        zf_debug('=> new unseen item', DBG_SESSION);
                        $item->isNew = true;
                    } else {
                    	zf_debug('=> This is old news', DBG_SESSION);
                        $item->isNew = false;
                    }
                }
                // whatever case, mark this news as current, we saw it in the feed

            } else {
                /* should happen only if items have date */
                zf_debug('Dated item marked as new: '.$item->title, DBG_SESSION);
                $this->_timestamps[$id]['ts'] = $now;
                $item->isNew= true;

            }
            $this->_timestamps[$id]['current'] = true;
        }
    }

    // takes the list of items, and delete those
    // not seen during the last marking round
    public function purge($subId) {

        $this->load($subId);

        // for each ids from our table
        // if id has not been seen, delete it
        // if id has been seen, mark it as unseen for next time
        // save

        $cnt = count($this->_timestamps);
        $tmpts = $this->_timestamps;

        //echo "<code>"; print_r($tmpts); echo "</code>";
        zf_debug($cnt. " news to check for purge on " . $subId, DBG_SESSION);

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
        zf_debug(count($this->_timestamps). " news left after purge on " .$subId, DBG_SESSION);
        $this->save();

    }

}

