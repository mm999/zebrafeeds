<?php
// ZebraFeeds - copyright (c) 2006 Laurent Cazalet
// http://www.cazalet.org/zebrafeeds
//
// zFeeder 1.6 - copyright (c) 2003-2004 Andrei Besleaga
// http://zvonnews.sourceforge.net
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


// ZebraFeeds subscription storage, OPML support

if( !defined('ZF_URL') ) die( 'Please, do not access this page directly.' );



/* XML parsing functions */
function zf_opmlStartElement($parser, $name, $attributes) {
	global $zf_opmlItems, $zf_opmlMode;


	// if position is found, then it's a ZebraFeeds OPML file
	if ($zf_opmlMode == 'liberal') {
		$includeIt = true;
	} else {
		// in strict mode, we MUST have a position
		$includeIt =(isset($attributes['POSITION']) && $attributes['POSITION'] != '');
	}
	if ($includeIt) {
		$subscription = new Subscription(html2specialchars($attributes['XMLURL']));
		$subscription->initFromXMLAttributes($attributes);
		$zf_opmlItems[$subscription->id] = $subscription;
		zf_debug('loaded sub: '.$subscription->id.' '.$subscription->title.'| tags('. implode(',', $subscription->tags).')', DBG_OPML);
	}

	if (ZF_DEBUG & DBG_LIST) {
		//print_r($attributes);
	}
}

function zf_opmlEndElement($parser, $name)
{
}


function html2specialchars($str){
	 $trans_table = array_flip(get_html_translation_table(HTML_ENTITIES));
	 return strtr($str, $trans_table);
}


class SubscriptionStorage {

	private $_parseMode;

	/* array of subscription Objects */
	protected $subscriptions;

	public $lastError;
	public $lastResult;
	protected $opmlfilename;

	private static $instance = NULL;
	
	public static function getInstance() {
		if (self::$instance == NULL) {
			self::$instance = new SubscriptionStorage();
		}
		return self::$instance;
	}

	private function __construct($source='') {
		$this->subscriptions = array();

		$this->lastError = '';
		$this->lastResult = '';

		if ($source == '') {
			$this->opmlfilename = ZF_OPMLFILE;
		} else {
			$this->opmlfilename = $source;
		}
		$this->load();
	}

