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


// ZebraFeeds template management

if (!defined('ZF_VER')) exit;

class template {
	/* properties {{{*/
	public $name;

	public $pageHeader;
	private $header;
	private $footer;
	private $channel;
	private $channelFooter ;
	private $newsDay;
	private $newsDayFooter;
	private $news;
	private $newsByDate;
	private $article;

	private $isInvalid;

	// optional tags to convert
	private $_optionsTags;

	protected $_wrappingType;

	private $_html;
	// string for the template part being parsed. contained semi-processed output
	private $_buffer;
	private $_filename;/*}}}*/

	public function __construct($name) {
		$this->pageHeader = '';
		$this->header = '';
		$this->footer = '';
		$this->channel = '';
		$this->channelFooter = '';
		$this->newsDay = '';
		$this->newsDayFooter = '';
		$this->news = '';
		$this->newsByDate = '';
		$this->article = '';

		$this->isInvalid = false;

		$this->_html = '';
		// string for the template part being parsed. contained semi-processed output
		$this->_buffer = '';
		$this->_filename = '';
		$this->_optionsTags = array();

		/* wrapping types: ways to format the output
		supported values:
		"none", just echo
		"js", wraps the output into javascript code
		*/
		$this->_wrappingType = 'none';

		$this->name = $name;
		if (file_exists($this->_getFileName())) {
			$this->load();
		}
	}

	public function load() {

		$this->_loadFile();
		/* here are our template parts */
		$this->pageHeader	  = $this->_extractSection('templateHeader');
		$this->header		  = $this->_extractSection('header','',true);
		$this->footer		  = $this->_extractSection('footer','',true);
		$this->channel		  = $this->_extractSection('channel');
		$this->channelFooter  = $this->_extractSection('channelFooter', '', true);
		$this->news			  = $this->_extractSection('news');
		$this->newsByDate	  = $this->_extractSection('newsByDate', 'news');
		$this->newsDay		  = $this->_extractSection('newsDay','',true);
		$this->newsDayFooter  = $this->_extractSection('newsDayFooter', 'channelFooter', true);
		$this->article	  	  = $this->_extractSection('article');

		zf_debug("template loaded", DBG_RENDER);
		// unset to free memory
		unset($this->_html);
	}

	/* buffer print: format optional tags, and sends to print
	use this function if we have to process the optional tags*/
	protected function _printBuffer() {
		//last pass at tags substitution
		$this->_formatOptions();

		if ($this->_wrappingType == 'js') {
			$this->javascriptOutput($this->_buffer);
		} else {
			echo $this->_buffer;
		}
		unset($this->_buffer);

	}

	// simple print of a string, without any formatting
	protected function _print($output) {
		if ($this->_wrappingType == 'js') {
			$this->javascriptOutput($output);
		} else {
			echo $output;
		}
	}

	protected function javascriptOutput(&$output) {
		// remove all eol chars and escape single quotes
		echo "document.write('".str_replace(array("\r","\n","'"), array("", "", "\\'"), $output)."');\n";
	}

	public function printPageHeader() {
		$this->_buffer = $this->pageHeader;
		$this->_formatCommon();
		$this->_printBuffer();
	}

	public function printHeader() {
		$code = '';
		$this->_buffer = $this->header;
		$this->_formatCommon();
		// allow options in this sections, for RSS feed generation
		$this->_formatOptions();
		$this->_printBuffer();
	}


	public function printNews($item) {
		$this->_buffer = $this->news;
		$this->_formatCommon();
		$this->_formatChannel($item->feed);
		$this->_formatNews($item);
		$this->_printBuffer();

	}

	public function printNewsByDate($item) {
		$this->_buffer = $this->newsByDate;
		$this->_formatCommon();
		$this->_formatChannel($item->feed);
		$this->_formatNews($item);
		$this->_printBuffer();
	}

	/* normally called in ajax requests when containing
	element is always the same	*/
	public function printArticle($item) {
		$this->_buffer = $this->article;
		$this->_formatCommon();
		$this->_formatChannel($item->feed);
		$this->_formatNews($item);
		$this->_printBuffer();
	}

	public function printSummary($summary) {
		$this->_buffer = $summary;
		$this->_printBuffer();
	}

	public function printFooter() {
		$this->_buffer = $this->footer;
		$this->_formatCommon();
		$this->_printBuffer();

	}

	public function printDay($date) {
		$this->_print( str_replace('{date}', $date, $this->newsDay));
	}

	public function printDayFooter($date) {
		$this->_print(str_replace('{date}', $date, $this->newsDayFooter));
	}


