<?php


/*=======*/
/*   a subscription, a channel with metadata
*/
class Subscription {

	public $id;

	//feed title
	public $title;

	// source website address
	public $link;

	// source or user generated description
	public $description;

	//URL of the subscription file - feed
	public $xmlurl;

	// number of items to show for this subs
	public $shownItems = ZF_DEFAULT_NEWS_COUNT;

	public $position = -1;
	public $isActive = true;

	//array of strings, one entry per tag
	public $tags = array();

	public function __construct($address){
		$this->xmlurl = $address;
		$this->id = zf_makeId($this->xmlurl, '');
	}

	public function initFromXMLattributes(&$attributes) {
		$this->position = $attributes['POSITION'];

		if ($attributes['TITLE'] != '') {
			$this->title = html2specialchars($attributes['TITLE']);
		}

		if ($attributes['HTMLURL'] != '') {
			$this->link = html2specialchars($attributes['HTMLURL']);
		}

		if ($attributes['DESCRIPTION'] != '') {
			$this->description = html2specialchars($attributes['DESCRIPTION']);
		}

		if ( ($attributes['SHOWNITEMS'] != '') && (is_numeric($attributes['SHOWNITEMS'])) ) {
			$this->shownItems = $attributes['SHOWNITEMS'];
		}

		if ( ($attributes['TAGS'] != '') ) {
			$this->tags = array_unique(explode(',',html2specialchars($attributes['TAGS'])));
			//print_r($this->tags);
		}

		$this->isActive = ($attributes['ISSUBSCRIBED'] == 'yes');

	}

	public function __toString(){
		return $this->xmlurl;
	}

}

/*=======*/

class NewsItem {

	// unique id from title and desc
	public $id;

	//subscription of this item
	public $subscriptionId;

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

	public function __construct($subscriptionId, $link, $title, $date) {
		$this->enclosures = array();
		$this->isTruncated = false;
		$this->isNew = false;
		$this->date_timestamp = -1;
		$this->link = $link;
		$this->title = $title;
		$this->date_timestamp = $date;
		$this->subscriptionId = $subscriptionId;
		$this->id = zf_makeId($subscriptionId, $this->link.$this->title);
	}

/*all sorts of processing to the item object
 Everything that happens here is cached
 - normalize items for dates and description
 - make relative paths absolute in item's description
 - set items and channel id

 publisher
*/
	public function normalize($history) {
		/* build our id, used as CSS element id. add timestamp to make it unique  */

		if ( $this->date_timestamp == 0) {

			// we should let our
			// history management system decide
			//$item['date_timestamp'] = 0;
			//print_r($channel);
			$firstseen = $history->getDateFirstSeen($this->id);
			if ($firstseen == 0) {
				$firstseen = time();
			}
			$this->date_timestamp = $firstseen;
			if (ZF_DEBUG & DBG_AGGR) {
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

	public function hasEnclosures(){
		return sizeof($this->enclosures)>0;
	}


	public function addEnclosure($enclosure) {
		array_push($this->enclosures, $enclosure);
	}

	/* returns a JSON-serializable header of this instance,
	without the publisher and enclosures */
	public function getSerializableHeader() {
		return new SerializableItemHeader($this);
	}

	/* returns a JSON-serializable header of this instance,
	including the publisher and without enclosures */
	public function getFullSerializableHeader($summary = false) {
		$header = new SerializableItemHeader($this);
		$header->setPublisher($this->publisher);
		if ($summary) $header->summary = $this->summary;
		return $header;
	}

	/* returns a JSON-serializable header of this instance,
	including the publisher and without enclosures */
	public function getSerializableItem() {
		return new SerializableItem($this);
	}
}

/*=======*/
class SerializableItemHeader {
	// unique id from title and desc
	public $id;
	// address and title of the news
	public $link;
	public $title;
	//time stamp of the news publication if provided, or first seen if not
	public $date_timestamp;
	public $isNew;

	public $summary; //might end up empty;
	//Publisher this item was obtained from
	// might end up empty
	public $subscriptionId;

	// make this object out of a NewsItem object
	public function __construct($item) {
		$this->id = $item->id;
		$this->link = $item->link;
		$this->title = $item->title;
		$this->isNew = $item->isNew;
		$this->date_timestamp = $item->date_timestamp;

	}

}

/*=======*/
class SerializableItem extends SerializableItemHeader {
	public $description;
	public $enclosures;
	public function __construct($item) {

		parent::__construct($item);
		$this->description = $item->description;
		$this->enclosures = $item->enclosures;

	}

}
/*=======*/
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


/*=======*/
class FeedOptions {
	// how to shorten the list of items
	public $trimType = 'none';
	// freshness number in days, hours or news
	public $trimSize = 0;
	public $matchExpression = "";

	public function setTrim($type, $size) {
		zf_debug("FeedOption trim to: $size $type");
		$this->trimType = $type;
		$this->trimSize = $size;
	}

	//allowed values: Xdays, Ynews,  Zhours, today,  yesterday, onlynew, auto
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

        // 'auto' is supported, but Feed object won't do anything with it
        // trimItems must be called explicitely with right value to be of any
        // use
		if ($trimString == 'auto') {
            $this->setTrim('auto', 0);
        }
	}

}

