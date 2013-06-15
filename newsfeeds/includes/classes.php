<?php

/*=======*/
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

	public function __construct($address){
		$this->xmlurl = $address;
		$this->id = zf_makeId($this->xmlurl, '');
	}

	public function normalize() {
	}

	public function __toString(){
		return $this->xmlurl;
	}
}

/*=======*/
/* channel with data obtained from publisher */
class Publisher extends ChannelDescriptor{

	//URL of favicon
	public $favicon;

	//URL to channel logo
	public $logo;

}


/*=======*/
/*   for a channel providing a feed
impact: opml.php
*/
class Subscription {

	// channel Descriptor object
	public $channel;

	// number of items to show for this subs
	public $shownItems = ZF_DEFAULT_NEWS_COUNT;

	public $refreshTime = ZF_DEFAULT_REFRESH_TIME;
	public $position = -1;
	public $isSubscribed = true;

	public function __construct($address){
		$this->channel = new ChannelDescriptor($address);
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

		if ( ($attributes['REFRESHTIME'] != '') && (is_numeric($attributes['REFRESHTIME'])) ) {
			$this->refreshTime = $attributes['REFRESHTIME'];
		}

		if ( ($attributes['SHOWEDITEMS'] != '') && (is_numeric($attributes['SHOWEDITEMS'])) ) {
			$this->shownItems = $attributes['SHOWEDITEMS'];
		}

		$this->isSubscribed = ($attributes['ISSUBSCRIBED'] == 'yes');

		// TODO here: get list name, possibly get list object from ListManager. Singleton?
	}

	public function __toString(){
		return $this->channel->__toString();
	}

}

/*=======*/
class SubscriptionList {

	public $name;

	/* array of subscription Objects of this list, indexed by channel->id*/
	public $subscriptions;

	/* how this list will be rendered by default in printMainView
	- feed: sorted by channel, max "subscription->shownItems" items displayed
	- date: all news; sorted by date
	- trim: by date, according to trimType and trimSize
	setting ignored in async mode (output by calls to async.php)
	*/
	public $viewMode;

	/* render max "trimSize" latest news, days, hours
	irrelevant if Aggregator viewMode is feed */
	public $trimType;

	/* number of news/days/hours to render
	irrelevant if Aggregator viewMode is feed */
	public $trimSize;

}

/*=======*/

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

	public function __construct($publisherUrl, $link, $title, $date) {
		$this->enclosures = array();
		$this->isTruncated = false;
		$this->isNew = false;
		$this->date_timestamp = -1;
		$this->link = $link;
		$this->title = $title;
		$this->date_timestamp = $date;
		$this->id = zf_makeId($publisherUrl, $this->link.$this->title);
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
	public $publisherId;
	public $publisherLink;
	public $publisherTitle;
	public $publisherIcon;

	// make this object out of a NewsItem object
	public function __construct($item) {
		$this->id = $item->id;
		$this->link = $item->link;
		$this->title = $item->title;
		$this->isNew = $item->isNew;
		$this->date_timestamp = $item->date_timestamp;

	}

	/* assign it a Publisher object */
	public function setPublisher($publisher) {
		$this->publisherId = $publisher->id;
		$this->publisherLink = $publisher->link;
		$this->publisherTitle = $publisher->title;
		$this->publisherIcon = $publisher->favicon;
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
		$this->setPublisher($item->publisher);

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

