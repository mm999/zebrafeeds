<?php


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





