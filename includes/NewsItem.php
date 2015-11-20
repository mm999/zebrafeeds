<?php

class NewsItem {

	// unique id from title and desc
	public $id;

	// address and title of the news
	public $link;

	public $title;

	public $description;
	public $summary;

	public $isNew; /* TODO find a better way of doing this */
	//time stamp of the news publication if provided, or first seen if not
	public $pubdate;

	public $source;

	//array of enclosure objects
	public $enclosures;

	public function __construct($source) {
		// init all data, including isnew by comparing newdate and ts_impress
		// structure to allow easy export to JSON object, while keeping class and members
		$this->enclosures = array();
		$this->source = $source;
	}

	public static function createFromFlatArray($source, $item, $isNewSpot = 0xFFFFFFFF) {
		zf_debug('processing cached item', DBG_ITEM | DBG_FEED);
		if (ZF_DEBUG & DBG_ITEM) var_dump($item);
		$feedItem = new NewsItem($source);

		zf_debug("new spot: $isNewSpot impressed on: ".$item['ts_impress'], DBG_ITEM | DBG_FEED);
		$feedItem->isNew = $isNewSpot < $item['ts_impress'];
		$enclosures = json_decode($item['enclosures']);
		/* remove fields we dont need anymore */
		unset($item['ts_impress']);
		unset($item['enclosures']);
		unset($item['source_id']);
		foreach ($item as $key => $value) {
			if (!is_array($value))
				$feedItem->$key = $value;
		}

        if (is_array($enclosures)) {
			foreach ($enclosures as $enc) {
				$newenc = new Enclosure();
				foreach ($enc as $key => $value) {
					$newenc->$key = $value;
				}
/*				$newenc->link = $enc['link'];
				$newenc->length = $enc['length'];
				$newenc->type = $enc['type'];*/
				array_push($feedItem->enclosures, $newenc);
        	}
        }

		zf_debug('cached item after processing', DBG_FEED | DBG_ITEM); if (ZF_DEBUG & DBG_ITEM) var_dump($feedItem);

	    return $feedItem;
	}

	/* returns a JSON-serializable header of this instance,
	without the publisher, enclosures and full body */
	public function getSerializableHeader() {
		$header = (array)$this;

		unset($header['description']);
		unset($header['source']);
		$header['enclosures'] = array();
		foreach ($this->enclosures as $enc) {
			$header['enclosures'][] = (array)$enc;
		}
		return $header;
	}

	/* returns a JSON-serializable header of this instance,
	including the publisher and enclosures */
	public function getFullSerializableHeader($wantSummary = false) {
		$header = (array)$this;

		if (!$wantSummary) unset($header['summary']);
		$header['source'] = (array)$this->source;
		$header['enclosures'] = array();
		foreach ($this->enclosures as $enc) {
			$header['enclosures'][] = (array)$enc;
		}
		return $header;
	}

	/* returns a JSON-serializable header of this instance,
	including the publisher and enclosures */
	public function getSerializableItem() {
		$header = (array)$this;

		$header['source'] = (array)$this->source;
		$header['enclosures'] = array();
		foreach ($this->enclosures as $enc) {
			$header['enclosures'][] = (array)$enc;
		}
		return $header;
	}

	public function hasEnclosures() {
		return sizeof($this->enclosures)>0;
	}
}


/*=======*/
class Enclosure {
	public $link;
	public $length;
	public $type;

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
