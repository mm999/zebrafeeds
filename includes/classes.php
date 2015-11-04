<?php


/*=======*/
/*   a subscription, a channel with metadata
*/
class Subscription {

	public $source;

	// number of items to show for this subs
	public $shownItems = ZF_DEFAULT_NEWS_COUNT;

	public $position = -1;
	public $isActive = true;

	//array of strings, one entry per tag
	public $tags = array();

	public function __construct(){
	}

	public static function fromXMLattributes(&$attributes) {
		$instance = new Self();
		$instance->source = Source::fromXMLattributes($attributes);

		$instance->position = $attributes['POSITION'];

		if ( ($attributes['SHOWNITEMS'] != '') && (is_numeric($attributes['SHOWNITEMS'])) ) {
			$instance->shownItems = $attributes['SHOWNITEMS'];
		}

		if ( ($attributes['TAGS'] != '') ) {
			$instance->tags = array_unique(explode(',',html2specialchars($attributes['TAGS'])));
			//print_r($this->tags);
		}

		$instance->isActive = ($attributes['ISSUBSCRIBED'] == 'yes');

		return $instance;
	}

	public function __toString(){
		return $this->source->xmlurl;
	}

}


class Source {

	public $id;

	//feed title
	public $title;

	// source website address
	public $link;

	// source or user generated description
	public $description;

	//URL of the subscription file - feed
	public $xmlurl;

	public function __construct(){
	}


	public static function create($title, $link, $description, $xmlurl) {
		$instance = new Self();
		$instance->xmlurl = $xmlurl;
		$instance->link = $link;
		$instance->description = $description;
		$instance->title = $title;
		$instance->id = zf_makeId($instance->xmlurl, '');
		return $instance;
	}


	public static function fromXMLattributes(&$attributes) {

		$instance = new Self();

		if ($attributes['TITLE'] != '') {
			$instance->title = html2specialchars($attributes['TITLE']);
		}

		if ($attributes['HTMLURL'] != '') {
			$instance->link = html2specialchars($attributes['HTMLURL']);
		}

		if ($attributes['XMLURL'] != '') {
			$instance->xmlurl = html2specialchars($attributes['XMLURL']);
		}

		if ($attributes['DESCRIPTION'] != '') {
			$instance->description = html2specialchars($attributes['DESCRIPTION']);
		}
		$instance->id = zf_makeId($instance->xmlurl, '');
		return $instance;
	}

	public static function fromAddress($xmlurl){
		$proxy = new SourceProxy($xmlurl);
		return $proxy->makeSourceFromAddress($xmlurl);
	}


}

/*=======*/

class NewsItem {

	// unique id from title and desc
	public $id;

	// address and title of the news
	public $link;

	public $title;

	public $description;
	public $summary;
	public $isTruncated;
	public $isNew;
	//time stamp of the news publication if provided, or first seen if not
	public $date_timestamp;

	public $feed;
	public $source;

	//array of enclosure objects
	public $enclosures;

	public function __construct($feed, $source, $link, $title, $date, $id='') {
		$this->enclosures = array();
		$this->isTruncated = false;
		$this->isNew = false;
		$this->date_timestamp = -1;
		$this->link = $link;
		$this->title = $title;
		$this->date_timestamp = $date;
		/* if GUID available, use it as basis for id */
		if (strlen($id) > 0 ) {
			$key = $id;
		} else {
			$key = $this->link.$this->title;
		}
		$this->id = zf_makeId($source->id, $key);
		$this->feed = $feed;
		$this->source = $source;
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
		$header->subscriptionId = $this->feed->subscriptionId;
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

