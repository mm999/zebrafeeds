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

require_once($zf_path . 'includes/common.php');

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

	private $isDynamic;
	private $isInvalid;

	// optional tags to convert
	private $_optionsTags;

	private $_wrappingType;

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
		$this->listHeader = '';
		$this->listFooter = '';
		$this->newsDay = '';
		$this->newsDayFooter = '';
		$this->news = '';
		$this->newsByDate = '';
		$this->article = '';

		$this->isDynamic = false;
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
		$this->listHeader     = $this->_extractSection('listHeader','',true);
		$this->listFooter     = $this->_extractSection('listFooter','',true);
		$this->channel		  = $this->_extractSection('channel'); 
		$this->channelFooter  = $this->_extractSection('channelFooter', '', true); 
		$this->news			  = $this->_extractSection('news');
		$this->newsByDate	  = $this->_extractSection('newsByDate', 'news');
		$this->newsDay		  = $this->_extractSection('newsDay','',true);
		$this->newsDayFooter  = $this->_extractSection('newsDayFooter', 'channelFooter', true);
		$this->article	  	  = $this->_extractSection('article');

		// dynamicnews tag can be either in pageHeader or in header
		$this->isDynamic = strpos($this->header, '{dynamicnews}') || strpos($this->pageHeader, '{dynamicnews}');
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

	public function printPageHeader($feed) {
		$this->_buffer = $this->pageHeader;
		$this->_formatCommon();
		$this->_formatDynamicCode();
		$this->_printBuffer();
	}

	public function printHeader() {
		$code = '';
		if ($this->isDynamic) {
			$code .= '<script type="text/javascript">var ZFURL="'.ZF_URL.'"; var ZFTEMPLATE="'.$this->name.'";</script>';
			$this->_buffer = $code. "\n". $this->header;
			$this->_formatDynamicCode();
		} else {
			$this->_buffer = $this->header;
		}
		$this->_formatCommon();
		// allow options in this sections, for RSS feed generation
		$this->_formatOptions();
		$this->_printBuffer();
	}

	public function printListHeader($feed) {
		$this->_buffer = $this->listHeader;
		$this->_formatCommon();
		// allow options in this sections, for RSS feed generation
		$this->_formatOptions();
		$this->_printBuffer();
	}


	protected function _formatDynamicCode() {
		$this->_buffer = str_replace('{dynamicnews}', '<script type="text/javascript" src="'.ZF_URL.'/zfclientside.js"></script>', $this->_buffer);

	}


	public function printNews(&$item) {
		$this->_buffer = $this->news;
		$this->_formatCommon();
		$this->_formatChannel($item['channel']);
		$this->_formatNews($item);
		$this->_printBuffer();

	}

	public function printNewsByDate(&$item) {
		$this->_buffer = $this->newsByDate;
		$this->_formatCommon();
		$this->_formatChannel($item['channel']);
		$this->_formatNews($item);
		$this->_printBuffer();
	}

	/* normally called in ajax requests when containing
	element is always the same	*/
	public function printArticle(&$item) {
		$this->_buffer = $this->article;
		$this->_formatCommon();
		$this->_formatChannel($item['channel']);
		$this->_formatNews($item);
		$this->_printBuffer();
	}

	public function printFooter() {
		$this->_buffer = $this->footer;
		$this->_formatCommon();
		$this->_printBuffer();

	}
	public function printListFooter() {
		$this->_buffer = $this->listFooter;
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
		$channel = $feed->channel;
		$last_fetched = $feed->last_fetched;

		$this->_buffer = $this->channel;
		$this->_formatCommon();
		$this->_formatChannel($channel);

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


	/* process tags that can be in any part of the template 
	*/
	protected function _formatCommon() {
		$this->_buffer = str_replace('{scripturl}', ZF_URL, $this->_buffer);
	}

	protected function _formatOptions(){
		/* do options */
		foreach($this->_optionsTags as $tag => $value) {
			$this->_buffer = str_replace('{'.$tag.'}', $value, $this->_buffer);
		}
	}

	/* process channel-related template tags 
	*/
	protected function _formatChannel(&$channel) {
		$schannel = $channel;
		if ($this->name == 'SYSTEM.rss') {
			$schannel['title'] = htmlspecialchars($channel['title'], ENT_QUOTES);
			$schannel['description'] = htmlspecialchars($channel['description'], ENT_QUOTES);
		} 
		$schannel['link'] = htmlspecialchars($channel['link'], ENT_QUOTES);
		$schannel['xmlurl'] = htmlspecialchars($channel['xmlurl'], ENT_QUOTES);

		if (isset($channel['logo']) && ($channel['logo'] != "")) {
			$schannel['logo'] = htmlspecialchars($channel['logo'], ENT_QUOTES);
			$this->_buffer = str_replace('{chanlogo}', "<a href=\"" . $schannel['link']. "\"><img src=\"" . $schannel['logo']. "\" style=\"border:0;\" alt=\"" . $schannel['title']. "\" title=\"" . $schannel['title']. "\" /></a>", $this->_buffer);
		} else {
			$this->_buffer = str_replace('{chanlogo}', '', $this->_buffer);
		}

		if (isset($channel['favicon']) && ($channel['favicon'] != "")) {
			$schannel['favicon'] = htmlspecialchars($channel['favicon'], ENT_QUOTES);
			$this->_buffer = str_replace('{chanfavicon}', "<a href=\"" . $schannel['link']. "\"><img src=\"" . $schannel['favicon']. "\" style=\"border:0;\" width=\"16\" height=\"16\" alt=\"" . $schannel['title']. "\" title=\"" . $schannel['title']. "\" /></a>", $this->_buffer);
		} else {
			$this->_buffer = str_replace('{chanfavicon}', '', $this->_buffer);
		}
		
		$this->_buffer = str_replace('{chanlink}', $schannel['link'], $this->_buffer);
		$this->_buffer = str_replace('{chanid}', $schannel['id'], $this->_buffer);
		$this->_buffer = str_replace('{chandesc}', $schannel['description'], $this->_buffer);

		$this->_buffer = str_replace('{chantitle}', $schannel['title'], $this->_buffer);
		$this->_buffer = str_replace('{feedurl}', $schannel['xmlurl'], $this->_buffer);

	}

	/* process item-related template tags 
	*/
	protected function _formatNews(&$item) {
		$sitem = $item;
		if ($this->name == 'SYSTEM.rss') {
			$sitem['title'] = htmlspecialchars($item['title'], ENT_QUOTES);
			$sitem['description'] = htmlspecialchars($item['description'], ENT_QUOTES);
			$sitem['summary'] = htmlspecialchars($item['summary'], ENT_QUOTES);
		} 
		$sitem['link'] = htmlspecialchars($item['link'], ENT_QUOTES);

		$this->_buffer = str_replace('{itemid}', $item['id'], $this->_buffer);
		$this->_buffer = str_replace('{link}', $sitem['link'], $this->_buffer);
		$this->_buffer = str_replace('{link_encoded}', urlencode($sitem['link']), $this->_buffer);
		if (isset($item['date_timestamp']) && ($item['date_timestamp'] != -1)) {
			if ($this->name == 'SYSTEM.rss') {
				$pubdate = date('r', $item['date_timestamp']);
			} else {
				$pubdate = zf_transcode(strftime(ZF_PUBDATEFORMAT, date($item['date_timestamp'])));
			}
		} else {
			$pubdate = $item['pubdate'];
		}

		$this->_buffer = str_replace('{pubdate}', $pubdate, $this->_buffer);
		$this->_buffer = str_replace('{relativedate}', getRelativeTime($item['date_timestamp']), $this->_buffer);
		$this->_buffer = str_replace('{title}', $sitem['title'], $this->_buffer);
		if (ZF_NEWITEMS!='no' && isset($item['isnew']) && $item['isnew']) {
			$this->_buffer = str_replace('{isnew}', ZF_ISNEW_STRING, $this->_buffer);

		} else {
			$this->_buffer = str_replace('{isnew}', '', $this->_buffer);
		}

		/* description */
		$this->_buffer = str_replace('{description}', $sitem['description'], $this->_buffer);

		$this->_formatEnclosures($item);

		$hasSummary = strpos( $this->_buffer, '{summary}');
		$this->_buffer = str_replace('{summary}', $sitem['summary'], $this->_buffer);

		$zfarticleurl = ZF_HOMEURL.'?type=article&zftemplate='.urlencode($this->name).'&itemid='.$item['id'].'&xmlurl='.urlencode($item['channel']['xmlurl']);
		
		if ($hasSummary && $item['istruncated'])
			$readmorelink = '<a href="'.$zfarticleurl.'">Read full news</a>';
		else
			$readmorelink = '';
		$this->_buffer = str_replace('{readfullnewslink}', $readmorelink, $this->_buffer);
		$this->_buffer = str_replace('{articleurl}', $zfarticleurl, $this->_buffer);


		// for RSS feeds only
		$this->_buffer = str_replace('{guid}', md5($sitem['link']), $this->_buffer);

	}



	
	protected function _formatEnclosures(&$item) {
		// enclosures
		$enclosurelist = "";
		if (isset($item['enclosures'])) {
			if ($this->name == 'SYSTEM.rss') {
				foreach($item['enclosures'] as $enclosure) {
					$enclosurelist .= ' <enclosure url="'.$enclosure['link'].'" length="'.$enclosure['length'].'" type="'.$enclosure['type'].'" />';
				}
			
			} else {
				foreach($item['enclosures'] as $enclosure) {
					//special treatment for images: inline
					if (!(strpos($enclosure['type'], 'image') === false)) {
						$enclosurelist .= '<img src="'.$enclosure['link'].'" style="margin: 4px; border:0;" alt="embedded image"/>';
						continue;
					}

					$icon = $enclosure['type'];
					$title= 'Undetected file';
					if (!(strpos($enclosure['type'], 'audio') === false)) {
						$icon = '<img src="'.ZF_URL.'/images/audio.png" style="border:0;" alt="Audio content"/>';
						$title = 'Audio content.';
					}
					if (!(strpos($enclosure['type'], 'video') === false)) {
						$icon = '<img src="'.ZF_URL.'/images/video.png" style="border:0;" alt="Video content"/>';
						$title = 'Video content.';
					}
					// nice output format for the size
					$size = sprintf("%01.2f MB",$enclosure['length'] / 1048576);
					$enclosurelist .= ' <a href="'.htmlentities($enclosure['link']).
					'" title="'.$title.' Size: '.$size.'">'.
					$icon.'</a> ';					   
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
			$result = $this->_extractSection($substitute,'', $optional);
		} else {
			$result = '';
		}
		return($result);
	}

	public function _getFileName(){
		global $zf_path;
		return $zf_path .'templates/'.$this->name.'.html';
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

}

?>