	/* loads a list. returns true if success
	puts error in lastError if failure */
	protected function load() {
		global $zf_path,$zf_opmlItems, $zf_opmlMode;


		/* default values for parsing this opml file */
		$zf_opmlItems = array();

		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, "zf_opmlStartElement", "zf_opmlEndElement");
		zf_debug('Opening file '.$this->opmlfilename, DBG_OPML);
		@$fp = fopen($this->opmlfilename, "r");
		$data = "";
		if ($fp) {
			$data = fread($fp, filesize($this->opmlfilename));

			fclose($fp);
			$xmlResult = xml_parse($xml_parser, $data);
			$xmlError = xml_error_string(xml_get_error_code($xml_parser));
			$xmlCrtline = xml_get_current_line_number($xml_parser);
			xml_parser_free($xml_parser);
			unset($data);

			if ($xmlResult) {
				$this->subscriptions = $zf_opmlItems;
				unset($zf_opmlItems);

			} else {
				$this->lastError = "Error parsing subscriptions file <br />error: $xmlError at line: $xmlCrtline";
				zf_debug("$xmlError at line: $xmlCrtline", DBG_OPML);
				return false;
			}
		} else {
			$this->lastError = 'Error opening the subscriptions file!';
			return false;
		}
		$this->_sanitize();
		return true;

	}

	/* write the whole opml list to a file
	returns true if success. puts error message in lastError if failure
	*/
	public function save() {

		zf_debug('saving '.count($this->subscriptions).' subscriptions', DBG_OPML);

		$this->_sanitize();


		$fp = fopen($this->opmlfilename, "w");
		if ($fp) {
			if (ZF_OWNERNAME != '')
				$ownername = "\n\t\t<ownerName>" . htmlspecialchars(ZF_OWNERNAME,ENT_QUOTES) . "</ownerName>\n";
			else
				$ownername = '';
			if (ZF_OWNEREMAIL != '')
				$owneremail = "\n\t\t<ownerEmail>" . htmlspecialchars(ZF_OWNEREMAIL,ENT_QUOTES) . "</ownerEmail>\n";
			else
				$owneremail = '';
			$dateModified = "\n\t\t<dateModified>" . gmdate("D, d M Y H:i:s \G\M\T") . "</dateModified>\n";

			fwrite($fp, "<?xml version=\"1.0\"?>\n<!-- subscription list generated by " . ZF_VER . " on " . gmdate("D, d M Y H:i:s \G\M\T") . " -->\n");
			fwrite($fp, "<opml version=\"1.0\">\n\t<head>\n\t\t<title>ZebraFeeds</title>" . $dateModified . $ownername . $owneremail . "\t</head>\n");
			fwrite($fp, "\t<body>\n");

			foreach ($this->subscriptions as $sub) {
				$temptitle = stripslashes($sub->title);
				$tempdesc = stripslashes($sub->description);
				$temphtmlurl = stripslashes($sub->link);
				$tempxmlurl = stripslashes($sub->xmlurl);
				fwrite($fp, "\t\t<outline type=\"rss\"" .
									" position=\"" . $sub->position .
									"\" text=\"" . htmlspecialchars($temptitle, ENT_QUOTES) .
									"\" title=\"" . htmlspecialchars($temptitle, ENT_QUOTES) .
									"\" description=\"" . htmlspecialchars($tempdesc, ENT_QUOTES) .
									"\" xmlUrl=\"" . htmlspecialchars($tempxmlurl, ENT_QUOTES) .
									"\" htmlUrl=\"" . htmlspecialchars($temphtmlurl, ENT_QUOTES) .
									"\" shownItems=\"" . $sub->shownItems .
									"\" tags=\"" . htmlspecialchars(implode(',',$sub->tags), ENT_QUOTES) .
									"\" isSubscribed=\"" . ($sub->isActive?'yes':'no').
									"\" />\n");
			}

			fwrite($fp, "\t</body>\n</opml>");
			$this->lastResult= 'Subscription list file saved';
			zf_debug('saved', DBG_OPML);
			fclose($fp);
			@chmod($this->opmlfilename, 0666);
			return true;
		} else {
			zf_error('error saving OPML', DBG_OPML);
			$this->lastError = "Error opening the subscription list file for writing !";
			return false;
		}
	}

	public function getOPMLURL() {
		return ZF_URL . '/zebrafeeds.opml';
	}


	public function getNextPosition() {
		$lastpos = 0;
		foreach($this->subscriptions as $i => $sub) {
			if ($sub->position > $lastpos) {
				$lastpos = $sub->position;
			}
		}
		return $lastpos+1;
	}

	public function isPositionTaken($pos, $exceptAt) {
		foreach($this->subscriptions as $id => $sub) {
			if ($sub->position == $exceptAt) continue;
			//echo $i.' -- '.$sub->channels;
			if ($sub->position == $pos) {
				return $pos;
			}
		}
		return -1;
	}

	public function cancelSubscription($id) {
		zf_debug('cancelling subscription '.$id, DBG_OPML);
		unset($this->subscriptions[$id]);
		return $this->save();
	}

	/* update the channel at position $index
	 return true if ok, or false if duplicate position problem
	public function setChannelAtPos($index, $subscription) {
		$checkPos = $this->isPositionTaken($subscription->position, $index);
		if ($checkPos > -1){
			$this->lastError = 'Error: duplicate position with channel <em>'. $this->subscriptions[$checkPos]->title.'</em>';
			return false;
		}
		$this->subscriptions[$index] = $subscription;
		return true;
	}*/

	public function storeSubscription($sub) {
		if ($sub->position < 0) {
			$newpos = $this->getNextPosition();
			$sub->position = $newpos;
		}
		if (isset($this->subscriptions[$sub->id])) {
			unset($this->subscriptions[$sub->id]);
		}
		$this->subscriptions[$sub->id] = $sub;
		return $this->save();
	}

	public function getSubscription($id) {
		if (isset($this->subscriptions[$id])) {
			return $this->subscriptions[$id];
		} else {
			return null;
		}
	}

	//return an array of subscriptions indexed by id, matching tag
	public function getSubscriptions($tag='', $onlySubscribed = false) {
		$result = array();
		//zf_debug("getting subscriptions for tag='$tag', subscribed only? $onlySubscribed");
		foreach ($this->subscriptions as $sub) {
			// we want only those matching tag if relevant, and subscribed if requested
			if ( (($tag=='')?true:array_search($tag, $sub->tags)>-1) && ($onlySubscribed?$sub->isActive:true) ) {
				//zf_debug('found subscription '. $sub->title);
				$result[$sub->id] = $sub;
			}
		}
		return $result;
	}

	//return an array of subscriptions indexed by id, matching tag
	public function getActiveSubscriptions($tag='') {
		return $this->getSubscriptions($tag, true);
	}

	public function getTags() {
		$tags = array();
		foreach ($this->subscriptions as $sub) {
			// return tags only from active subscriptions
			if ($sub->isActive) $tags = array_merge($tags, $sub->tags);
		}
		return array_values(array_unique($tags));
	}

	/* make sure we have correct data, particularly position
	should be called after read, and before save */
	private function _sanitize() {

		$nextPos = $this->getNextPosition();

		foreach ($this->subscriptions as $i => $sub) {
			if (!($sub->position > 0 && is_numeric($sub->position))) {
				zf_debug('fixing position: '.$sub->title.' ('.$sub->position.') to '.$nextPos, DBG_OPML);
				$sub->position = $nextPos;
				$nextPos++;
			}
		}
	}

}