	public function printChannel($feed) {
		$last_fetched = $feed->last_fetched;

		$this->_buffer = $this->channel;
		$this->_formatCommon();
		$this->_formatChannel($feed);

		/* now, replace the channel header specific tags */

		if ($last_fetched >0) {
			if ($this->name == 'SYSTEM.rss') {
				$chantime = date('r', $last_fetched);
			} else {
				$chantime = zf_transcode(strftime(ZF_PUBDATEFORMAT, $last_fetched));
			}
		} else {
			$chantime = "?";
		}

		$this->_buffer = str_replace('{lastupdated}', $chantime, $this->_buffer);

		$this->_printBuffer();

	}

	public function printChannelFooter() {
		if (!empty($this->channelFooter)) {
			$this->_buffer = $this->channelFooter;
			$this->_printBuffer();
		}

	}

	/* generate bottom line NOT USED IF JSON*/
	public function printCredits() {
		if ((!defined("ZF_SHOWCREDITS")) || (ZF_SHOWCREDITS!='no')) {
			echo ' <div id="generator">aggregated by <a href="http://www.cazalet.org/zebrafeeds">ZebraFeeds</a></div>';
		}

		zf_debugRuntime("after credits");
	}


	public function printErrors() {
		if ((ZF_DISPLAYERROR =="yes")  && (!empty($this->errorLog)) ) {
			// TODO fix $this->printStatus($this->errorLog);
		}
	}

	/* process tags that can be in any part of the template
	*/
	protected function _formatCommon() {
		$this->_buffer = str_replace('{scripturl}', ZF_URL, $this->_buffer);
		$this->_buffer = str_replace('{template}', $this->name, $this->_buffer);
	}

	protected function _formatOptions(){
		/* do options */
		foreach($this->_optionsTags as $tag => $value) {
			$this->_buffer = str_replace('{'.$tag.'}', $value, $this->_buffer);
		}
	}

	/* process channel-related template tags
	*/
	protected function _formatChannel($feed) {

		if ($this->name == 'SYSTEM.rss') {
			$stitle = htmlspecialchars($feed->title, ENT_QUOTES);
			$sdesc = htmlspecialchars($feed->description, ENT_QUOTES);
		} else {
			$stitle = $feed->title;
			$sdesc = $feed->description;
		}

		$slink = htmlspecialchars($feed->link, ENT_QUOTES);
		$sxmlurl = htmlspecialchars($feed->xmlurl, ENT_QUOTES);

		/*TODO: logo and favicon
		if ($feed->logo != "") {
			$slogo = htmlspecialchars($feed->logo, ENT_QUOTES);
			$this->_buffer = str_replace('{chanlogo}', "<a href=\"" . $slink. "\"><img src=\"" . $slogo. "\" style=\"border:0;\" alt=\"" . $stitle. "\" title=\"" . $stitle. "\" /></a>", $this->_buffer);
		} else {
			$this->_buffer = str_replace('{chanlogo}', '', $this->_buffer);
		}*/

		/*if ($feed->favicon != "") {
			$feed->favicon = htmlspecialchars($feed->favicon, ENT_QUOTES);
			$this->_buffer = str_replace('{chanfavicon}', "<a href=\"" . $slink. "\"><img src=\"" . $sfavicon. "\" style=\"border:0;\" width=\"16\" height=\"16\" alt=\"-\" title=\"" . $stitle. "\" /></a>", $this->_buffer);
		} else {
			$this->_buffer = str_replace('{chanfavicon}', '', $this->_buffer);
		}*/

		$this->_buffer = str_replace('{chanlink}', $feed->link, $this->_buffer);
		$this->_buffer = str_replace('{chanid}', $feed->subscriptionId, $this->_buffer);
		$this->_buffer = str_replace('{chandesc}', $sdesc, $this->_buffer);

		$this->_buffer = str_replace('{chantitle}', $stitle, $this->_buffer);
		$this->_buffer = str_replace('{feedurl}', $sxmlurl, $this->_buffer);

	}

