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


// Zebrachannels OPML support

if( !defined('ZF_URL') ) die( 'Please, do not access this page directly.' );

//require_once($zf_path . 'includes/categories.php');

/* Structure of the Category array used all over
 The OPML file storing our category is "extended" to store our config data.
 Is it bad? probably, but convenient
  - the outline tag stores the channel options

 Structure of the channel record

 The structure mixes channel data and presentation data
 Elements structure:
   (channel related items)
	  title: channel title
	  htmlurl: url to website
	  text: =title
	  description: channel description
	  xmlurl: address of the subscription file

   (presentation items)
	  position: a number used to order channels in the page
	  refreshtime: time to cache channel news
	  showeditems: number of items to display by default
	  issubscribed: if yes, we have to display this channel
*/


/* XML parsing functions */
function zf_opmlStartElement($parser, $name, $attributes) {
	global $zf_opmlItems, $zf_opmlMode, $zf_opmlOptions;


	// if position is found, then it's a ZebraFeeds OPML file
	if ($zf_opmlMode == 'liberal') {
		$includeIt = true;
	} else {
		// in strict mode, we MUST have a position
		$includeIt =(isset($attributes['POSITION']) && $attributes['POSITION'] != '');
	}
	if ($includeIt) {
		$subscription = new Subscription();
		$subscription->initFromXMLAttributes($attributes);
		$zf_opmlItems[$subscription->position] = $subscription;
	}

	if (isset($attributes['VIEWMODE']) ) {
		$zf_opmlOptions['viewmode'] = ($attributes['VIEWMODE'] != '')?$attributes['VIEWMODE']:'feed';
	}
	if (isset($attributes['TRIMTYPE']) ) {
		$zf_opmlOptions['trimtype'] = ($attributes['TRIMTYPE'] != '')?$attributes['TRIMTYPE']:'news';
	}
	if (isset($attributes['TRIMSIZE']) ) {
		$zf_opmlOptions['trimsize'] = ($attributes['TRIMSIZE'] != '')?$attributes['TRIMSIZE']:'5';
	}
	if (ZF_DEBUG == 10) {
		print_r($attributes);
	}
}

function zf_opmlEndElement($parser, $name)
{
}


function html2specialchars($str){
	 $trans_table = array_flip(get_html_translation_table(HTML_ENTITIES));
	 return strtr($str, $trans_table);
}

class opml {

	private $_isFile;
	private $_parseMode;

	public $name;
	public $subscriptions;

	public $viewMode;
	public $trimType;
	public $trimSize;

	public $lastError;
	public $lastResult;

	public function __construct($name='') {
		$this->viewMode = 'feed';
		$this->trimType = 'news';
		$this->trimSize = 5;
		$this->subscriptions = array();

		$this->lastError = '';
		$this->lastResult = '';

		if (!empty($name)) {
			$this->name = $name;
			$this->_isFile = true;
		} else {
			$this->_isFile = false;
			$name = 'undefined';
		}

	}

