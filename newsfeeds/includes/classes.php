<?php

/*  abstract specification for a channel providing a feed*/
class ChannelDescriptor {
	//unique id from url
	public $id;

	//feed title
	public $title;

	// source website address
	public $link;

	// source or user generated description
	public $description;

	//URL of the subscription file - feed
	public $xmlurl;

	public function normalize() {
		 $this->id = zf_makeId($this->xmlurl, '');
	}

}

/* channel with data obtained from publisher */
class Publisher extends ChannelDescriptor{

	//URL of favicon
	public $favicon;

	//URL to channel logo
	public $logo;

	public function __construct() {

	}

}


/*   for a channel providing a feed
impact: opml.php
*/
class Subscription {

	// channel Descriptor object
	public $channel;

	// number of items to show for this subs
	public $shownItems = ZF_DEFAULT_NEWS_COUNT;

	public $refreshTime = ZF_DEFAULT_REFRESH_TIME;
	public $position = 1;
	public $isSubscribed = true;

	public function __construct(){
		$this->channel = new ChannelDescriptor();
	}

	public function initFromXMLattributes(&$attributes) {
		$this->position = $attributes['POSITION'];

		if ($attributes['TITLE'] != '') {
			$this->channel->title = html2specialchars($attributes['TITLE']);
		}

		if ($attributes['HTMLURL'] != '') {
			$this->channel->link = html2specialchars($attributes['HTMLURL']);
		}

		if ($attributes['DESCRIPTION'] != '') {
			$this->channel->description = html2specialchars($attributes['DESCRIPTION']);
		}

		if ($attributes['XMLURL'] != '') {
			$this->channel->xmlurl = html2specialchars($attributes['XMLURL']);
		}

		if ( ($attributes['REFRESHTIME'] != '') && (is_numeric($attributes['REFRESHTIME'])) ) {
			$subscription->refreshTime = $attributes['REFRESHTIME'];
		}

		if ( ($attributes['SHOWEDITEMS'] != '') && (is_numeric($attributes['SHOWEDITEMS'])) ) {
			$subscription->refreshTime = $attributes['SHOWEDITEMS'];
		}

		$this->isSubscribed = ($attributes['ISSUBSCRIBED'] == 'yes');

	}

}


class NewsItem {

	// unique id from title and desc
	public $id;

	//Publisher object this item was obtained from
	public $publisher;

	// address and title of the news
	public $link;

	public $title;

	public $description;
	public $summary;
	public $isTruncated;
	public $isNew;
	//time stamp of the news publication if provided, or first seen if not
	public $date_timestamp;

	//array of enclosure objects
	public $enclosures;

	public function __construct() {
		$this->enclosures = array();
		$this->isTruncated = false;
		$this->isNew = false;
		$this->date_timestamp = -1;

	}

/*all sorts of processing to the item object
 Everything that happens here is cached
 - normalize items for dates and description
 - make relative paths absolute in item's description
 - set items and channel id

 publisher
*/
	public function normalize($publisher) {
		/* build our id, used as CSS element id. add timestamp to make it unique  */
		$this->id = zf_makeId($publisher->xmlurl, $this->link.$this->title);

		if ( $this->date_timestamp == 0) {

			// we should let our
			// history management system decide
			//$item['date_timestamp'] = 0;
			//print_r($channel);
			$history= $publisher->history;
			$firstseen = $history->getDateFirstSeen($this->id);
			if ($firstseen == 0) {
				$firstseen = time();
			}
			$this->date_timestamp = $firstseen;
			if (ZF_DEBUG==2) {
				zf_debug('-- using history time '. $this->date_timestamp);
			}

		}

		if (!isset($this->summary) || (strlen($this->summary) == 0) ) {
			  $this->summary = $this->description;
		}

		$strsum = strip_tags($this->summary);
		$this->isTruncated = false;
		if (strlen($strsum) > ZF_MAX_SUMMARY_LENGTH ) {
			$this->summary = substr($strsum, 0, ZF_SUMMARY_TRUNCATED_LENGTH).'...';
			$this->isTruncated = true;
		}

	}


	public function addEnclosure($enclosure) {
		array_push($this->items, $enclosure);
	}

}



class Enclosure {
	public $link;
	public $length;
	public $type;

	public function __construct() {
	}

	public function isImage() {
		return !(strpos($this->type, 'image') === false);
 	}

	public function isAudio() {
		return !(strpos($this->type, 'audio') === false);
	}

	public function isVideo() {
		return !(strpos($this->type, 'video') === false);
	}
}


class FeedOptions {
	public $trimType = 'none';
	public $trimSize = 0;
	public $matchExpression = "";

	public function setTrim($type, $size) {
		$this->trimType = $type;
		$this->trimSize = $size;
	}

	//allowed values: Xdays, Ynews,  Zhours, today,  yesterday, new
	public function setTrimStr($trimString) {
		if (preg_match("/([0-9]+)(.*)/",$trimString, $matches)) {
            $this->setTrim($matches[2],$matches[1]);
        }
		if ($trimString == 'today') {
            $this->setTrim('today', 0);
        }
		if ($trimString == 'yesterday') {
            $this->setTrim('yesterday', 0);
        }
		if ($trimString == 'onlynew') {
            $this->setTrim('onlynew', 0);
        }
	}

}

?>
