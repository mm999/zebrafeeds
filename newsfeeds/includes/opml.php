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
function zf_opmlStartElement($parser, $name, $attributes)
{
	global $zf_opmlItems, $zf_opmlCount, $zf_opmlMode, $zf_opmlOptions;


	// if position is found, then it's a ZebraFeeds OPML file
	if ($zf_opmlMode == 'liberal') {
		$includeIt = true;
	} else {
		// in strict mode, we MUST have a position
		$includeIt =(isset($attributes['POSITION']) && $attributes['POSITION'] != ''); 
	}
	if ($includeIt) {

		$pos = $zf_opmlCount;
		$zf_opmlItems[$pos]['position'] = $attributes['POSITION'];
		$zf_opmlItems[$pos]['title'] = ($attributes['TITLE'] != '')?$attributes['TITLE']:'';
		$zf_opmlItems[$pos]['htmlurl'] = ($attributes['HTMLURL'] != '')?$attributes['HTMLURL']:'';
		$zf_opmlItems[$pos]['text'] = ($attributes['TEXT'] != '')?$attributes['TEXT']:'';
		$zf_opmlItems[$pos]['description'] = ($attributes['DESCRIPTION'] != '')?$attributes['DESCRIPTION']:'';
		$zf_opmlItems[$pos]['xmlurl'] = ($attributes['XMLURL'] != '')?$attributes['XMLURL']:'';
		$zf_opmlItems[$pos]['refreshtime'] = ($attributes['REFRESHTIME'] != '')?$attributes['REFRESHTIME']:ZF_DEFAULT_REFRESH_TIME;
		$zf_opmlItems[$pos]['showeditems'] = ($attributes['SHOWEDITEMS'] != '')?$attributes['SHOWEDITEMS']:ZF_DEFAULT_NEWS_COUNT;
		$zf_opmlItems[$pos]['issubscribed'] = ($attributes['ISSUBSCRIBED'] != '')?$attributes['ISSUBSCRIBED']:'yes';
	}

	// backwards compatibility
	if (isset($attributes['GROUPBY']) ) {
		$zf_opmlOptions['viewmode'] = ($attributes['GROUPBY'] != '')?$attributes['GROUPBY']:'feed';
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
	global $zf_opmlCount;
	if ($name == 'OUTLINE') $zf_opmlCount++;
}




class opml {

	var $_isFile;
	var $_parseMode;
	var $name;
	var $channels;
	// options array, not exactly like feed options in Aggregator class. 
	//has the view mode field, which is a separate member in aggregator
	var $options;
	
	var $lastError;
	var $lastResult;
   
	function opml($name='') {
		$this->options = array( 'viewmode' =>'feed', 
								'trimtype' => 'news',
								 'trimsize' => 5 );
		$this->channels = array();
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
	function load($from = '') {
		global $zf_path,$zf_opmlItems, $zf_opmlCount, $zf_opmlMode, $zf_opmlOptions;

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
		$zf_opmlCount = 0;
		$zf_opmlItems = array();
		$zf_opmlOptions = $this->options;

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
				$this->channels = $zf_opmlItems; //usort($zf_opmlItems, "zf_compareChannelPos");
				unset($zf_opmlItems);
				$this->options = $zf_opmlOptions;
				
				
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
	function save() {

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
							"viewmode=\"".$this->options['viewmode']."\" ".
							"trimtype=\"".$this->options['trimtype']."\" ".
							"trimsize=\"".$this->options['trimsize']."\" ".
						   ">\n");

			foreach ($this->channels as $channel) {
				$temptitle = stripslashes($channel["title"]);
				$tempdesc = stripslashes($channel["description"]);
				$temphtmlurl = stripslashes($channel["htmlurl"]);
				$tempxmlurl = stripslashes($channel["xmlurl"]);
				fwrite($fp, "\t\t<outline type=\"rss\"" .
									" position=\"" . $channel["position"] .
									"\" text=\"" . htmlspecialchars($temptitle, ENT_QUOTES) .
									"\" title=\"" . htmlspecialchars($temptitle, ENT_QUOTES) .
									"\" description=\"" . htmlspecialchars($tempdesc, ENT_QUOTES) .
									"\" xmlUrl=\"" . htmlspecialchars($tempxmlurl, ENT_QUOTES) .
									"\" htmlUrl=\"" . htmlspecialchars($temphtmlurl, ENT_QUOTES) .
									"\" refreshTime=\"" . $channel["refreshtime"] .
									"\" showedItems=\"" . $channel["showeditems"] .
									"\" isSubscribed=\"" . $channel["issubscribed"] .
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

	function getFileName($name='') {
		if ($name != ''){
			return ZF_OPMLDIR . '/'.$name.'.opml';
		} else {
			return ZF_OPMLDIR . '/'.$this->name.'.opml';
		}
	}


	function getURL() {
		return ZF_URL .'/'. ZF_OPMLBASEDIR . '/'.urlencode($this->name).'.opml';
	}

	function delete() {
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
	function rename($newName) {

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
	function create() {
		if (!file_exists($this->getFileName())) {
			return $this->save();
		} else {
			$this->lastError = "Error: a list with the same name already exists.";
			return false;
		}
	}


	function getNextPosition() {
		$lastpos = 0;
		foreach($this->channels as $i => $item) {
			if ($item['position'] > $lastpos) {
				$lastpos = $item['position'];
			}
		}
		return $lastpos+1;
	}

	function isPositionTaken($pos, $exceptAt) {
		for($i = 0; $i < count($this->channels); $i++) {
			if ($i == $exceptAt) continue;
			//echo $i.' -- '.$this->channels;
			if ($this->channels[$i]['position'] == $pos) {
				return $i;
			}
		}
		return -1;
	}
	function removeChannelAtPos($index) {
		unset($this->channels[$index]);
	}

	/* update the channel at position $index
	 return true if ok, or false if duplicate position problem */
	function setChannelAtPos($index, $channel) {
		$checkPos = $this->isPositionTaken($channel['position'], $index);
		if ($checkPos > -1){
			$this->lastError = 'Error: duplicate position with channel <em>'. $this->channels[$checkPos]['title'].'</em>';
			return false;
		}
		$this->channels[$index] = array_merge($this->channels[$index], $channel);
		return true;
	}

	function addChannel($channel) {
		$this->channels[] = $channel;
	}

	
	/* make sure we have correct data in our array
	should be called after read, and before save */
	function _sanitize() {

		$nextPos = $this->getNextPosition();

		foreach ($this->channels as $i => $channel) {
			if (!($channel["position"] > 0 && is_numeric($channel["position"]))) {
				$channel["position"] = $nextPos++;
			}
			if (!($channel["issubscribed"] == 'yes' || $channel["issubscribed"] == 'no'))
				$channel["issubscribed"] = 'no';

			if (!(is_numeric($channel["refreshtime"]))) {
				$channel["refreshtime"] = 60;
			}
			if (!(is_numeric($channel["showeditems"]) && $channel["showeditems"] > -1)) {
				$channel["showeditems"] = 0;
			}

			$this->channels[$i] = $channel;

		}
	}


}



?>