	/* loads a list. returns true if success
	puts error in lastError if failure */
	public function load($from = '') {
		global $zf_path,$zf_opmlItems, $zf_opmlMode, $zf_opmlOptions;

		if ($from == '') {
			$opmlfilename = $this->getFileName();
			$this->_isFile = true;
			$zf_opmlMode = 'strict';

		} else {
			$opmlfilename = $from;
			// may be a file, probably not...
			$this->_isFile = false;
			$zf_opmlMode = 'liberal';
		}


		/* default values for parsing this opml file */
		$zf_opmlItems = array();
		$zf_opmlOptions = array();

		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, "zf_opmlStartElement", "zf_opmlEndElement");
		@$fp = fopen($opmlfilename, "r");
		$data = "";
		if ($fp) {
			if ($this->_isFile) {
				$data = fread($fp, filesize($opmlfilename));
			} else {
				while (true) {
					@$datas = fread($fp, 4096);
					if (strlen($datas) == 0) {
						break;
					}
					$data .= $datas;
				}
			}

			fclose($fp);
			$xmlResult = xml_parse($xml_parser, $data);
			$xmlError = xml_error_string(xml_get_error_code($xml_parser));
			$xmlCrtline = xml_get_current_line_number($xml_parser);
			xml_parser_free($xml_parser);
			unset($data);

			if ($xmlResult) {
				$this->subscriptions = $zf_opmlItems;
				unset($zf_opmlItems);
				$this->viewMode = $zf_opmlOptions['viewmode'];
				$this->trimType = $zf_opmlOptions['trimtype'];
				$this->trimSize = $zf_opmlOptions['trimsize'];

			} else {
				$this->lastError = "Error parsing subscriptions file <br />error: $xmlError at line: $xmlCrtline";
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

		$opmlfilename = $this->getFileName();

		$this->_sanitize();


		$fp = fopen($opmlfilename, "w");
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
			fwrite($fp, "<opml version=\"1.0\">\n\t<head>\n\t\t<title>" . htmlspecialchars($this->name,ENT_QUOTES) . "</title>" . $dateModified . $ownername . $owneremail . "\t</head>\n");
			fwrite($fp, "\t<body ".
							"viewmode=\"".$this->viewMode."\" ".
							"trimtype=\"".$this->trimType."\" ".
							"trimsize=\"".$this->trimSize."\" ".
						   ">\n");

			foreach ($this->subscriptions as $sub) {
				$temptitle = stripslashes($sub->channel->title);
				$tempdesc = stripslashes($sub->channel->description);
				$temphtmlurl = stripslashes($sub->channel->link);
				$tempxmlurl = stripslashes($sub->channel->xmlurl);
				fwrite($fp, "\t\t<outline type=\"rss\"" .
									" position=\"" . $sub->channel->position .
									"\" text=\"" . htmlspecialchars($temptitle, ENT_QUOTES) .
									"\" title=\"" . htmlspecialchars($temptitle, ENT_QUOTES) .
									"\" description=\"" . htmlspecialchars($tempdesc, ENT_QUOTES) .
									"\" xmlUrl=\"" . htmlspecialchars($tempxmlurl, ENT_QUOTES) .
									"\" htmlUrl=\"" . htmlspecialchars($temphtmlurl, ENT_QUOTES) .
									"\" refreshTime=\"" . $sub->channel->refreshTime .
									"\" showedItems=\"" . $sub->channel->shownItems .
									"\" isSubscribed=\"" . $sub->channel->isSubscribed .
									"\" />\n");
			}

			fwrite($fp, "\t</body>\n</opml>");
			$this->lastResult= 'Subscription list <strong>'.$this->name.'</strong> saved';
			fclose($fp);
			@chmod($opmlfilename, 0666);
			return true;
		} else {
			$this->lastError = "Error opening the subscription list <strong>".$this->name."</strong> for writing !";
			return false;
		}
	}

	public function getFileName($name='') {
		if ($name != ''){
			return ZF_OPMLDIR . '/'.$name.'.opml';
		} else {
			return ZF_OPMLDIR . '/'.$this->name.'.opml';
		}
	}


	public function getURL() {
		return ZF_URL .'/'. ZF_OPMLBASEDIR . '/'.urlencode($this->name).'.opml';
	}

	public function delete() {
		$deletefilename = $this->getFileName();
		if (file_exists($deletefilename)) {
			unlink($deletefilename);
			$this->lastResult = "List ".$this->name." deleted";
			return true;
		} else {
			//huh???
			$this->lastError = "Error: List ".$this->name." could not be found";
			return false;
		}
	}

	/*----------*/
	public function rename($newName) {

		$oldfilename = $this->getFileName();
		$newfilename = $this->getFileName($newName);

		if (file_exists($oldfilename) && !(file_exists($newfilename))) {
			if (rename($oldfilename,$newfilename)){
				$this->lastResult = "List renamed to <strong>".$newName."</strong>";
				$this->name = $newName;
				return true;
			} else {
				$this->lastError = "Error renaming list ".$this->name;
				return false;
			}
		} else {
			$this->lastError = "Error: list ".$newName." already exists.";
			return false;
		}

	}

	/*----------*/
	public function create() {
		if (!file_exists($this->getFileName())) {
			return $this->save();
		} else {
			$this->lastError = "Error: a list with the same name already exists.";
			return false;
		}
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
		for($i = 0; $i < count($this->subscriptions); $i++) {
			if ($i == $exceptAt) continue;
			//echo $i.' -- '.$this->channels;
			if ($this->subscriptions[$i]->position == $pos) {
				return $i;
			}
		}
		return -1;
	}
	public function removeChannelAtPos($index) {
		unset($this->subscription[$index]);
	}

	/* update the channel at position $index
	 return true if ok, or false if duplicate position problem */
	public function setChannelAtPos($index, $subscription) {
		$checkPos = $this->isPositionTaken($subscription->position, $index);
		if ($checkPos > -1){
			$this->lastError = 'Error: duplicate position with channel <em>'. $this->subscriptions[$checkPos]->title.'</em>';
			return false;
		}
		$this->subscriptions[$index] = $subscription;
		return true;
	}

	public function addSubscription($sub) {
		$newpos = $this->getNextPosition();
		$sub->position = $newpos;
		$this->subscriptions[$newpos] = $sub;
	}


	/* make sure we have correct data, particularly position
	should be called after read, and before save */
	public function _sanitize() {

		$nextPos = $this->getNextPosition();

		foreach ($this->subscriptions as $i => &$subscription) {
			if (!($subscription->position > 0 && is_numeric($subscription->position))) {
				$subscription->position = $nextPos++;
			}
		}
	}


}



?>