	/* process item-related template tags
	*/
	protected function _formatNews($item) {
		if ($this->name == 'SYSTEM.rss') {
			$stitle = htmlspecialchars($item->title, ENT_QUOTES);
			$sdescription = htmlspecialchars($item->description, ENT_QUOTES);
			$ssummary = htmlspecialchars($item->summary, ENT_QUOTES);
		} else {
			$stitle = $item->title;
			$sdescription = $item->description;
			$ssummary = $item->summary;
		}
		$slink = htmlspecialchars($item->link, ENT_QUOTES);

		$this->_buffer = str_replace('{itemid}', $item->id, $this->_buffer);
		$this->_buffer = str_replace('{link}', $slink, $this->_buffer);
		$this->_buffer = str_replace('{link_encoded}', urlencode($slink), $this->_buffer);
		if ($item->date_timestamp != -1) {
			if ($this->name == 'SYSTEM.rss') {
				$pubdate = date('r', $item->date_timestamp);
			} else {
				$pubdate = zf_transcode(strftime(ZF_PUBDATEFORMAT, date($item->date_timestamp)));
			}
		} else {
			$pubdate = $item->pubdate;
		}

		$this->_buffer = str_replace('{pubdate}', $pubdate, $this->_buffer);
		$this->_buffer = str_replace('{relativedate}', getRelativeTime($item->date_timestamp), $this->_buffer);
		$this->_buffer = str_replace('{title}', $stitle, $this->_buffer);

		$newvalue = ($item->isNew)? ZF_ISNEW_STRING: '';
		$this->_buffer = str_replace('{isnew}', $newvalue, $this->_buffer);


		/* description */
		$this->_buffer = str_replace('{description}', $sdescription, $this->_buffer);

		$this->_formatEnclosures($item);

		$hasSummary = strpos($this->_buffer, '{summary}');
		$this->_buffer = str_replace('{summary}', $ssummary, $this->_buffer);

		$zfarticleurl = '?q=item&zftemplate='.$this->name.'&itemid='.$item->id.'&id='.$item->feed->subscriptionId;
		$this->_buffer = str_replace('{articleurl}', $zfarticleurl, $this->_buffer);

		$zfdownloadcontent = '?q=download-item&zftemplate='.$this->name.'&itemid='.$item->id.'&id='.$item->feed->subscriptionId;
		$this->_buffer = str_replace('{download}', $zfdownloadcontent, $this->_buffer);


		// for RSS feeds only
		$this->_buffer = str_replace('{guid}', md5($slink), $this->_buffer);

	}




	protected function _formatEnclosures($item) {
		// enclosures
		$enclosurelist = "";
		if ($item->hasEnclosures()) {
			if ($this->name == 'SYSTEM.rss') {
				foreach($item->enclosures as &$enclosure) {
					$enclosurelist .= ' <enclosure url="'.$enclosure->link.'" length="'.$enclosure->length.'" type="'.$enclosure->type.'" />';
				}

			} else {
				foreach($item->enclosures as &$enclosure) {
					//special treatment for images: inline
					if ($enclosure->isImage()) {
						$enclosurelist .= '<img src="'.$enclosure->link.'" style="margin: 4px; border:0;" alt="embedded image"/>';
						continue;
					}

					$icon = $enclosure->type;
					$title= $enclosure->type;
					if ($enclosure->isAudio()) {
						$icon = '<img src="'.ZF_URL.'/res/img/audio.png" style="border:0;" alt="Audio content"/>';
						$title = 'Audio content';
					}
					if ($enclosure->isVideo()) {
						$icon = '<img src="'.ZF_URL.'/res/img/video.png" style="border:0;" alt="Video content"/>';
						$title = 'Video content';
					}
					// nice output format for the size
					$size = sprintf("%01.2f MB",$enclosure->length / 1048576);
					if ($enclosure->length >0)
					$enclosurelist .= ' <a href="'.htmlentities($enclosure->link).
					'">'.
					$icon.' '.$title.', '.$size.'</a> ';
				}
			}
		} else {
			// give nbsp to avoid aving a possible empty span or div that breaks the validation
			if ($this->name !== 'SYSTEM.rss') {
				$enclosurelist = ' ';
			}
		}
		$this->_buffer = str_replace('{enclosures}', $enclosurelist, $this->_buffer);

	}


	/* parse the template file. looks for the string in $section to extract parts
	delimited by $section and "END".$section
	if section is not found, can optionally use a substitute  */
	protected function _extractSection($section, $substitute='' ) {
		$startdelim='<!-- ' . $section . ' -->';
		$len = strlen($startdelim);
		$startPos = strpos($this->_html, $startdelim);
		$endPos = strpos($this->_html, '<!-- END' . $section . ' -->');
		// do we find our markers?
		if ($startPos != false && $endPos != false) {
			$result = substr($this->_html, $startPos + $len, ($endPos - $startPos- $len));

		} else if (!empty($substitute)) {
			// we have a replacement
			$result = $this->_extractSection($substitute,'');
		} else {
			$result = '';
		}
		return($result);
	}

	public function _getFileName(){
		return ZF_TEMPLATESDIR.'/'.$this->name.'.html';
	}

	protected function _loadFile() {
		// could use file_get_contents, but it would require php >= 4.3.0
		$this->_html = '';
		$htmlData = '';
		$temp = file($this->_getFileName());
		foreach($temp as $i => $htmlData) {
			$this->_html .= $htmlData;
		}
	}

	/* add options tags that will be
	replaced once for all after rendering
	$tags: associative array */
	public function addTags($tags) {
		$this->_optionsTags = array_merge($this->_optionsTags, $tags);
	}

	public function setWrappingType($type) {
		$this->_wrappingType = $type;
	}
}

